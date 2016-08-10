---
title: 從 SQL Server 到 MariaDB - [2] 資料庫 ACL 與帳號管理
tags:
  - MariaDB
  - MSSQL
date: 2016-08-10 15:48:38
---

## 需求說明
接下來要對付的就是資料庫 ACL 與帳號管理如何在 MariaDB 上面實現。

在 AP 與 SQL Server 之間的連線，除了有防火牆控管以外，還設了一道[資料庫 ACL][1] 來確保是正確的帳號從正確的 ip 連線進正確的資料庫。

在 MariaDB 上原來是可以透過內建的功能完成這個需求，不過最後還是放棄改用其他方法。

為什麼會這樣說呢?

首先來了解一下 MariaDB 的帳號管理方式，透過 `CREATE USER` 來[了解][2]。
 1. Account Name 是 `username@host` 的[組合][3]。
 2. 所以 `test@a` 與 `test@b` 是不同帳號。

這樣對於管理會有什麼差別呢?
 1. 在 SQL Server 裡一個人就一個帳號 `username`，但是在 mariaDB 是有多個組合但是是同一個帳號概念。
 2. 每一個帳號有自己的權限，如果該帳號有 N 個可以 access 的 ip，那就要設 N 次。
 3. 同理，如果使用者要修改密碼，不管是找一個改完後讓 DBA 複製到其他相同帳號的方式，或是使用者一個一個改，這樣與原來 SQL Server 的帳號管理方式都會差距太大。
 4. `host` 的表示有 wildcard 符號可用，但是這樣無法特別指定部份 ip，不能符合需求。
 
綜合以上的問題，無法使用系統內建的方式處理資料庫 ACL，只能透過 logon trigger 方式來解決。

來看一下 MariaDB 那邊可以加 logon trigger。

我看到這篇[文章][4]，提到如何加 logon/logoff trigger for audit，十分有幫助。

以下就是轉換的過程。

## loginmain 與 logindate 的轉換

首先 create `master` 資料庫 (向 SQL Server 致敬 XD)，裡頭兩個資料表，參照原來的方式產生。

### `master.loginmain` 部份
```sql
CREATE TABLE `loginmain` (
`idx` char(36) NOT NULL DEFAULT 'none',
`username` longtext NOT NULL,  
`srcip` longtext NOT NULL,  
`apname` longtext,  
`crdate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
`upddate` datetime DEFAULT NULL,  
PRIMARY KEY (`idx`)
);
```

因為 MariaDB 的 table 預設值不能是 function，所以要透過 trigger 加以實踐預設值。

要設兩個 trigger 來達到以下的需求
 1. idx 為自動編號的 guid/uuid。
 
   ```sql
   DELIMITER //
   CREATE TRIGGER create_uuid_for_loginmain 
   BEFORE INSERT ON loginmain
   FOR EACH ROW
   BEGIN    
   IF NEW.idx = 'none' THEN        
   SET NEW.idx = UUID();    
   END IF;
   END;
   //
   ```
 2. 當變更 (update) 時自動更新 upddate。
 
   ```sql
   DELIMITER //
   CREATE TRIGGER update_upddate_for_loginmain
   BEFORE UPDATE ON loginmain
   FOR EACH ROW 
   BEGIN    
   SET NEW.upddate = now();
   END;
   //
   ```
   
   這裡要注意的是為什麼不 `AFTER UPDATE` 而是 `BEFORE UPDATE` 呢?
   原因在於 `AFTER UPDATE` 的 `NEW` 與 `OLD` 新舊值是[不能][5]變動的，但是 `BEFORE UPDATE` 是[可以][6]改變 `NEW` 值(因為還沒真正更新) 
   這樣就可以在更新時更新 `upddate` 欄位了。

### `master.logindate` 部份
```sql
CREATE TABLE `logindate` (
`idx` char(36) NOT NULL DEFAULT 'none',
`lm_idx` char(36) NOT NULL,  
`wkdnum` int(11) NOT NULL,
`dfrom` time NOT NULL DEFAULT '00:00:00',
`dto` time NOT NULL DEFAULT '23:59:59',
`crtdate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
`upddate` datetime DEFAULT NULL,  
PRIMARY KEY (`idx`)
);

DELIMITER //
CREATE TRIGGER create_uuid_for_logindate
BEFORE INSERT ON logindate
FOR EACH ROW
BEGIN
    IF NEW.idx = 'none' THEN
        SET NEW.idx = UUID();
    END IF;
END;

CREATE TRIGGER update_upddate_for_logindate
BEFORE UPDATE  ON logindate
FOR EACH ROW 
BEGIN
    SET NEW.upddate = now();
END;
//
```

## login trigger 部份

經過尋找 MariaDB 的 manual，轉換如下。

```sql 
DELIMITER //
CREATE PROCEDURE check_login()
SQL SECURITY DEFINER
BEGIN

SET @idx = '';
SET @idx2 = '';

select idx into @idx from master.loginmain 
     where username = SUBSTRING_INDEX(SUBSTRING_INDEX(user(), '@', 1), '@', -1)  and  
		 srcip like CONCAT('%', SUBSTRING_INDEX(SUBSTRING_INDEX(user(), '@', 2), '@', -1), '%') ;
   
if @idx = '' then
	SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30001, MESSAGE_TEXT='無法在資料庫中找到您的登入帳號、來源IP、或是使用之程式不正確。';
else
    select idx into @idx2 from master.logindate
	where lm_idx = @idx and 
          wkdnum = dayofweek(now())-1 and
          (cast(now() as time) between dfrom and dto);
    if @idx2 = '' then
		SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30002, MESSAGE_TEXT='您的帳號無法在此時段登入。';
    end if;
end if;
END;
//
```
* `select idx into @idx from master.loginmain where username = SUBSTRING_INDEX(SUBSTRING_INDEX(user(), '@', 1), '@', -1) and srcip like CONCAT('%', SUBSTRING_INDEX(SUBSTRING_INDEX(user(), '@', 2), '@', -1), '%') ;` 
   這段是把 username@host 拆開來分別比對。
* 可以看到無法對應用程式名稱 (`apname`) 來比較，因為沒辦法拿到資料。不過原來的需求這項本來就是非必要的，加上微軟也提到[不要][9]把這個參數拿來做 security check，就不在這次轉換的需求中了。
* `SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30001, MESSAGE_TEXT='無法在資料庫中找到您的登入帳號、來源IP、或是使用之程式不正確。';`
	 這段就是如果找不到就[拋錯][7]。
	 
塞點資料進去測試。

<img src = "https://raw.githubusercontent.com/sujunmin/sujunmin.github.com/master/test/acl_1.png" />
<img src = "https://raw.githubusercontent.com/sujunmin/sujunmin.github.com/master/test/acl_2.png" />


最後是修改 logon trigger 發動的設定。在[之前][4]找到的文章裡有說到可以在 `init_connect` [做][8] logon trigger，所以到 `my.ini` 加一行

`init_connect="CALL master.check_login();"`

要給帳號有執行這個 store procedure 的權限 (以後新增帳號完都要執行這個)。
```sql
grant execute on procedure master.check_login to 'test'@'%';
grant execute on procedure master.check_login to 'sujunmin'@'%';
```

重開 MariaDB。

測試一下。
```msdos
C:\Users\Administrator>date
現在日期是: 2016/08/10
輸入新日期: (yy-mm-dd)

C:\Users\Administrator>time
現在時間是: 17:44:00.55
輸入新時間:

C:\Users\Administrator>mysql -u sujunmin -p
Enter password: ************
Welcome to the MariaDB monitor.  Commands end with ; or \g.
Your MariaDB connection id is 3
Server version: 10.1.14-MariaDB mariadb.org binary distribution

Copyright (c) 2000, 2016, Oracle, MariaDB Corporation Ab and others.

Type 'help;' or '\h' for help. Type '\c' to clear the current input statement.

MariaDB [(none)]> select true from dual;
+------+
| TRUE |
+------+
|    1 |
+------+
1 row in set (0.00 sec)

MariaDB [(none)]> quit
Bye

C:\Users\Administrator>mysql -u test -p
Enter password: ************
Welcome to the MariaDB monitor.  Commands end with ; or \g.
Your MariaDB connection id is 5
Server version: 10.1.14-MariaDB

Copyright (c) 2000, 2016, Oracle, MariaDB Corporation Ab and others.

Type 'help;' or '\h' for help. Type '\c' to clear the current input statement.

MariaDB [(none)]> select true from dual;
ERROR 2006 (HY000): MySQL server has gone away
No connection. Trying to reconnect...
Connection id:    6
Current database: *** NONE ***

ERROR 1184 (08S01): Aborted connection 6 to db: 'unconnected' user: 'test' host
 'localhost' (init_connect command failed)
MariaDB [(none)]> quit
Bye
```

調整一下登入時間設定。
<img src = "https://raw.githubusercontent.com/sujunmin/sujunmin.github.com/master/test/acl_3.png" />

再測試一下。
```msdos
C:\Users\Administrator>mysql -u test -p
Enter password: ************
Welcome to the MariaDB monitor.  Commands end with ; or \g.
Your MariaDB connection id is 7
Server version: 10.1.14-MariaDB

Copyright (c) 2000, 2016, Oracle, MariaDB Corporation Ab and others.

Type 'help;' or '\h' for help. Type '\c' to clear the current input statement.

MariaDB [(none)]> select true from dual;
ERROR 2006 (HY000): MySQL server has gone away
No connection. Trying to reconnect...
Connection id:    8
Current database: *** NONE ***

ERROR 1184 (08S01): Aborted connection 8 to db: 'unconnected' user: 'test' host
 'localhost' (init_connect command failed)
MariaDB [(none)]> quit
Bye
```
當然還是不行，因為 host 是錯的，調整一下。
<img src = "https://raw.githubusercontent.com/sujunmin/sujunmin.github.com/master/test/acl_4.png" />

再測試看看。
```msdos
C:\Users\Administrator>mysql -u test -p
Enter password: ************
Welcome to the MariaDB monitor.  Commands end with ; or \g.
Your MariaDB connection id is 9
Server version: 10.1.14-MariaDB mariadb.org binary distribution

Copyright (c) 2000, 2016, Oracle, MariaDB Corporation Ab and others.

Type 'help;' or '\h' for help. Type '\c' to clear the current input statement.

MariaDB [(none)]> select true from dual;
+------+
| TRUE |
+------+
|    1 |
+------+
1 row in set (0.00 sec)

MariaDB [(none)]> quit
Bye

C:\Users\Administrator>
```
可以登入了。



[1]: https://sujunmin.github.io/blog/2013/07/08/%E6%8E%A7%E5%88%B6%E8%B3%87%E6%96%99%E5%BA%AB%E4%BD%BF%E7%94%A8%E8%80%85%E7%9A%84%E7%99%BB%E5%85%A5/
[2]: https://mariadb.com/kb/en/mariadb/create-user/
[3]: https://mariadb.com/kb/en/mariadb/create-user/#account-names
[4]: http://www.fromdual.com/mysql-logon-and-logoff-trigger-for-auditing
[5]: http://www.techonthenet.com/mariadb/triggers/after_update.php
[6]: http://www.techonthenet.com/mariadb/triggers/before_update.php
[7]: https://mariadb.com/kb/en/mariadb/signal/
[8]: https://mariadb.com/kb/en/mariadb/server-system-variables/#init_connect
[9]: https://msdn.microsoft.com/en-us/library/ms189770.aspx
