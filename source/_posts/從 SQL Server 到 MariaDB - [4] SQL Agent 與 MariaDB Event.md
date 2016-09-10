---
title: 從 SQL Server 到 MariaDB - [4] SQL Agent 與 MariaDB Event
tags:
  - MariaDB
  - MSSQL
date: 2016-09-10 14:11:42
---

## 需求說明
回到維運面的處理，在 SQL Server 有 SQL Server Agent Service 作為 Job System，說實在真的好用，在 msdb 裡儲存了很多 Job 的資料，不管是統計還是錯誤時的資料收集都做的非常好，因此系統每天會出資料庫系統狀態報表，其中有兩個項目有很多東西能看

<img src="https://raw.githubusercontent.com/sujunmin/sujunmin.github.com/master/test/SQLServerAgentLog_1.png" />
<img src="https://raw.githubusercontent.com/sujunmin/sujunmin.github.com/master/test/SQLServerAgentLog_2.png" /> 

因此每天的檢查大部份都是看這些 Log 的狀態，希望在 MariaDB 上面希望也有類似的方法實作。

## Job System 的選擇
在 MariaDB 上有 `[Event]`(https://mariadb.com/kb/en/mariadb/events/) 可以用，在 Windows 裡有 Windows Tasks 可以用，但他們跟 SQL Server Agent Service 真的是差太多了，這兩個服務 MariaDB 的 `Event` 幾乎是射後不理，Windows 的 Tasks 也只有少少的 Log 可以用，基本上直接用是沒法達到原來維運的要求的，最後仍然是選擇 MariaDB 的 `Event` 配合一點紀錄來實作 Job System。

## MariaDB 的 `Event`
詳細資料可以參考[這個](https://mariadb.com/kb/en/mariadb/events/)，基本上很簡單，就是設定某個 Schema 的什麼時候做什麼事情，用 `Show events;` 可以看到 `Event` 的內容，不過這邊要來說明的是 `mysql.event` 這個[資料表](https://mariadb.com/kb/en/mariadb/mysqlevent-table/)，因為所有的 `Event` 資料都是從這個地方加工的。

其中有一些時間戳記，經過一些實驗結果 (因為真的寫的好少，不太清楚真正的意思) 有一些認識

|  欄位名              | 意義                                              | 
|---------------------|--------------------------------------------------|
| `created`       | `Event` 產生的時間                             |
| `modified`      | `Event` 被修改的時間                           |
| `last_executed` | 上次執行結束時間 (不論成功或失敗)                     |
| `starts`        | `Event` 開始時間 (第一次開始執行的時間)           |
| `ends`          | `Event` 結束時間 (如果沒有結束日期就是 Null)      |

要注意的是 `last_executed` 是不管成功或是失敗執行完的時間，`starts` 是第一次執行的時間，如果他是週期性執行，沒有結束的時間，只會記住第一次的

這個時間系統很重要的是因為它會關係到後續報表怎麼出，所以要先了解一下

另外的是處理情形的資訊，在 `mysql.event` 沒有任何紀錄最後一次狀態的資訊 (所以才說它幾乎是射後不理的系統)，這邊就需要自己紀錄了。 

## 紀錄 `Event` 執行狀態的 table
根據需求，我在 `master` Create 了 `event_history` 資料表

```sql
CREATE TABLE event_history (
    db CHAR(64) NOT NULL DEFAULT '',
    name CHAR(64) NOT NULL DEFAULT '',
    start DATETIME NULL DEFAULT NULL,
    end DATETIME NULL DEFAULT NULL,
    sqlstate CHAR(64) NULL DEFAULT NULL,
    errno CHAR(64) NULL DEFAULT NULL,
    message_text VARCHAR(500) NULL DEFAULT NULL,
    record_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx1 (db, name)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB
;
```  
|  欄位名              | 意義                                              | 
|---------------------|--------------------------------------------------|
| `db`            | `mysql.event.db`                             |
| `name`          | `mysql.event.name`                           |
| `start`         | 這次執行開始時間                                    |
| `end`           | 這次執行結束時間                                    |
| `sqlstate`      | 錯誤時紀錄 `sqlstate`                          |
| `errno`         | 錯誤代碼                                           |
| `message_text`  | 訊息                                              |
| `record_time`   | 紀錄時間                                           |

### 攔截錯誤時的訊息
MariaDB 有個 [Handler](https://mariadb.com/kb/en/mariadb/declare-handler/) 作為錯誤處理 try-catch 的機制，可以從這邊下手。

[Diagnostics](https://mariadb.com/kb/en/mariadb/get-diagnostics/) 可以在錯誤時拿到相關的資訊，有 `sqlstate` ，`errno` ，`message_text` 可以拿來用。

### Prepare Statements
由上面的準備，幾乎是可以完成表裡需要的欄位項目，但是如果要每個 `Event`都要前面加 `Handler` 與 `Diagnostics` 來塞資料，這樣太麻煩也有可能會忘記，所以要做個 Store Procedure 來達成這個需求。

其中需要讓 Store Procedure 執行傳進來的 SQL Statements，有 [Prepare Statement](https://mariadb.com/kb/en/mariadb/prepare-statement/) 可以用，這樣就差不多了

不過這個 Prepare Statement 有限制使用的語法，而且只能執行一行。

```sql
CREATE PROCEDURE proc_for_event_history(IN idb char(64), IN iname char(64), IN sql TEXT)
    LANGUAGE SQL
    NOT DETERMINISTIC
    CONTAINS SQL
    SQL SECURITY DEFINER
    COMMENT ''
BEGIN
      DECLARE EXIT HANDLER FOR SQLEXCEPTION
      BEGIN
        GET DIAGNOSTICS CONDITION 1 @sqlstate = RETURNED_SQLSTATE,  @errno = MYSQL_ERRNO, @text = MESSAGE_TEXT;
        select now() into @start from dual;
        insert into master.event_history (db, name, start, end, sqlstate, errno, message_text) values (idb, iname, @start, now(), @sqlstate, @errno, @text);
      END;
     
     set @sql := sql;
     prepare stmt from @sql;
    
     select now() into @start from dual;
     execute stmt;
     select sleep(10);
     insert into master.event_history (db, name, start, end, sqlstate, errno, message_text) values (idb, iname, @start, now(), NULL, NULL, 'OK');

     DEALLOCATE PREPARE stmt;
END
```

錯誤的時候有錯誤訊息，但正確的時候不知道那邊可以找到訊息，只好先打個 OK 了。

有一個 `select sleep(10);` 是拿來做實驗用的，平時可以拿掉。

## 實驗

1. `Event 系統要打開

   `SET Global event_schedular=1`
   
   或是在 `my.cnf` 加上 `events_scheduler=1`
2. 一個簡單 table，然後做一個 `Event` 是一直加一的
   ```sql
   CREATE Database test1;

   CREATE TABLE test2 (
    idtest2 int(11) NOT NULL,
    PRIMARY KEY (idtest2)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

   CREATE EVENT test_event
    ON SCHEDULE
        EVERY 1 MINUTE STARTS '2016-09-07 16:00:58'
    ON COMPLETION NOT PRESERVE
    ENABLE
    COMMENT ''
    DO begin
     call master.proc_for_event_history('test1', 'test_event', 'UPDATE test1.test22 SET idtest2 = idtest2 + 1');
   end;

   ```
   這裡的 `test22` 是故意打錯的，看看結果如何
3. 看一下結果
```cmd
MariaDB [master]> select * from event_history order by record_time desc limit 1\G
*************************** 1. row ***************************
          db: test1
        name: test_event
       start: 2016-09-10 15:57:58
         end: 2016-09-10 15:57:58
    sqlstate: 42S02
       errno: 1146
message_text: Table 'test1.test22' doesn't exist
 record_time: 2016-09-10 15:57:58
1 row in set (0.00 sec)
```
4. 調回來正確的，再看一下結果
```cmd
MariaDB [master]> select * from event_history order by record_time desc limit 1\G
*************************** 1. row ***************************
          db: test1
        name: test_event
       start: 2016-09-10 16:00:58
         end: 2016-09-10 16:01:08
    sqlstate: NULL
       errno: NULL
message_text: OK
 record_time: 2016-09-10 16:01:08
1 row in set (0.00 sec)
```
開始跟結束差 10 秒鐘是之前加的 `select sleep(10)` 的關係。

## 還需要解決的項目

1. Query 正確時的回傳要能抓到
2. 該次真正開始執行時間
3. 下一次開始執行時間

