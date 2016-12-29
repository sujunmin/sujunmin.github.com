---
title: 從 SQL Server 到 MariaDB - [11] Audit Log Parser 的實做

tags:
  - MariaDB
  - MSSQL
date: 2016-12-29 16:04:48
---

## 需求說明
因為公司的稽核項目很多，所以大部分的專案都會買一些 SIEM 的設備 (Arcsight, 趨勢的平台...)，有的時候很好用，但是有的時候太複雜或是很慢，所以就有自己作 Audit Log Parser 的動機。

對於 SQL Server 的 Audit Log (Trace File)有個自己作的 [Parser](https://github.com/sujunmin/SQLServerTraceFileParser)，對於 MariaDB 應該也要有一樣的方式處理。

## 基本架構
我的環境除了正式機以外，還有備援機 (Slave)，原來是想在備援機上的 relayed binlog 來作處理，結果大失所望。那些從 master relayed 來的 binlog 完全不會有[紀錄](https://jira.mariadb.org/browse/MDEV-11618)，這樣就不能從備援機正式機的 Audit Log 了。

我的 SQL Server 正式機因為怕效能損耗不會在正式機上 Parsing Trace Files，而是把資料透過網芳存到備援機，由備援機來 Parsing，那 MariaDB 可不可以這樣做呢?

答案是可以的。

在 `server_audit_file_path` 設定 `////remotehost//dir//audit.log` 這樣就能達到遠端存資料了 (記得反斜線的數量)。

接下就是設計的問題。仍然使用好用的 Powershell 來達成目標。

架構大概是這樣

- 先前處理
    - 建立相關資料夾與環境
    - 匯入 log
    - 分析 log
    - 如有重要資料需儲存的需放到資料庫
    - 如有重要資料需要即時告警的需發送

接下來就來介紹如何分析與處理。

## Audit Log 的過程與實做
首先從[官方文件](https://mariadb.com/kb/en/mariadb/about-the-mariadb-audit-plugin/)可以看到結構。

```
[timestamp],[serverhost],[username],[host],[connectionid],[queryid],[operation],[database],[object],[retcode]
```

有這些資料就能分析了(以下可能有更好的解法，這個[解法](https://github.com/sujunmin/MariaDBAuditFileParser/blob/master/TraceFileParser.ps1)只是其中一種)。

### 以 `[timestamp],` 分段
因為檔案不是用 `\r\n` 或 `\n` 來分隔一行，而是用空白，在`[timestamp]` 與 `[object]` 也會有空白，所以需要先分段。透過 `[timestamp],` 來分段。

```powershell
$matches = ([regex] '(\d{4})(\d{2})(\d{2}) (\d{2}):(\d{2}):(\d{2}),').Matches($auditfilecontent)
```
### 每一段以 `,` 分欄位
接下來就是每一個 `,` 欄位，到 `[object]` 前都是這樣分的。

```powershell
$submatches = ([regex] ',').Matches($line)  
$timestamp    = $line.SubString(0, $submatches[0].Index)
$serverhost   = $line.SubString($submatches[0].Index+1, $submatches[1].Index - $submatches[0].Index-1)
$username     = $line.SubString($submatches[1].Index+1, $submatches[2].Index - $submatches[1].Index-1)
$hosts        = $line.SubString($submatches[2].Index+1, $submatches[3].Index - $submatches[2].Index-1)
$connectionid = $line.SubString($submatches[3].Index+1, $submatches[4].Index - $submatches[3].Index-1)
$queryid      = $line.SubString($submatches[4].Index+1, $submatches[5].Index - $submatches[4].Index-1)
$operation    = $line.SubString($submatches[5].Index+1, $submatches[6].Index - $submatches[5].Index-1)
$database     = $line.SubString($submatches[6].Index+1, $submatches[7].Index - $submatches[6].Index-1)
$retcode      = $line.SubString($submatches[$submatches.Count-1].Index+1,$line.length - $submatches[$submatches.Count-1].Index-1).Trim()
```

到了 `[object]`，因為有可能裡頭也有 `,` 所以不能直接拿 `split` 的值來用，取最後一個 `,` 之前的當作是 `[object]`。

```powershell
if ($submatches[$submatches.Count-1].Index - $submatches[7].Index- 3 -lt 0) 
 {
   $object = ""
 }else
 {
   $object = $line.SubString($submatches[7].Index+2, $submatches[$submatches.Count-1].Index - $submatches[7].Index-3)  
 }
```

有了資料就能作其他用途了。

## 處理的[方式](https://github.com/sujunmin/MariaDBAuditFileParser/blob/master/TraceFileParser.ps1)

基本上的需求如下
 - 原始資料保存
 - 人員使用的帳號(客服或帳務處理)作業需在每日統計報表裡呈現
 - 資料庫管理者使用的帳號作業需在每日統計報表裡呈現
 - DCL 與 DDL 作業時需即時發送通知給主管
 - 每日使用狀態報表

### 重要資料紀錄 DB
針對可得到的資料要作一個[紀錄](https://github.com/sujunmin/MariaDBAuditFileParser/blob/master/auditdb.sql)，用資料庫處理。

```sql
CREATE DATABASE IF NOT EXISTS `auditdb`;
USE `auditdb`;

CREATE TABLE IF NOT EXISTS `auditdata` (
  `timestamp` datetime NOT NULL,
  `serverhost` char(100) NOT NULL,
  `username` char(100) NOT NULL,
  `hosts` char(100) NOT NULL,
  `connectionid` char(100) NOT NULL,
  `queryid` char(100) NOT NULL,
  `operation` char(100) NOT NULL,
  `database` char(100) NOT NULL,
  `object` varchar(10000) DEFAULT NULL,
  `retcode` char(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

### 相關人員帳號使用情形紀錄
看 `[username]` 欄位即可得到資訊。

```powershell
if ($Users -contains $username)
                {
                    $sql = "INSERT INTO " + $AuditDB + "." + $TraceFileData + " VALUES (STR_TO_DATE('" + $timestamp + "','%Y%m%d %H:%i:%S'), '" + $serverhost + "', '" + $username + "', '" + $hosts + "', '" + $connectionid + "', '" + $queryid + "', '" + $operation + "', '" + $database + "', '" + $object + "', '" + $retcode +"');"    
                    & $mysqlexe -u $AuditDBUserName --password=$AuditDBPassword -h $AuditDBServer  -e "$($sql)"
}
```
### DCL 與 DDL 作業即時告警

如果 `server_audit.dll` 是用[之前](https://sujunmin.github.io/blog/2016/10/03/%E5%BE%9E%20SQL%20Server%20%E5%88%B0%20MariaDB%20-%20[6]%20Audit%20%E7%9A%84%E5%AF%A6%E4%BD%9C/)說的方式重新作一個，這邊就很簡單：看 `[operation]` 是不是 `QUERY_DCL`，`QUERY_DML`，`QUERY_DDL` 就可以了。如果不是用之前的方式，這邊就要分析 `[object]` 裡頭的內容了。

把所需的資料放到一個 `ArrayList` 裡，全部都 Parsing 完以後看看是不是有資料，有的話就發信告警。

```powershell
if ($MailEventClasses -contains $operation)
{
    $DXL.Add(($timestamp, $serverhost, $username, $hosts, $connectionid, $queryid, $operation, $database, $object, $retcode))
}
...
if ($DXL.Count -ne 0)
{
    $output = "<table border=1 style=border-width: thin; border-spacing: 2px; border-style: solid; border-color: gray; border-collapse: collapse;> 
                 <tr><th>StartTime</th>
             <th>LoginName</th>
                 <th>HostName</th>
             <th>ServerName</th>
                 <th>DatabaseName</th>
                 <th>Operation</th>
                     <th>TextData</th>
                     <th>ReturnCode</th></tr>"

        $DXL | Foreach-Object {

        $output = $output + "<tr><td>" + $_[0] + "</td><td>" +  $_[2] + "</td><td>" + $_[3] + "</td><td>" + $_[1] + "</td><td>" + $_[7] + "</td><td>" + $_[6] + "</td><td>" + $_[8] + "</td><td>" + $_[9] + "</td></tr>"
    }

    $output = $output + "</table>"
    Send-MailMessage -To $MailTo -From $MailFrom -Subject "資料庫特權活動即時告警" -Body "$output" -BodyAsHtml -SmtpServer $MailServer -Encoding ([System.Text.Encoding]::UTF8)        
}
```

## 每日匯總報表

如果之前的作法中，塞資料到資料庫的動作有作的話，這邊就十分簡單。

先設定一些資料。

```powershell
$AuditDBServer = "server"
$AuditDB = "AuditDB"
$TraceFileData = "auditdata"
$AuditDBUserName = "username"
$AuditDBPassword = "password"
$mysqlexe = "D:\Program Files\MariaDB 10.1\bin\mysql.exe"
$Users = "'need','to','be','audit','users'"
$MailFrom = "from@abc.com"
$MailEventClasses = "'QUERY_DDL','QUERY_DCL'"
$MailTo = "to@def.com"
$MailServer = "smtp.server"
```

接下來移除 Audit 資料庫裡頭 3 天前的資料。

```powershell
$NowTime = Get-Date
$TempPath = "D:\AuditFile"

$sql = "delete from " + $AuditDB + "." + $TraceFileData + " where timestamp < '"+("{0:yyyy-MM-dd}" -f $NowTime.AddDays(-3))+ "' or timestamp is Null;"
& $mysqlexe -u $AuditDBUserName --password=$AuditDBPassword -h $AuditDBServer  -e "$($sql)" 
```

符合 `$MailEventClasses` 的列出來，作為每日資料庫系統設定異動報表。

```powershell
$output = "<table border=1 style=border-width: thin; border-spacing: 2px; border-style: solid; border-color: gray; border-collapse: collapse;>  
                 <tr><th>StartTime</th>
		     <th>LoginName</th>
	             <th>HostName</th>
		     <th>ServerName</th>
	             <th>DatabaseName</th>
	             <th>Operation</th>
                     <th>TextData</th>
                     <th>ReturnCode</th></tr>"

$sql  = "select '<tr><td>', ``timestamp``, '</td><td>', ``username`` , '</td><td>', ``hosts``, '</td><td>', ``serverhost`` , '</td><td>', ``database`` , '</td><td>', ``operation`` , '</td><td>', ``object``, '</td><td>', ``retcode`` , '</td></tr>' from " + $AuditDB + "." + $TraceFileData + " where operation in (" + $MailEventClasses + ") and timestamp between STR_TO_DATE('" +("{0:yyyy-MM-dd}" -f $NowTime.AddDays(-1))+ "', '%Y-%m-%d')  and STR_TO_DATE('" +("{0:yyyy-MM-dd}" -f $NowTime)+ "', '%Y-%m-%d')"
$output = $output + (& $mysqlexe -u $AuditDBUserName --password=$AuditDBPassword -h $AuditDBServer -sN -e "$($sql)" ) 
$output = $output + "</table>"

$Subj = $("{0:yyyy-MM-dd}" -f $NowTime.AddDays(-1)) + " 每日資料庫系統設定異動報表"
Send-MailMessage -To $MailTo -From $MailFrom -Subject "$Subj"  -Body "$output" -BodyAsHtml  -SmtpServer $MailServer -Encoding ([System.Text.Encoding]::UTF8)
```

符合 `$Users` 的列出來，作為每日資料庫使用者使用情形報表。

```powershell
$output = "<table border=1 style=border-width: thin; border-spacing: 2px; border-style: solid; border-color: gray; border-collapse: collapse;>  
                 <tr><th>StartTime</th>
		     <th>LoginName</th>
	             <th>HostName</th>
		     <th>ServerName</th>
	             <th>DatabaseName</th>
	             <th>Operation</th>
                     <th>TextData</th>
                     <th>ReturnCode</th></tr>"

$sql  = "select '<tr><td>', ``timestamp``, '</td><td>', ``username`` , '</td><td>', ``hosts``, '</td><td>', ``serverhost`` , '</td><td>', ``database`` , '</td><td>', ``operation`` , '</td><td>', ``object``, '</td><td>', ``retcode`` , '</td></tr>' from " + $AuditDB + "." + $TraceFileData + " where username in (" + $Users + ") and timestamp between STR_TO_DATE('" +("{0:yyyy-MM-dd}" -f $NowTime.AddDays(-1))+ "', '%Y-%m-%d')  and STR_TO_DATE('" +("{0:yyyy-MM-dd}" -f $NowTime)+ "', '%Y-%m-%d')"
$sql
$output = $output + (& $mysqlexe -u $AuditDBUserName --password=$AuditDBPassword -h $AuditDBServer -sN -e "$($sql)" ) 
$output = $output + "</table>"


$Subj = $("{0:yyyy-MM-dd}" -f $NowTime.AddDays(-1)) + " 每日資料庫使用者使用情形報表"
Send-MailMessage -To $MailTo -From $MailFrom -Subject "$Subj"  -Body "$output" -BodyAsHtml  -SmtpServer $MailServer -Encoding ([System.Text.Encoding]::UTF8)
```
