---
title: 從 SQL Server 到 MariaDB - [3] 資料庫移轉與系統程式修改
tags:
  - MariaDB
  - MSSQL
date: 2016-09-06 09:39:47
---

## 需求說明
在資料庫移轉的部份，原來的資料庫有廠商使用的部份，然後也有我們自己維護的系統的部份。廠商的部份使用 [MySQL Workbench](https://www.mysql.com/products/workbench/) 的 Database Migrate 功能，花了一點時間轉置，基本上沒有太大的問題，系統程式都能正常運行，因此我想我們這邊的程式應該也可以如法泡製吧。

結果花了一個星期的時間才完成進入測試，相關的過程紀錄如下。

系統架構
1. Classic ASP，程式跟 Html 寫在一起
2. Windows 2008 R2 64 bits 
3. IIS 7.5
4. [MariaDB ODBC 2.0.11](https://downloads.mariadb.org/connector-odbc/)
5. SQL Server 2008 R2
6. 使用 SQL Server 基本欄位
7. 加密資料使用對稱式金鑰加密
8. 登入系統密碼用 MD5 Hash

基本需求
1. 程式的流程與架構不要被修改
2. 著重於語法與使用方式的轉換
3. 最快方法處理完畢

## 資料庫移轉
如同前面所說，原來是想用 [MySQL Workbench](https://www.mysql.com/products/workbench/) 的 Database Migrate 功能轉換即可，結果碰到釘子，原因如下。
1. 資料庫裡的資料有 Html Codes (上傳活動網頁使用)，有換行符號，匯入工具的換行符號辨識只有幾種 (CR, LF)，會被截斷
2. 時間欄位問題 (不過其實沒什麼問題，這邊走了一點冤枉路，研究了一下 SQL Server 怎麼存時間的)
3. 字碼問題
4. 加密欄位問題(要先解密才匯出來，不然到 MariaDB 也解不開)

所以後來是自己寫 Script 處理
1. 使用 bcp 匯出資料，用 tab 分隔資料欄位，用 | 分隔資料行
```cmd
bcp "select * from table" queryout "query.csv" -c -t"\t" -r"|" -S Server -U username -P password
...
bcp "open symmetric key ...; select cast(descryptby key(key, ...) as nvarchar) ... from table2" queryout "table2.csv" -c -t"\t" -r"|" -S Server -U username -P password 
...
```
2. 因為 bcp 不支援 Unicode 輸出 (SQL Server does not support code page 65001 (UTF-8 encoding).)，只支援雙字元輸出 (-w)，但那看起來會十分奇怪，所以要轉檔成為 UTF-8

3. 使用 load data local infile 來匯入資料
```sql
truncate table tab_name;
...
load data local infile 'query.csv' into table tab_name
fields terminated by '\t'
lines terminated by '|'
...
```
4. 細部調整一些資料

## 語法轉換

| T-SQL                                                                                               | MariaDB SQL                                                   | 
|-----------------------------------------------------------------------------------------------------|---------------------------------------------------------------| 
| getdate()                                                                                           | now()                                                         | 
| dateadd()                                                                                           | adddate()                                                     |
| top N ...                                                                                           | ... limit N                                                   |
| HashBytes('md5', ...)                                                                                 | MD5(...)                                                      |
| open symmetric key ... by password=passprase ...<br>encryptbykey(key, ...)<br>decryptbykey(key, ...)| aes_encrypt(passphrase, ...)<br> aes_decrypt(passphrase, ...) |

### limit 的問題
基本上不會有什麼問題，但是在程式裡有類似這樣的動作
```sql
select ... from table where idx not in (select idx from table2 where ... limit 10)
```
會有如下的錯誤
```cmd
This version of MariaDB doesn't yet support 'LIMIT & IN/ALL/ANY/SOME subquery'
```
不過有神人[幫忙](http://stackoverflow.com/questions/7124418/mysql-subquery-limit)，這個是有解的
```sql
select ... from table where idx not in (select idx from (select idx from table2 where ... limit 10) as i)
```
只要多一層 select，不要直接接上 in 就可以解決了

## Invalid string or buffer length 錯誤
在上面的基本語法轉換以後，基本上 99% 的功能已經是沒有問題了，但是在這最後的 1% 卻花了我好幾天的時間。

這個問題的發生是這樣的，當遇到某些欄位的時候就會發生這個錯誤
```cmd
[ma-2.0.11][mariadb-10.1.16] Invalid string or buffer length
```
這還不是一直發生，有的時候會有，有的時候很正常，功能的部份有的會發生，有的不會，這樣就要去看看程式碼有關那些欄位的存取情形與資料庫狀態，但是這就跟基本需求裏面說的相違背 (需要了解更多的 Code)，所以首先懷疑是 ODBC 的關係。

換了 1.0 版本更慘，連原來 SQL Statement 都會被截斷，基本上是不能退到 1.0 版了

只好硬著頭皮看 Codes 跟資料庫，終於發現到問題

當欄位是空字串 (不是 Null 喔) 時，透過 ODBC Select 到 ADODB.RecordSet 會有問題 (在其他 client 都沒問題)，無論有幾個欄位有這樣的情形，只要有一個它就 Error 給你看

所以就要改寫那些 SQL 語法 (通常都是 select *) 為明確欄位且要先轉為 Null 或是其他的值

原來 (有 a b c d e 5 個欄位，其中 c d e 有可能是空字串)

```sql
select * from table where ...
```

改寫成

```sql
select *, (case c when '' then NULL else c end) as c, (case d when '' then ' ' else d end) as d, (case e when '' then 'x' else e end) as e from table where ... 
```

Codes 馬上暴增超多的，Debug 更困難了

以前的 SQL Server ADODB 不會這樣，所以都沒有任何轉換機制，現在就要為了這個寫一堆多餘的 Codes

這個問題我有發個 [issue](https://jira.mariadb.org/browse/ODBC-52)，希望未來能解決

另外有個錯誤訊息，也是一樣的 
```cmd
Data provider or other service returned an E_FAIL status
```

