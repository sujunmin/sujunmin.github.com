---
title: 從 SQL Server 到 MariaDB - [7] Linked Server 與 FederatedX Storage Engine
tags:
  - MariaDB
  - MSSQL
date: 2016-10-05 10:05:16
---

## 需求說明
資料庫有跨地區傳送資料的需求，在 SQL Server 用到 Linked Server 來傳送資料，希望在 MariaDB 也有類似的 Solution 可以使用。

原來的架構如下圖

<img src=https://raw.githubusercontent.com/sujunmin/sujunmin.github.com/master/test/ls_1.png />

## 採用 FederatedX Storage Engine 的原因
這種同步問題應該採用 Replication 來執行的，但是因為下面的原因而不使用
1. 在 Site B 會有一份 Site A `A_DB` 的副本，然後透過那個副本來傳送資料到 Site B 的 `B_DB`，架構如下

<img src=https://raw.githubusercontent.com/sujunmin/sujunmin.github.com/master/test/ls_2.png />

2. 這樣 Site B 有一份 `A_DB` 的副本，蠻浪費空間的，如果把讀寫頭指向 `B_DB`，雖然可以多個 GTID，但是感覺很混亂，而且出問題就很難修復。
3. Replication 不能設定同步時間 (Site A 與 Site B 中間的連線頻寬與其他服務 share，不能一直使用)，雖說可以透過網路管控讓他自己停止 Replication，但是覺得這樣不能完全控制，不太方便。
4. 在[這篇](http://stackoverflow.com/questions/5370970/how-to-create-linked-server-mysql)裡有網友解答可以透過 FEDERATED engine 達成 linked server 的需求，因此就來看看這是什麼。

## MariaDB FederatedX Storage Engine 介紹
後來去找 MariaDB 上的 [FEDERATED Storage Engine](https://mariadb.com/kb/en/mariadb/federated-storage-engine/) 時，發現到它已經不再開發了，取而代之的是 MariaDB 自己 fork 的 [FederatedX Storage Engine](https://mariadb.com/kb/en/mariadb/about-federatedx/)，相關比較如此[連結](https://mariadb.com/kb/en/mariadb/differences-between-federatedx-and-federated/)。

FederatedX Storage Engine 的說明文件是我覺得 MariaDB 文件裏面寫的最口語的，很容易了解。

這個 FederatedX Storage Engine 的產生是原來 Cisco 需要存取各設備的 MySQL 的資料，但是發動端沒有那麼多空間儲存這些資料，於是這個機制就產生了。

以下是針對 SQL Server Linked Server 與 MariaDB FederatedX Storage Engine 做一個比較

| 項目| SQL Server Linked Server | MariaDB FederatedX Storage Engine |
|----|--------------------------|-----------------------------------|
|實作層級| Database Level | Table Level|
|異質資料庫連結種類| 多種類 | MariaDB, MySQL, PostgreSQL (其他專案) |
|遠端資料庫的 DDL 異動處理| 沒有感覺 | 需重建 HANDLER |
|執行遠端資料庫的 DDL | 可以 (`exec('ddl')`) | 不可以 |

## 測試
### 建立  HANDLER
首先開一個 Site A，同一台機器的 3308 Port。

上面開一個 `A_DB`，裡頭一個 `table`，一個欄位 `col` `char(50)`，一行 `'A'`。

在原來的 MariaDB 假設他是 Site B，開一個 DB `B_DB` 裡頭一個 `table`，一個欄位 `col` `char(50)`，一行 `'B'`。

在 Site B 上執行以下指令

```sql
use B_DB;

create server 'Site_A' foreign data wrapper 'mysql' options
  (HOST '127.0.0.1',
  DATABASE 'A_DB',
  USER 'root',
  PASSWORD 'passowrd',
  PORT 3308,
  SOCKET '',
  OWNER 'root');

CREATE TABLE A_table ENGINE=FEDERATED CONNECTION='Site_A/table';

```

接著可以 `select * from A_table` 看看，應該會看到 A。

這邊還有一些要注意的
1. 在 `CREATE TABLE` 的時候可以故意打錯 table 名字，會報錯 (與原來 FEDERATED 的差異第 2 項)。
2. 在 `CREATE TABLE ... CONNECTION='Site_A/table'` 的時候也可以 `mysql://username:password@hostname:port/database/tablename`，但是我的密碼有特殊字@，所以還好有 `create server` 這個方法建立。
3. 建立完成後可以看看資料庫的 `B_DB` 資料夾，只會有 `a_table.frm`，不會有 `a_table.ibd`，因為資料實際不在 Site B。
4. Replication Slave 不會有這樣方法產生出來的 Table，如果要切換記得要手動加回去。

### 遠端資料庫執行 DDL
在 Site A 的 `A_DB` 把 table 多一個欄位 `col1` `int`。

到 Site B `select * from A_table` 看看。

你會只看到一個欄位 `col`，結果是 A。

這時候需要重建 HANDLER

```sql
use B_DB;
drop table A_table;
CREATE TABLE A_table ENGINE=FEDERATED CONNECTION='Site_A/table';
```
重新 Select 看看，應該會看到正確的資料。

### 在 FEDERATED Table 上執行 DDL
在 Site B 的 `B_DB` 對 `A_table` 新增一個欄位
```sql
ALTER TABLE `A_table` ADD COLUMN `col3` INT NULL AFTER `col2`;
```
會有以下的錯誤
```cmd
SQL錯誤（1031）：Storage engine FEDERATED of the table b_db.a_table doesn't have this option
```

如果透過 `execute` 呢?

```sql
use B_DB;
set @sql := 'ALTER TABLE A_table ADD COLUMN col3 INT NULL AFTER col2';
prepare stmt from @sql;
execute stmt;
```
還是跟上面一樣的錯(不能偷渡就是了)。

## 一些想法
這個 FEDERATED Table 有點像 .Net 開發時拉 Web Reference 會產生出 wsdl 檔在本機，然後透過他的定義去使用 Web Services，只是開發時有一些方法可以 auto update wsdl，以確保介面進出的正確性，這裡的 FEDERATED Table 沒有更新的功能。

不過如果資料表都沒什麼會動的話其實沒什麼差異，這樣就很好用了。

然後因為是 Table Level 的關係，Linked Server 只要做一個 Database Link 即可，但是這個就要每個 Table 都要建一次。

最後的架構

<img src=https://raw.githubusercontent.com/sujunmin/sujunmin.github.com/master/test/ls_3.png />

