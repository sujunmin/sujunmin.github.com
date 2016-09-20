---
title: 從 SQL Server 到 MariaDB - [5] Log-shipping 與 MariaDB Replication
tags:
  - MariaDB
  - MSSQL
date: 2016-09-19 11:50:18
---

## 需求說明
在資料庫的備援部份，採用 SQL Server Log-shipping 方式來處理，希望在移轉至 MariaDB 時也有不錯的類似 Log-shipping 方式執行。

## MariaDB Replication uses GTID
接下來的文章來自於許多的文件，從[官方文件](https://mariadb.com/kb/en/mariadb/gtid/)，[MariaDB · 版本特性 · MariaDB 的 GTID 介绍](http://mysql.taobao.org/monthly/2016/02/08/)，還有[mariadb10 GTID 研究笔记. md](https://segmentfault.com/a/1190000000598062)與[實作](https://segmentfault.com/a/1190000000601849)，整理了一下。

### Master 與 Slave
與 SQL Server Log-shipping 不一樣的地方如下
1. 不管什麼樣的 Replication (樹狀或是環型串聯)，發動端皆是 <b>Slave</b>，也就是說 Master 不存放任何有關 Replication 的資料，所有資料皆在 Slave 裡。
2. 在 Primary Database (Master) 會是 Backup Trasnsaction 的動作，而在 Secondary Database (Slave) 是 Copy 與 Restore 的動作，在 MariaDB Replication 只會在 Slave 連到 Master 時看要複製哪一段的 binlog 加以重現。
3. 因為 GTID 的設計，一個 Slave 的 Master 可以是多個，當然一個 Master 可以有多個 Slave。
4. Slave 重現完資料以後就會繼續跟 Master 要進一步的 binlog 資料，所以基本上是沒有落差可言的，在 SQL Server Log-shipping 是有發動時間設定的(預設為 15 分鐘)。
5. 系統的資料庫 (mysql 那些)也可以做 Replication。

在 4 的部份，其實對於現在的維運有些麻煩。因為這 15 分鐘的落差，可以拿來利用成正式環境的 buffer，下錯指令時如果還在 15 分鐘的時間分隔下，還可以趕快去備援區拿資料蓋回去，現在沒有時間設定的話就比較麻煩。有人發個 [issue](https://jira.mariadb.org/browse/MDEV-7145) 希望能 merge MySQL 的 `MASTER_DELAY` 功能，不過表定是 10.2.x 的功能，希望能實作在 10.1.x。

在 5 的部份我覺得還蠻方便的就是帳號密碼的同步，在 SQL Server Log-shipping 是要分開想辦法同步的，管理就很方便了。

### Slave 如何透過 GTID 做 Replication

GTID 的規格部份就不解釋了，上面的文件寫的很清楚。

在 MariaDB 有一些 Server Variables 紀錄 GTID 的資訊

| Server Variables Names | 意義                               |
|------------------------|------------------------------------|
| `gtid_binlog_pos`      | binlog 裡最後一筆紀錄的  GTID        |
| `gtid_slave_pos`       | Slave 上紀錄的最後一個 binlog GTID   |
| `gtid_current_pos`     | Database 上紀錄的最後一個 binlog GTID|

什麼叫作 "紀錄的最後一個 binlog GTID" 呢?

對 `gtid_slave_pos` 來說，就是 master 的最後一筆 GTID。

對 `gtid_current_pos` 來說，不管當下的 Instance 之前是 Master 還是 Slave

```
     if gtid_binlog_pos.ServerID = Instance.ServerID and gtid_binlog_pos.DomainID = Instance.DomainID and gtid_binlog_pos.SequenceID > gtid_slave_pos.SequenceID (同 ServerID 與 DomainID 狀態下) then
       gtid_current_pos = gtid_binlog_pos
     else 
       gtid_current_pos = gtid_slave_pos (同 ServerID 與 DomainID 狀態下)
     end if
``` 

官方文件有說到一個例子，假設 A 為 Master，B 為 Slave，參數為 `slave_pos`，把 A 先關一會兒，讓 B 成為 Master，最後回復 A 並成為 B 的 Slave。

<img src = "https://raw.githubusercontent.com/sujunmin/sujunmin.github.com/master/test/MariaDB_Replication_1.png" />

* 一開始 A 的 `gtid_binlog_pos` 與 `gtid_current_pos` 相同（`1-1-123`, `1-1-137`, `1-1-145`），`gtid_slave_pos` 是空值。
* B 的 `gtid_slave_pos` 記著 A 的最後一個 GTID。
* 在 A 的 `1-1-145` 的時候把 A 先關掉，中間 B 的修改先忽略不看(假設沒發生)，現在資料庫停在 `1-1-145`。
* 在 A 恢復以後，設定 A 為 B 的 Slave，但是這個時候的 `gtid_slave_pos` 是空值，所以設成 `current_pos` (就是 `1-1-145`)。
* 到 B 的 `1-1-147` 的時候，最新的就是 `1-1-147`，B 就知道怎麼恢復了 (`145`-`147`)，正式加成 A 的 Slave。

這個的假設前提是在那個中斷時間內沒 B 沒有任何的 binlog 紀錄交易，這樣能確保 `1-1-145` 到 `1-1-147` 都只有在有 Slave 的狀態下 Master 產生的交易，假設 B 中間原來忽略的的 `2-2-138` 被產生了，那麼 binlog 的順序就會在 `1-1-145` 與 `1-1-147` 中間夾一個 `2-2-138`，奇怪的交易紀錄，會導致 A 的加入失敗。

所以官方建議在 Slave 上不要有任何的 binlog 紀錄 (Session 中 `@@sql_log_bin=0`)，避免當這個 Slave 有機會變成 Master 或是再 Slave 到其他地方時有交易紀錄異常的問題。

如果還是要修改 Slave 的資料造成 binlog 會有變動，還是要把參數設為 `slave_pos`，確保 Slave 會從 Master 看的 binlog 資料不會因為 Slave 的變動而異常，不能像 `current_pos` 那樣兩邊都有可能取值，不管是什麼角色。

在 B 當 Slave 的時候如果沒有開 binlog，那麼 `current_pos` 與 `slave_pos` 是同一件事，當然後續的切換是不會發生的 (B 沒 binlog 不能當 Master)。

## 最後的設計
剛剛最後有說到在 Slave 沒有開 binlog 的方式，我原來是想要用這個的，因為這蠻像現在的維運模式。
1. 當 Master 壞了就停掉 Slave 的 Service
2. 等到 Master 好的時候停機備份到 Master 上
3. 在 Slave 重新建 Replication

如果 Slave 有開 binlog，第2步的停機備份似乎可以把時間縮短
1. 當 Master 壞了就停掉 Slave 的 Service
2. 等到 Master 好的時候倒回 Slave 的備份
3. 同步完成後重新建立 Master / Slave 順序 

## 測試
### 在同一個機器上開新的 Instance
* 有一個不錯用的工具 [mysql_install_db.exe](https://mariadb.com/kb/en/mariadb/mysql_install_dbexe/) 可以直接開新的 Instance。
  
  我們用這個先開一個 Instance 在 Port 3307。

* 設定原來在 3306 的為 Master (`server-id=1`)，3307 的為 Slave (`server-id=2`)，都開啟 binlog (`log-bin`)。 
* Event 記得設成 Disabled on Slave。
* 接下來備份 Master
  
  ```cmd
  E:\backup>mysqldump -u sujunmin -p --master-data --all-databases --events --routines --gtid > all_db.sql
  Enter password: ************
  ```
  其中幾個重要參數

  | 參數           | 意義                                        |
  |---------------|---------------------------------------------|
  | `master-data` | dump 出來的掛 master ID                      |
  | `events`      | 包含 events                                 |
  | `routines`    | 包含 functions 與 store procedures           |
  | `gtid`        | 產生 `CHANGE MASTER TO master_use_gtid` 語法 |

  觀察一下 `all_db.sql` 的內容
  ```sql
  -- MySQL dump 10.16  Distrib 10.1.17-MariaDB, for Win64 (AMD64)
  (中間省略)
  --
  -- Position to start replication or point-in-time recovery from
  --

  -- CHANGE MASTER TO MASTER_LOG_FILE='WSTest-bin.000002', MASTER_LOG_POS=3732;

  --
  -- GTID to start replication from
  --

  CHANGE MASTER TO MASTER_USE_GTID=slave_pos;
  SET GLOBAL gtid_slave_pos='0-1-770';

  --
  -- Current Database: 'master'
  --
  (後面省略)
  ```
  可以看到如果要用舊的方式(兼容 MySQL Replication) 與新的方式。
* 啟動 Slave 的 Instance。  
* 把 `all_db.sql` 倒到 Slave (3307) 上面。
* 在 Master 上開一個 User `lsuser` 作為 Replication 用，權限是 `REPLICATION SLAVE` 與 `SUPER` (這個我不設定會沒權限登入，但是官方網站沒有這個權限)。
* 在 Slave 上設定 `CHANGE MASTER` 
  
  ```cmd
  MariaDB [(none)]> change master to master_host='localhost', master_port=3306, master_user='lsuser', master_password='password';
  Query OK, 0 rows affected (0.05 sec)
  ```

* `START SLAVE;`
* `SHOW SLAVE STATUS;`

  ```cmd
   MariaDB [(none)]> show slave status\G
   *************************** 1. row ***************************
               Slave_IO_State: Waiting for master to send event
                  Master_Host: localhost
                  Master_User: lsuser
                  Master_Port: 3306
                Connect_Retry: 60
              Master_Log_File: WSTest-bin.000002
          Read_Master_Log_Pos: 5429
               Relay_Log_File: WSTest-relay-bin.000002
                Relay_Log_Pos: 5757
        Relay_Master_Log_File: WSTest-bin.000002
             Slave_IO_Running: Yes
            Slave_SQL_Running: Yes
              Replicate_Do_DB:
                           (省略)
                   Using_Gtid: Slave_Pos
                  Gtid_IO_Pos: 0-1-775
      Replicate_Do_Domain_Ids:
    Replicate_Ignore_Domain_Ids:
                Parallel_Mode: conservative
    1 row in set (0.00 sec)
  ```

* 看一下同步狀況
* 移除 Master 模擬 Master 壞掉了狀態
  * `sc stop mariadb`
  * `sc delete mariadb`
  * 移除資料
* Slave 沒法連到 Master 了

  ```cmd
  MariaDB [(none)]> show slave status\G
  *************************** 1. row ***************************
               Slave_IO_State: Reconnecting after a failed master event read
                  Master_Host: localhost
                  Master_User: lsuser
                  Master_Port: 3306
                Connect_Retry: 60
              Master_Log_File: WSTest-bin.000002
          Read_Master_Log_Pos: 8223
               Relay_Log_File: WSTest-relay-bin.000002
                Relay_Log_Pos: 8551
        Relay_Master_Log_File: WSTest-bin.000002
             Slave_IO_Running: Connecting
            Slave_SQL_Running: Yes
                           (省略)
                Last_IO_Errno: 2003
                Last_IO_Error: error reconnecting to master 'lsuser@localhost:3306' - retry-time: 60  retries: 86400  message: Can't connect to MySQL server on'localhost' (10061 "Unknown error")
               Last_SQL_Errno: 0
               Last_SQL_Error:
   Replicate_Ignore_Server_Ids:
             Master_Server_Id: 1
               Master_SSL_Crl:
           Master_SSL_Crlpath:
                   Using_Gtid: Slave_Pos
                  Gtid_IO_Pos: 0-1-784
      Replicate_Do_Domain_Ids:
   Replicate_Ignore_Domain_Ids:
                Parallel_Mode: conservative
   1 row in set (0.00 sec)
  ```

* `STOP SLAVE`
* 打開 Slave 的 Event 服務
* 繼續服務
* 重建 Master
  
  ```cmd
  E:\backup>mysqldump -u sujunmin -p --port 3307 --master-data --all-databases --events --routines --gtid > all_db.sql
  Enter password: ************
  ```
  
  觀察一下 `all_db.sql` 的內容
  
  ```sql
  -- MySQL dump 10.16  Distrib 10.1.17-MariaDB, for Win64 (AMD64)
  (中間省略)
  --
  -- Position to start replication or point-in-time recovery from
  --

  -- CHANGE MASTER TO MASTER_LOG_FILE='WSTest-bin.000003', MASTER_LOG_POS=12441;

  --
  -- GTID to start replication from
  --

  CHANGE MASTER TO MASTER_USE_GTID=slave_pos;
  SET GLOBAL gtid_slave_pos='0-2-976';

  --
  -- Current Database: 'master'
  --
  (後面省略)
  ```

* 啟動 Master 的 Instance。  
* 把 `all_db.sql` 倒到 Master (3307) 上面。
* 在 Slave 上設定 `CHANGE MASTER` 為 `current_pos` 
  
  ```cmd
  MariaDB [(none)]> change master to master_host='localhost', master_port=3306, master_user='lsuser', master_password='password', master_use_gtid=current_pos;
  Query OK, 0 rows affected (0.05 sec)
  ```

* `START SLAVE;`
* `SHOW SLAVE STATUS;`
  
   ```cmd
   MariaDB [(none)]> show slave status\G
  *************************** 1. row ***************************
                             (省略)
                Last_IO_Errno: 1236
                Last_IO_Error: Got fatal error 1236 from master when reading data from binary log: 'Error: connecting slave requested to start from GTID 0-2-980, which is not in the master's binlog. Since the master's binlog contains GTIDs with higher sequence numbers, it probably means that the slave has diverged due to executing extra erroneous transactions'
               Last_SQL_Errno: 0
               Last_SQL_Error:
   Replicate_Ignore_Server_Ids:
             Master_Server_Id: 1
               Master_SSL_Crl:
           Master_SSL_Crlpath:
                   Using_Gtid: Current_Pos
                  Gtid_IO_Pos: 0-2-980
      Replicate_Do_Domain_Ids:
   Replicate_Ignore_Domain_Ids:
                 Parallel_Mode: conservative
  1 row in set (0.00 sec)
   ```

* `STOP SLAVE;`
* 在 Slave 上設定 `CHANGE MASTER` 為 `slave_pos`   
  
  ```cmd
  MariaDB [(none)]> change master to master_host='localhost', master_port=3306, master_user='lsuser', master_password='password', master_use_gtid=slave_pos;
  Query OK, 0 rows affected (0.05 sec)
  ```

* `START SLAVE;`
* `SHOW SLAVE STATUS;`
  
   ```cmd
   MariaDB [(none)]> show slave status\G
   *************************** 1. row ***************************
               Slave_IO_State: Waiting for master to send event
                  Master_Host: localhost
                  Master_User: lsuser
                  Master_Port: 3306
                Connect_Retry: 60
              Master_Log_File: WSTest-bin.000003
          Read_Master_Log_Pos: 7177
               Relay_Log_File: WSTest-relay-bin.000003
                Relay_Log_Pos: 7466
        Relay_Master_Log_File: WSTest-bin.000003
             Slave_IO_Running: Yes
            Slave_SQL_Running: Yes
                           (省略)
                   Using_Gtid: Slave_Pos
                  Gtid_IO_Pos: 0-1-1167
      Replicate_Do_Domain_Ids:
    Replicate_Ignore_Domain_Ids:
                  Parallel_Mode: conservative
   1 row in set (0.00 sec)
   ```

  * 這兩個對應 (0-2-976, 0-1-1167) 都幫你做好了
  * 完成

  ## 一些想法
  * 如果是 Multi-Master 的這個系統會很好做 (`CREATE MASTER to ..., master_use_gtid=slave_pos;`)，因為 `slave_pos` 可以是多個的，例如 `gtid_slave_pos='0-1-123,1-2-456,...'`
  * Slave 如果要快速完復，不需要備份還原的話，那麼就只能當 Snapshot 來用 (不能動他讓他產生自己的 binlog)
  * 對於原來的需求 (單一 Master 單一 Slave) 基本上設定一下就完成了
