---
title: 從 SQL Server 到 MariaDB - [1] 備份與還原
tags:
  - MariaDB
  - MSSQL
date: 2016-08-08 14:47:20
---

## 需求說明
從 SQL Server 要轉到 MariaDB 第一件碰到管理上的轉變，就是備份與還原。

在 SQL Server 管理備份這回事，有幾個方式達成最少資料遺失的方式：
 1. Full Backups 完整備份
 2. Differencial Backups 差異備份
 3. Transation Data Backups 交易紀錄備份
 
基本上這三個配合運用可達到幾乎 0 資料遺失的結果，這個在網路上應該有一大堆的說明，沒什麼太大的問題。

但是到了 MariaDB 這邊該如何處理呢?

在 MariaDB 這邊提供與 MySQL 一樣的[方式][1]，mysqldump，當然還有其他的方式，但是最熟悉的仍然是簡單的 mysqldump。

然後...就這樣。

就這樣?

是的，基本上 MariaDB 的備份還原只有 mysqldump 那樣的 Full Backups，沒有差異備份那些方式。

那要怎麼完成最少資料遺失呢? 總不能一直 Full Backup 吧?

## Binary Log

MariaDB 提供一種與 MySQL 一樣的方式，稱之為 [Binary Log][2] (binlog)。

這個 binlog 是[什麼][3]呢?

簡單來說，就是紀錄著資料庫變動的紀錄，以 binary 方式儲存，有點像 Oracle DB 的 redo log，當有需要叫回備份時，首先要還原最近一次的 Full Backup，然後接著依序[恢復][4]該時間之後的 binlogs，這樣就能達到幾乎 0 資料遺失的還原。

<img src=https://sujunmin.github.io/test/mariadb_backup_and_restore.png />

如果是上圖的樣子，要還原的時候就是：
 1. Full Backup
 2. binlog_n (從 time_A 開始)
 3. binlog_n+1
 4. binlog_n+2
 5. binlog_n+3 (到 time_B 結束)
 
接下來就是來做個實驗。

## 實驗

a. 首先開啟 binlog 功能，在 my.ini 裡可以設定，重開 mariadb 就可以開始了。

``` 
log_bin
```
這個是沒加參數的，預設會放在 `%mariadb_root%\data\`，詳細可以加[路徑][5]。

開完裡頭應該會出現 `XXX-bin.00001` 字樣的檔案， `XXX` 是 Hostname。

b. 準備一個 database `test1`，裡頭一個資料表 `test1` ，欄位 `aaaa` 為 `int` 。

先做一次 full backup。

```msdos
E:\backup>mysqldump -u sujunmin -p test1 > test1.sql
Enter password: ************

E:\backup>
```

c. 新增一筆資料 `1234`。

d. `trucnate test1.test1;`

e. 來看看 binlog 裡頭有什麼東西。
```msdos
E:\backup>mysqlbinlog "e:\MariaDB 10.1\data\WSTest-bin.000001" -v > test.sql

E:\backup>
```

f. test.sql 內容
```sql
/*!50530 SET @@SESSION.PSEUDO_SLAVE_MODE=1*/;
/*!40019 SET @@session.max_insert_delayed_threads=0*/;
/*!50003 SET @OLD_COMPLETION_TYPE=@@COMPLETION_TYPE,COMPLETION_TYPE=0*/;
DELIMITER /*!*/;
# at 4
#160808 14:25:16 server id 1  end_log_pos 249 	Start: binlog v 4, server v 10.1.14-MariaDB created 160808 14:25:16 at startup
# Warning: this binlog is either in use or was not closed properly.
ROLLBACK/*!*/;
BINLOG '
zCWoVw8BAAAA9QAAAPkAAAABAAQAMTAuMS4xNC1NYXJpYURCAGxvZwAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAADMJahXEzgNAAgAEgAEBAQEEgAA3QAEGggAAAAICAgCAAAACgoKAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAEEwQAAK+oLro=
'/*!*/;
# at 249
#160808 14:25:16 server id 1  end_log_pos 274 	Gtid list []
# at 274
#160808 14:25:16 server id 1  end_log_pos 314 	Binlog checkpoint WSTest-bin.000001
# at 314
#160808 14:28:32 server id 1  end_log_pos 352 	GTID 0-1-1 trans
/*!100101 SET @@session.skip_parallel_replication=0*//*!*/;
/*!100001 SET @@session.gtid_domain_id=0*//*!*/;
/*!100001 SET @@session.server_id=1*//*!*/;
/*!100001 SET @@session.gtid_seq_no=1*//*!*/;
BEGIN
/*!*/;
# at 352
#160808 14:28:32 server id 1  end_log_pos 466 	Query	thread_id=3	exec_time=0	error_code=0
use `test1`/*!*/;
SET TIMESTAMP=1470637712/*!*/;
SET @@session.pseudo_thread_id=3/*!*/;
SET @@session.foreign_key_checks=1, @@session.sql_auto_is_null=0, @@session.unique_checks=1, @@session.autocommit=1/*!*/;
SET @@session.sql_mode=1075838976/*!*/;
SET @@session.auto_increment_increment=1, @@session.auto_increment_offset=1/*!*/;
/*!\C utf8mb4 *//*!*/;
SET @@session.character_set_client=45,@@session.collation_connection=45,@@session.collation_server=33/*!*/;
SET @@session.lc_time_names=0/*!*/;
SET @@session.collation_database=DEFAULT/*!*/;
INSERT INTO `test1`.`test1` (`aaaa`) VALUES (1234)
/*!*/;
# at 466
#160808 14:28:32 server id 1  end_log_pos 493 	Xid = 55
COMMIT/*!*/;
# at 493
#160808 14:31:19 server id 1  end_log_pos 531 	GTID 0-1-2 ddl
/*!100001 SET @@session.gtid_seq_no=2*//*!*/;
# at 531
#160808 14:31:19 server id 1  end_log_pos 611 	Query	thread_id=3	exec_time=14	error_code=0
SET TIMESTAMP=1470637879/*!*/;
TRUNCATE `test1`
/*!*/;
DELIMITER ;
# End of log file
ROLLBACK /* added by mysqlbinlog */;
/*!50003 SET COMPLETION_TYPE=@OLD_COMPLETION_TYPE*/;
/*!50530 SET @@SESSION.PSEUDO_SLAVE_MODE=0*/;
```

其中我們可以看到重要的兩筆資料，它們在整個時間序列的狀態為何。

```sql
INSERT INTO `test1`.`test1` (`aaaa`) VALUES (1234)
/*!*/;
# at 466
#160808 14:28:32 server id 1  end_log_pos 493 	Xid = 55
COMMIT/*!*/;
# at 493
#160808 14:31:19 server id 1  end_log_pos 531 	GTID 0-1-2 ddl
/*!100001 SET @@session.gtid_seq_no=2*//*!*/;
# at 531
#160808 14:31:19 server id 1  end_log_pos 611 	Query	thread_id=3	exec_time=14	error_code=0
SET TIMESTAMP=1470637879/*!*/;
TRUNCATE `test1`
/*!*/;
```

g. 所以如果我們要恢復到 truncate table 之前的話，首先就是 restore full backup。

```msdos
E:\backup>mysql -u sujunmin -p test1 < test1.sql
Enter password: ************

E:\backup
```

h. 接著還原 binlog 到 14:31:19 之前(因為接下來就要 truncate table 了)。
```msdos
E:\backup>mysqlbinlog "e:\MariaDB 10.1\data\WSTest-bin.000001" --stop-datetime="2016/08/08 14:31:00" | mysql -u sujunmin -p test1
Enter password: ************

E:\backup>
```

i. 看一下 `test1.test1`，資料應該又回來了。

## 運用規劃

因為這個 binlog 會在系統的狀態改變(重新啟動，記憶體資料寫入 disk...)時產生，加上沒有其他的備份機制(類似像 Oracle DB 的 Archive 模式)，其實當有事情時會不能知道能夠還原到哪。

所以要透過強制 rotate 與備份資料來幫忙，以下是規劃的方式。

1. 當定時的時間一到，呼叫 [mysqlbinlogrotate][6] 切斷 log。
2. 之前的 log 複製到安全的地方處理。
3. 每次 Full Backup 完 [PURGE LOG][7]。

##　更新

### 2016/08/12
感謝[Sam Wong][8]提供資訊

>sync_binlog = 1 (<5.7.7 默認竟是 0，不是最保守的設定)

>innodb_flush_log_at_trx_commit = 1 (默認就是 1)

相關資料在[此][9]。　

[1]: https://mariadb.com/kb/en/mariadb/backup-and-restore-overview/
[2]: https://mariadb.com/kb/en/mariadb/binary-log/
[3]: https://mariadb.com/kb/en/mariadb/overview-of-the-binary-log/
[4]: http://dev.mysql.com/doc/refman/5.7/en/point-in-time-recovery.html
[5]: https://mariadb.com/kb/en/mariadb/replication-and-binary-log-server-system-variables/#log_bin
[6]: https://dev.mysql.com/doc/mysql-utilities/1.6/en/mysqlbinlogrotate.html
[7]: https://mariadb.com/kb/en/mariadb/sql-commands-purge-logs/
[8]: https://www.facebook.com/sam0737
[9]: https://mariadb.com/kb/en/mariadb/binlog-group-commit-and-innodb_flush_log_at_trx_commit/
