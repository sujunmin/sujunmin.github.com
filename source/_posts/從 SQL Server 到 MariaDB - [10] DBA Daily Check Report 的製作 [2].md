---
title: 從 SQL Server 到 MariaDB - [10] DBA Daily Check Report 的製作 [2]

tags:
  - MariaDB
  - MSSQL
date: 2016-12-27 11:45:16
---

### Event Status。

這個取得資料的方式，除了之前提到的[擴充 Event](https://sujunmin.github.io/blog/2016/09/10/%E5%BE%9E%20SQL%20Server%20%E5%88%B0%20MariaDB%20-%20[4]%20SQL%20Agent%20%E8%88%87%20MariaDB%20Event/)  可以得到 Event 結果以外，就是系統的 `information_schema.EVENTS` 可以得到相關資訊。

#### DB, Event Name, Last Run Time
這些都能從 `information_schema.EVENTS` 拿 (`event_schema`, `event_name`, `last_executed`)。

#### Event 種類
Event 種類有 `ENABLE`, `DISABLE`, `SLAVESIDE_DISABLED` (Replication 時從 Master 傳來的 Event，不會在 Slave 執行)，根據實際情形作標示。

```sql
CASE status 
  WHEN 'ENABLED' THEN '<div class="Healthy">Yes</div>' 
  WHEN 'SLAVESIDE_DISABLED' THEN '<div class="Healthy">No</div>' 
  ELSE '<div class="Warning">No</div>' 
END, 
```

#### Succeed
根據 `master.event` 與 `information_schema.EVENTS` 來 count，沒有 `errno` 的就是成功的。

```sql
CASE (select count(*) from master.event_history where db = ev.EVENT_SCHEMA and name = ev.EVENT_NAME and errno is null and start >= subdate(now(), @NumDays)) 
  WHEN 0 THEN CONCAT('<div class="Warning">', (select count(*) from master.event_history where db = ev.EVENT_SCHEMA and name = ev.EVENT_NAME and errno is null and start >= subdate(now(), @NumDays)), '</div>') 
  ELSE (select count(*) from master.event_history where db = ev.EVENT_SCHEMA and name = ev.EVENT_NAME and errno is null and start >= subdate(now(), @NumDays)) 
END,
```

#### Failed
根據 `master.event` 與 `information_schema.EVENTS` 來 count，有 `errno` 的就是失敗的。

```sql
CASE (select count(*) from master.event_history where db = ev.EVENT_SCHEMA and name = ev.EVENT_NAME and errno is not null and start >= subdate(now(), @NumDays)) 
  WHEN 0 THEN '<div class="Healthy">0</div>' 
  ELSE CONCAT('<div class="Critical">', (select count(*) from master.event_history where db = ev.EVENT_SCHEMA and name = ev.EVENT_NAME and errno is not null and start >= subdate(now(), @NumDays)),'</div>') 
END,
```

#### Next Run Time
根據 `master.event` 可以用算的，但是算的動作不能直接傳入 column 裡頭的值 (`select date_add(colA, interval interval_value interval_field)...`)，所以寫成醜醜的 `CASE`。

這個部份是我覺得最沒有自動化的地方。

```sql
case interval_field
  when "YEAR"	    then date_add(last_executed, interval interval_value YEAR)
  when "QUARTER"	then date_add(last_executed, interval interval_value QUARTER)
  when "MONTH"	    then date_add(last_executed, interval interval_value MONTH)
  when "DAY"	    then date_add(last_executed, interval interval_value DAY)
  when "HOUR"	    then date_add(last_executed, interval interval_value HOUR)
  when "MINUTE"	    then date_add(last_executed, interval interval_value MINUTE)
  when "WEEK"	    then date_add(last_executed, interval interval_value WEEK)
  when "SECOND"	    then date_add(last_executed, interval interval_value SECOND)
end,
```

(這個 interval 不是全部，我只有寫常用的)

#### Last Result
根據 `master.event` 與 `information_schema.EVENTS` 來看最後一次結果的內容。

```sql
(select CASE 
          WHEN errno is null THEN CONCAT('<span class="Healthy">', message_text, '</span>') 
          ELSE CONCAT('<span class="Critical">', message_text, '</span>') 
        END 
 from master.event_history where db = ev.EVENT_SCHEMA and name = ev.EVENT_NAME order by start limit 1),
```

### Fail Event

只要看之前作的 [`master.event_history`](https://sujunmin.github.io/blog/2016/09/10/%E5%BE%9E%20SQL%20Server%20%E5%88%B0%20MariaDB%20-%20[4]%20SQL%20Agent%20%E8%88%87%20MariaDB%20Event/) 就可以了。

```sql
select '<tr><td>', `db`, '</td><td>', `name`, '</td><td>', `start`, '</td><td>', `end`, '</td><td>', `sqlstate`, '</td><td>', `errno` , '</td><td>', `message_text`, '</td><td>', `record_time`, '</td></tr>' from master.event_history where errno is not null and start >= subdate(now(), @NumDays);
```

### Database File Status

這部份的資料可以從 `information_schema.tables` 來處理，以下為重要的欄位與說明。

|欄位|說明|
|:--|:--|
|`data_length`|資料大小|
|`index_length`|Index大小|
|`data_free`|剩餘空間|

所以一個 table file 的大小 = `data_length` + `index_length` + `data_free`。
用掉的大小 = `data_length` + `index_length`。

知道這些配合一些告警就能完成了。

```sql
SELECT '<tr><td>', table_schema, 
              '</td><td>',table_name,
              '</td><td>', concat(@@datadir, table_schema, '\\', table_name, '.*'),
              '</td><td>', Round((data_length + index_length + data_free) / 1024 / 1024 , 1),
 	      '</td><td>', Round((data_length + index_length) / 1024 / 1024 , 1),
 	      '</td><td>', Round((data_free) / 1024 / 1024 , 1),
 	      '</td><td>', CASE WHEN Round((data_length + index_length)/(data_length + index_length + data_free) * 100 , 2) > @CriticalThresholdPCT AND Round((data_length + index_length + data_free) / 1024 / 1024 , 1) > 1 THEN CONCAT('<div class="Critical">', Round((data_length + index_length)/(data_length + index_length + data_free) * 100 , 2),'</div>')
                                WHEN Round((data_length + index_length)/(data_length + index_length + data_free) * 100 , 2) > @WarningThresholdPCT AND Round((data_length + index_length + data_free) / 1024 / 1024 , 1) > 1 THEN CONCAT('<div class="Warning">', Round((data_length + index_length)/(data_length + index_length + data_free) * 100 , 2),'</div>')
		                ELSE CONCAT('<div class="Healthy">', Round((data_length + index_length)/(data_length + index_length + data_free) * 100 , 2),'</div>') END,
              '</td></tr>'
FROM   information_schema.tables where table_schema not in ('performance_schema', 'mysql', 'information_schema')
order by table_schema, table_name;
```

### Session List

只要看 `information_schema.PROCESSLIST` 就可以了。

```sql
select '<tr><td>', id,
'</td><td>', user, 
'</td><td>', host, 
'</td><td>', db, 
'</td><td>', command, 
'</td><td>', REPLACE(REPLACE(info,'<','<'), '>', '>'),
'</td><td>', state, 
'</td><td>', memory_used, 
'</td><td>', time, 
'</td><td>', query_id, 
'</td></tr>' from information_schema.PROCESSLIST order by id;
```

中間的 replace 是因為用到這個 Powershell 時，也是會在 `information_schema.PROCESSLIST` 呈現的，如果沒有替換一下，就會混亂了，所以要先 escape。

### Replication Status

這個是說希望能在報表中知道 Slave 的基本狀況，尤其是 Slave 如果發生 Error，會卡在那邊，如果 binlog 有 expire 時間的話，太晚知道問題解決完後 binlog 因 expire 會不見，就有機會無法復原。

首先，先透過 `information_schema.GLOBAL_STATUS` 的 `
SLAVE_RUNNING` 看該台機器是 Master 還是 Slave (在 [`CheckSlave.sql`](https://github.com/sujunmin/MariaDBDailyCheckScripts/blob/master/CheckSlave.sql)中)

```sql
select case variable_value when 'ON' then 'O' else '' end from information_schema.GLOBAL_STATUS where variable_name='SLAVE_RUNNING';
```

如果是的話就是 Slave。

看 Slave 狀態，指令是 `show slave status` (在 [`ShowSlaveStatus.sql`](https://github.com/sujunmin/MariaDBDailyCheckScripts/blob/master/ShowSlaveStatus.sql) 中)，但這個指令我找了很久，完全不知道是 reference 哪個 table 或是 variable，於是只能自己 Parsing 了。

```powershell
$Html = $Html + "<h2>Slave Status</h2>"
& $mysqlexe -u $rptuser --password=$rptpass -h $ServerIP -sN -e "source .\ShowSlaveStatus.sql" > out.html

if ((Get-Item .\out.html).length -gt 0)
{
$Html = $Html + "<table>
<tr>
<th>Master Host</th>
<th>Master Log File</th>
<th>Relay Master Log File</th>
<th>Slave IO State</th>
<th>Last Errno</th>
<th>Last Error</th>
<th>Last IO Errno</th>
<th>Last IO Error</th>
<th>Last SQL Errno</th>
<th>Last SQL Error</th>
</tr>"

$stat = [string[]](Get-Content .\out.html)

$Html = $Html + "<tr><td>" + $stat[2] + "</td><td>" + $stat[6] + "</td><td>" + $stat[10] + "</td><td>" + $stat[1] + "</td><td>"

if ($stat[19] -eq "0") {$Html = $Html + $stat[19] + "</td><td>" + $stat[20] + "</td><td>"}
else {$Html = $Html + "<div class=""Critical"">" + $stat[19] + "</div></td><td><div class=""Critical"">" + $stat[20] + "</div></td><td>"}

if ($stat[35] -eq "0") {$Html = $Html + $stat[35] + "</td><td>" + $stat[36] + "</td><td>"}
else {$Html = $Html + "<div class=""Critical"">" + $stat[35] + "</div></td><td><div class=""Critical"">" + $stat[36] + "</div></td><td>"}

if ($stat[37] -eq "0") {$Html = $Html + $stat[37] + "</td><td>" + $stat[38] + "</div></td></tr></table>"}
else {$Html = $Html + "<div class=""Critical"">" + $stat[37] + "</div></td><td><div class=""Critical"">" + $stat[38] + "</div></td></tr></table>"}
}
else {
$Html = $Html + "<span class=""Critical"">No Slave Status</span><br/>"
}
```

基本上就是分開數位子，就完成了。
