---
title: 從 SQL Server 到 MariaDB - [8] User-defined Functions 與擴增 Event 功能
tags:
  - MariaDB
  - MSSQL
date: 2016-11-08 09:47:22
---

## 需求說明
雖然一些排程或是資訊可以透過系統排程來處理，但是原有的 SQL Server 管理方式幾乎都是透過 SQL Server Job 系統執行，加上 SQL Server 有許多可以在 Server 本體就能做完的方式，為了追求一樣的管理方式，導入 User-defined Functions (UDF) 來擴增 Server 功能。

## 透過 MariaDB 裡的 Event System 進行備份
基本上 Google 搜尋 MariaDB/MySQL 備份，不外乎是利用 cron jobs 或是 Windows tasks 來進行備份，這樣進行備份一則與原來的 SQL Server 管理不一樣，另外一個也沒辦法知道備份的情況 (要登入主機看 Task 狀態)。

如果能透過 MariaDB 來發動備份 (透過 Event) 感覺能夠與以前的管理方式一樣，Server 要的排程 Server 自己發動，而且也能收集相關資料進行監控。

但是 MariaDB 沒有什麼介面可以來執行系統作業，這時候剛好找到 [UDF](https://mariadb.com/kb/en/mariadb/user-defined-functions/)，還有一大堆神人做好的 [MySQLUDF](http://www.mysqludf.org/) Repository，裡頭剛好有我要的與系統處理有關的 [UDF](https://github.com/mysqludf/lib_mysqludf_sys#readme)，正好可以拿來用。

## lib_mysqludf_sys 編譯、安裝、與使用

### 編譯
如果連到 lib_mysqludf_sys 的 github，發現到它的 Makefile 都是為 gcc 做的，Windows 用戶有點麻煩。

網路上可以找到編譯好的 dll，但是覺得有點怕不知道裏面有什麼。

還好找到一篇也是部份 MySQL UDF 的作者寫的 blog [Creating MySQL UDFs with Microsoft Visual C++ Express](http://rpbouman.blogspot.tw/2007/09/creating-mysql-udfs-with-microsoft.html)，裡頭有一些方法可以參考。

編譯的環境跟之前修改 [audit log plugin](https://sujunmin.github.io/blog/2016/10/03/%E5%BE%9E%20SQL%20Server%20%E5%88%B0%20MariaDB%20-%20[6]%20Audit%20%E7%9A%84%E5%AF%A6%E4%BD%9C/) 的環境一樣，需要注意的地方如下

#### Configuring the Include path
Platform SDK 的 Include Files 在 `C:\Program Files (x86)\Windows Kits\8.1\Include` 。

MariaDB 的我是 `<MariaDB>\include` 與 `<MariaDB>\include\mysql`。

這兩個資料夾的資料最好複製一份到其他地方，因為等會要改。

#### Adding the HAVE_DLOPEN macro
除了加這個以外，還要加兩個防止 `strncpy` 的安全問題一直錯的 macro。

`HAVE_DLOPEN;_CRT_SECURE_NO_DEPRECATE;_CRT_NONSTDC_NO_DEPRECATE`

#### Configuring the library path
Platform SDK 的 Lib Files 在 `C:\Program Files (x86)\Windows Kits\8.1\Lib` 。

MariaDB 的是 `<MariaDB>\lib` 。

#### 其他
編譯的時候會一直有 `snprintf` 重複定義的問題，為了減少麻煩，直接把 MariaDB 的 Include Files 裡有相關的定義先拿掉，待編譯完再改回來。

### 安裝
將產生出來的 dll 與 pdf 檔複製到 `<MariaDB>\lib\plugin`，透過 github 裡的 [`lib_mysqludf_sys.sql`](https://github.com/mysqludf/lib_mysqludf_sys/blob/master/lib_mysqludf_sys.sql) 安裝，記得 so 要改成 dll。

```sql
DROP FUNCTION IF EXISTS lib_mysqludf_sys_info;
DROP FUNCTION IF EXISTS sys_get;
DROP FUNCTION IF EXISTS sys_set;
DROP FUNCTION IF EXISTS sys_exec;
DROP FUNCTION IF EXISTS sys_eval;

CREATE FUNCTION lib_mysqludf_sys_info RETURNS string SONAME 'lib_mysqludf_sys.dll';
CREATE FUNCTION sys_get RETURNS string SONAME 'lib_mysqludf_sys.dll';
CREATE FUNCTION sys_set RETURNS int SONAME 'lib_mysqludf_sys.dll';
CREATE FUNCTION sys_exec RETURNS int SONAME 'lib_mysqludf_sys.dll';
CREATE FUNCTION sys_eval RETURNS string SONAME 'lib_mysqludf_sys.dll';
```

### 使用
可以看 [`lib_mysqludf_sys.html`](https://github.com/mysqludf/lib_mysqludf_sys/blob/master/lib_mysqludf_sys.html)，基本上很簡單。

|Function Name| Input | Output|
|-------------|-------|-------|
|`lib_mysqludf_sys_info`| N/A| 版本號碼|
|`sys_get`|系統變數名稱|系統變數值|
|`sys_set`|系統變數名稱<br /> 要設定的系統變數值 |0 為成功，其他為失敗|
|`sys_exec`|需執行的指令|該指令執行的 Process 傳回值|
|`sys_eval`|需執行的指令|指令在 Standard Output 產生內容|

來跑一下試試看

```sql
select sys_eval('ipconfig');
```

<img src=https://raw.githubusercontent.com/sujunmin/sujunmin.github.com/master/test/lib_mysqludf_sys_01.png />

竟然是亂碼，但是 cmd 的沒有問題

<img src=https://raw.githubusercontent.com/sujunmin/sujunmin.github.com/master/test/lib_mysqludf_sys_02.png />

顯然是 Windows 編碼問題，最後是這樣解決的。

```sql
select convert(convert(sys_eval('ipconfig') using big5) using utf8);
```

<img src=https://raw.githubusercontent.com/sujunmin/sujunmin.github.com/master/test/lib_mysqludf_sys_03.png />

## 與 Event 實作備份

首先透過之前做的 [Event 紀錄系統](https://sujunmin.github.io/blog/2016/09/10/%E5%BE%9E%20SQL%20Server%20%E5%88%B0%20MariaDB%20-%20[4]%20SQL%20Agent%20%E8%88%87%20MariaDB%20Event/)來做處理，不過因為 `sys_eval` 與 `sys_exec` 有珍貴的 Log 資料，所以不能只是紀錄單純的 OK 就好，加上兼容一般 SQL 的排程，所以修改了一下 Event 紀錄系統。

### 備份排程
做一個備份排程如下

```sql
CREATE DEFINER=`sujunmin`@`%` EVENT `backups`
	ON SCHEDULE
		EVERY 1 MINUTE STARTS '2016-11-07 13:42:26'
	ON COMPLETION NOT PRESERVE
	ENABLE
	COMMENT ''
	DO BEGIN
    call master.proc_for_event_history('master', 'backups', 'select sys_eval(\'"E://MariaDB 10.1//bin//mysqldump.exe" -u sujunmin -pabcdef@12345 --master-data --verbose --all-databases --events --routines --gtid 2>&1 > E://backup//all_db.sql\') into @outv;');
END
```

要注意的是中間那個 `2>&1`，因為 `mysqldump` 是拿 [Standard Error](http://dba.stackexchange.com/questions/14305/how-to-log-verbose-output-from-mysqldump) 來 Output 訊息，但是 `sys_eval` 只會送回 Standard Outupt 內容。

執行完以後看看結果。

<img src=https://raw.githubusercontent.com/sujunmin/sujunmin.github.com/master/test/lib_mysqludf_sys_04.png />

## 資安問題

網路上有很多使用 MySQL UDF 透過這些 UDF 來存取系統的[資安問題](https://securitypentester.ninja/mysql-udf-injection/)，所以要確保以下的防護是否有達成。

1. Web 連資料庫不能是 root 等級的帳號，權限愈小愈好。
2. AppArmor 或是 UAC 的設定。

## 其他紀錄

### 2016/11/08
安裝的時候如果碰到不能載入，除了之前那篇寫的內容之外，有可能是 Visual C++ Redistributable 套件需要安裝，我是在 Visual Studio 2015 編譯的，找 [Visual C++ Redistributable for Visual Studio 2015](https://www.microsoft.com/en-us/download/details.aspx?id=48145) 安裝就可以了。
