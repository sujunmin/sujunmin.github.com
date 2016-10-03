---
title: 從 SQL Server 到 MariaDB - [6] Audit 的實作
tags:
  - MariaDB
  - MSSQL
date: 2016-10-03 13:52:23
---

## 需求說明
在資料庫的 Audit (稽核) 部份，需要有一套如同 SQL Server 的方式來執行。

## MariaDB Server Audit Plugin
好在 MariaDB 有 Server Audit Plugin，相關文件[在此](https://mariadb.com/kb/en/mariadb/about-the-mariadb-audit-plugin/)。

開起來很簡單，設定也很容易。

## MariaDB Server Audit Plugin 與 SQL Sever Trace File Audit 的比較
SQL Server 的 Audit 是用 Trace File 來儲存 Audit 資料，以下用一個表格做一個比較

|項目| SQL Server Trace File Audit | MariaDB Server Audit Plugin |
|---|-----------------------------|-----------------------------|
|儲存方式| Binary File 自有格式      | Text File (逗號隔開)， syslog |
|取用方式| `fn_trace_gettable()` 取得資料 | 打開檔案或是 syslog client|
|紀錄分類方式與數量| 20 種 Class，每個 Class 數個 Events ([2012](https://msdn.microsoft.com/en-us/library/ms175481(v=sql.110).aspx)) | 8 種 (`CONNECT`, `QUERY`, `READ`, `WRITE`, `CREATE`, `ALTER`, `RENAME`, `DROP`) |
|資料的控制| `sp_trace_*` 設定人事時地物，檔案需要自己處理| `server_audit_*` 設定人事時地物，有 rotate 設定 |

## 設定說明

在文件裡 Logging events 一節提到的可設定的 Type，說明如下

1. `CONNECT` 就是連線訊息
2. `QUERY` 執行 SQL 指令與結果代碼
3. `TABLE` 哪些 Tables 因此 `QUERY` 而受影響
4. `QUERY_DDL` DDL 的 Query，是 `QUERY`的子集合
5. `QUERY_DML` DML 的 Query，是 `QUERY`的子集合
6. `QUERY_DCL` DCL 的 Query，是 `QUERY`的子集合

什麼是預設的 DDL, DML, DCL 的 Query 呢? 這邊要看 Code 才知道，因為後續會有改程式碼的動作，所以到時一併說明之。

另外一個部份是 Output 的 Operation 欄位，有 `CONNECT`, `QUERY`, `READ`, `WRITE`, `CREATE`, `ALTER`, `RENAME`, `DROP`，這部份的詳細定義也是要看 Code。

## 客製化 Server Audit Plugin
原來的 Server Audit Plugin 其實大部份的需求都可以達成了，如果有[自己](https://github.com/sujunmin/SQLServerTraceFileParser)做 Log Server 的需求，需要更細緻的分類，因為有些資訊可以延遲送出(如日報表)，但是特權帳號的使用要馬上通報給其他長官，光只有這 8 種情形無法滿足需求。

### 針對 DDL 與 DCL 加入更細緻的分類
這邊先來看看 MariaDB 對於 DDL 與 DCL 是怎麼判斷的，首先到官網下載原始碼 (如 10.1.17)，不要到 github 上抓，因為那太新了。

#### Code 分析與修改
到 `plugin\server_audit\server_audit.c` 找到以下程式碼

```c
enum sa_keywords
{
  SQLCOM_NOTHING=0,
  SQLCOM_DDL,
  SQLCOM_DML,
  SQLCOM_GRANT,
  SQLCOM_CREATE_USER,
  SQLCOM_CHANGE_MASTER,
  SQLCOM_CREATE_SERVER,
  SQLCOM_SET_OPTION,
  SQLCOM_ALTER_SERVER,
  SQLCOM_TRUNCATE,
  SQLCOM_QUERY_ADMIN,
  SQLCOM_DCL,
};

struct sa_keyword
{
  int length;
  const char *wd;
  struct sa_keyword *next;
  enum sa_keywords type;
};


struct sa_keyword xml_word=   {3, "XML", 0, SQLCOM_NOTHING};
struct sa_keyword user_word=   {4, "USER", 0, SQLCOM_NOTHING};
struct sa_keyword data_word=   {4, "DATA", 0, SQLCOM_NOTHING};
struct sa_keyword server_word= {6, "SERVER", 0, SQLCOM_NOTHING};
struct sa_keyword master_word= {6, "MASTER", 0, SQLCOM_NOTHING};
struct sa_keyword password_word= {8, "PASSWORD", 0, SQLCOM_NOTHING};
struct sa_keyword function_word= {8, "FUNCTION", 0, SQLCOM_NOTHING};
struct sa_keyword statement_word= {9, "STATEMENT", 0, SQLCOM_NOTHING};
struct sa_keyword procedure_word= {9, "PROCEDURE", 0, SQLCOM_NOTHING};


struct sa_keyword keywords_to_skip[]=
{
  {3, "SET", &statement_word, SQLCOM_QUERY_ADMIN},
  {0, NULL, 0, SQLCOM_DDL}
};


struct sa_keyword not_ddl_keywords[]=
{
  {4, "DROP", &function_word, SQLCOM_QUERY_ADMIN},
  {4, "DROP", &procedure_word, SQLCOM_QUERY_ADMIN},
  {4, "DROP", &user_word, SQLCOM_DCL},
  {6, "CREATE", &user_word, SQLCOM_DCL},
  {6, "CREATE", &function_word, SQLCOM_QUERY_ADMIN},
  {6, "CREATE", &procedure_word, SQLCOM_QUERY_ADMIN},
  {6, "RENAME", &user_word, SQLCOM_DCL},
  {0, NULL, 0, SQLCOM_DDL}
};


struct sa_keyword ddl_keywords[]=
{
  {4, "DROP", 0, SQLCOM_DDL},
  {5, "ALTER", 0, SQLCOM_DDL},
  {6, "CREATE", 0, SQLCOM_DDL},
  {6, "RENAME", 0, SQLCOM_DDL},
  {8, "TRUNCATE", 0, SQLCOM_DDL},
  {0, NULL, 0, SQLCOM_DDL}
};
```
以上可以看的的出來 DDL 是哪些會算，哪些不會算，譬如說 `DROP`, `DROP User` 算是 DDL，但是 `DROP Function` 不算 DDL。

```c

struct sa_keyword dml_keywords[]=
{
  {2, "DO", 0, SQLCOM_DML},
  {4, "CALL", 0, SQLCOM_DML},
  {4, "LOAD", &data_word, SQLCOM_DML},
  {4, "LOAD", &xml_word, SQLCOM_DML},
  {6, "DELETE", 0, SQLCOM_DML},
  {6, "INSERT", 0, SQLCOM_DML},
  {6, "SELECT", 0, SQLCOM_DML},
  {6, "UPDATE", 0, SQLCOM_DML},
  {7, "HANDLER", 0, SQLCOM_DML},
  {7, "REPLACE", 0, SQLCOM_DML},
  {0, NULL, 0, SQLCOM_DML}
};
```
這邊可以看得出來 DML。

```c
struct sa_keyword dcl_keywords[]=
{
  {6, "CREATE", &user_word, SQLCOM_DCL},
  {4, "DROP", &user_word, SQLCOM_DCL},
  {6, "RENAME", &user_word, SQLCOM_DCL},
  {5, "GRANT", 0, SQLCOM_DCL},
  {6, "REVOKE", 0, SQLCOM_DCL},
  {3, "SET", &password_word, SQLCOM_DCL},
  {0, NULL, 0, SQLCOM_DDL}
};
```
這邊可以看得出來 DCL。
```c

struct sa_keyword passwd_keywords[]=
{
  {3, "SET", &password_word, SQLCOM_SET_OPTION},
  {5, "ALTER", &server_word, SQLCOM_ALTER_SERVER},
  {5, "GRANT", 0, SQLCOM_GRANT},
  {6, "CREATE", &user_word, SQLCOM_CREATE_USER},
  {6, "CREATE", &server_word, SQLCOM_CREATE_SERVER},
  {6, "CHANGE", &master_word, SQLCOM_CHANGE_MASTER},
  {0, NULL, 0, SQLCOM_NOTHING}
};
```
這邊說明什麼時候會碰到密碼 (在 log 裡會紀錄動作，然後把密碼用星號表示)。

接下來找到以下的程式碼

```c
static int log_statement_ex(const struct connection_info *cn,
                            time_t ev_time, unsigned long thd_id,
                            const char *query, unsigned int query_len,
                            int error_code, const char *type)
{
  size_t csize;
  ...
  if (query == 0)
  {
    /* Can happen after the error in mysqld_prepare_stmt() */
    query= cn->query;
    query_len= cn->query_length;
    if (query == 0 || query_len == 0)
      return 0;
  }
  
  if (query && !(events & EVENT_QUERY_ALL) &&
      (events & EVENT_QUERY))
  {
    const char *orig_query= query;
    ...
    ...
    ...
csize= log_header(message, message_size-1, &ev_time,
                    servhost, servhost_len,
                    cn->user, cn->user_length,cn->host, cn->host_length,
                    cn->ip, cn->ip_length, thd_id, query_id, type);
    ...    
```
這邊的 Code 說明有許多的紀錄後來會被標記成 `QUERY`，如果要利用原來已經分類的 DCL 與 DDL 架構，這邊就要先來利用一下。

```c
static int log_statement_ex(const struct connection_info *cn,
                            time_t ev_time, unsigned long thd_id,
                            const char *query, unsigned int query_len,
                            int error_code, const char *type)
{
  size_t csize;
  ...
  char *modtype = type;

  if (query == 0)
  {
    /* Can happen after the error in mysqld_prepare_stmt() */
    query= cn->query;
    query_len= cn->query_length;
    if (query == 0 || query_len == 0)
      return 0;
  }

  if (!filter_query_type(query, not_ddl_keywords) &&
	  filter_query_type(query, ddl_keywords))
	  modtype = "QUERY_DDL";

  if (filter_query_type(query, dml_keywords))
	  modtype = "QUERY_DML";

  if (filter_query_type(query, dcl_keywords))
	  modtype = "QUERY_DCL";
  
  if (query && !(events & EVENT_QUERY_ALL) &&
      (events & EVENT_QUERY))
  {
    const char *orig_query= query;
    ...
    ...
    ...
  csize= log_header(message, message_size-1, &ev_time,
                    servhost, servhost_len,
                    cn->user, cn->user_length,cn->host, cn->host_length,
                    cn->ip, cn->ip_length, thd_id, query_id, modtype);
    ...  
```    
#### Build Plugin
接下來要來 Build Plugin，根據這個[文件](https://mariadb.com/kb/en/mariadb/Building_MariaDB_on_Windows/)，安裝該安裝的函式庫與工具，以下紀錄一下
1. Visual Studio 2015 Community 版
2. Bison (記得路徑不要有空白)
3. cmake
4. Windows SDK 8.1 (這個現在沒裝沒關係，等會可以裝)

Build 方式，在 Source 底下 
1. `mkdir bld`
2. `cd bld`
3. `cmake .. -G "Visual Studio 14 2015 Win64"` (64 位元版)
4. `cmake --build . --config Relwithdebinfo`

會碰到的錯 (然後會有一大堆的 warning)
1. Windows SDK 8.1 沒裝
   
   這個要去 `bld` 底下用 IDE 開 `MySQL.sln`，打開的時候他會說你的 SDK 缺少，要不要下載安裝，讓他自己跑完就可以了。
2. `sql\sql_locale.cc` 有怪字無法編譯
   
   我查到的結果都是開 notepad++ 去改 utf-8，不過我做完還是失敗，後來是開 notepad 然後用 unicode 儲存 (不是 utf-8 喔) 就可以過了。

### 測試
把 MariaDB 停掉，從 `bld\plugin\server_audit\RelWithDebInfo` 把 dll 跟 pdb 複製到 `MariaDB\lib` 下，重開看看。

原來的 log 大概像這樣
```syslog
20161003 13:15:58,WSTest,sujunmin,localhost,98,783,QUERY,test1,'REVOKE RELOAD  ON *.* FROM \'lsuser\'@\'%\'',0
```
應該會變成這樣
```syslog
20161003 16:31:14,WSTest,sujunmin,localhost,98,783,QUERY_DCL,test1,'REVOKE RELOAD  ON *.* FROM \'lsuser\'@\'%\'',0
```

後續的 Log Parser 就可以根據這些關鍵字來分析使用了。
