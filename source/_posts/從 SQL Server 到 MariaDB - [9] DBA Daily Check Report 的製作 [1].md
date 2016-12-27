---
title: 從 SQL Server 到 MariaDB - [9] DBA Daily Check Report 的製作 [1]
tags:
  - MariaDB
  - MSSQL
date: 2016-12-27 11:35:25
---

## 需求說明

(這篇與下一篇是因為參加 [2017 iT 邦幫幫忙鐵人賽](http://ithelp.ithome.com.tw/users/20103536/ironman)而產生的，原來是沒有那麼快要開始作，但是因為沒稿子了就只能趕快做出來，分享給大家)

在 SQL Server 與 Oracle DB 都有一個 Daily Check Scripts 來讓我在上班開始的時候作一些自主檢查，並且產出報告，這個對於管理多台機器較為方便。

在 SQL Server 上我採用[這個](http://www.wisesoft.co.uk/articles/dba_daily_checks_email_report.aspx) Scripts， Oracle DB 的部份採用[這個](https://github.com/sujunmin/OracleDBDailyCheckScripts)，十分方便，很多東西都能顯示，大部分的問題都能夠馬上發現。

到了 MariaDB 希望能夠 Porting 這個來方便管理。

接下來針對這個 [Porting](https://github.com/sujunmin/MariaDBDailyCheckScripts) 來說明過程
- PowerShell 基礎架構
- Uptime and Version
- Disk Drive Status
- Backup Status
- Event Status
- Fail Events Status
- Database File Status
- Session List
- Replication Status

### PowerShell 基礎架構
在作這個 Porting 之前，筆者先到 Google 找有沒有人有不錯的類似 Solution，大部分都是走 Shell Scripts，因為在 Windows 上用 PowerShell 比較方便，就決定用 PowerShell 來兜 Solution 了。

大致上的架構是這樣

- 變數設定
- Html 的頭(CSS, JS...)
    - mysql 執行各項的子 SQL
    - 將結果放到 out.html
    - Html = Html + out.html
    - 執行各項作業
- 全部都執行完畢後，整理一下寄信給相關人員

### Uptime and Version
Uptime 部份，從 `information_schema.GLOBAL_STATUS` 可以得到資料，接下來整理一下，化成需要的輸出。

```sql
SELECT CONCAT(CASE WHEN CAST(VARIABLE_VALUE as unsigned integer) < @UptimeCritical * 60 THEN '<span class="Critical">'
                   WHEN CAST(VARIABLE_VALUE as unsigned integer) < @UptimeWarning * 60 THEN '<span class="Warning">'
           ELSE '<span class="Healthy">' END,
FLOOR(HOUR(SEC_TO_TIME(VARIABLE_VALUE)) / 24), ' day(s), ',
MOD(HOUR(SEC_TO_TIME(VARIABLE_VALUE)), 24), ' hour(s), ',
MINUTE(SEC_TO_TIME(VARIABLE_VALUE)), ' minute(s)</span>')
from information_schema.GLOBAL_STATUS where VARIABLE_NAME='UPTIME';
```

Version 部份，從系統參數就能知道了。

```sql
SELECT @@version from dual;
```

### Disk Drives
這部份需要用的 Powershell 的 `get-wmiobject` 功能。

```powershell
$Disks = get-wmiobject -Class win32_logicaldisk -Filter "DriveType='3'"
```

接下就是計算大小，如果低於警告或是危險級就用不一樣的顏色標示。

```powershell
if (($d.FreeSpace / $d.Size * 100) -lt $FreeDiskSpacePercentCriticalThreshold) {$Html = $Html + "<div class=""Critical"">" + ("{0:N2}" -f ($d.FreeSpace / $d.Size * 100))+ "</div></td></tr>"}
     elseif (($d.FreeSpace / $d.Size * 100) -lt $FreeDiskSpacePercentWarningThreshold) {$Html = $Html + "<div class=""Warning"">" + ("{0:N2}" -f ($d.FreeSpace / $d.Size * 100))+ "</div></td></tr>"}
else {$Html = $Html + "<div class=""Healthy"">" + ("{0:N2}" -f ($d.FreeSpace / $d.Size * 100))+ "</div></td></tr>"} 
```

然後就接到原來要輸出的資料裡，這個部份就完成了。

### Backup Status

這個部份從系統面看不出來有跟所需要的資料有關連，但是從建立資料庫備份的檔案系統可以看得出來，因此也是用 Powershell 來完成這個需求。

|檔案資訊|備份資訊|Powershell 用法|
|:-----|:------|:-------------|
|檔案名稱|備份檔案名稱|`$File.FullName`|
|建立時間|開始備份的時間|`$File.CreationTime`|
|修改時間|完成備份時間|`$File.LastWriteTime`|


這邊的程式結構也是跟之前的一樣，如果有設定完整備份的資料夾，那就去看看那個資料夾狀態。

```powershell
Get-ChildItem $FullBackupPath -Filter FullBackup* |
        Foreach-Object {
	   $Html = $Html + "<tr><td>" + $_.FullName + "</td><td>" + $_.CreationTime + "</td><td>" + $_.LastWriteTime + "</td><td>" + ("{0:N2}" -f ($_.Length / 1048576)) + " MB</td></tr>" 
}
```

