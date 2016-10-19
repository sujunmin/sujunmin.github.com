---
title: Trace File 挖寶記
tags:
  - MSSQL
date: 2016-10-19 14:53:47
---

昨天下午收到一封告警訊息，發現到是一個從來沒有在告警訊息中出現的帳號 (datareader/datawriter, dbo login)，覺得很奇怪，不知道是不是又被 SQL Injection 攻擊了，於是有了這篇紀錄說明如何從 Trace File 挖寶。

<img src=https://raw.githubusercontent.com/sujunmin/sujunmin.github.com/master/test/eventclass_118_1.png />

這篇的結果不是那麼重要，但是怎麼從 Trace File 找到想要的資訊我覺得還蠻重要的。

基本上 TextData 應該都會有 Query 資訊，大部份都能馬上判定是什麼狀況，但是像這種沒有 TextData 的就要直接去看看 Trace File 裡頭的資訊了。

Event Class 是 118，[Audit Object Derived Permission Event](https://msdn.microsoft.com/en-us/library/ms190802.aspx)。

裡頭寫到重要的資訊，就是欄位編號，名稱，跟內容。

從 [Log Server](https://github.com/sujunmin/SQLServerTraceFileParser) 拿出所要的 csv 檔，取出需要的部份 (如果原來的太大了的話)。

<img src=https://raw.githubusercontent.com/sujunmin/sujunmin.github.com/master/test/eventclass_118_2.png />

一些重要的欄位

| 欄位號碼 | 名稱 | 內容 |
|---------|-----|------|
|8|HostName|Name of the computer on which the client is running. This data column is populated if the host name is provided by the client. To determine the host name, use the HOST_NAME function.|
|10|ApplicationName|Name of the client application that created the connection to an instance of SQL Server. This column is populated with the values passed by the application rather than the displayed name of the program.|
|11|LoginName|Name of the login of the user (either the SQL Server security login or the Microsoft Windows login credentials in the form of DOMAIN\username).|
|14|StartTime|Time at which the event started, if available.|
|21|EventSubClass|Type of event subclass.<br/>1=Create<br/>2=Alter<br/>3=Drop<br/>4=Dump<br/>11=Load|
|23|Success|1 = success. 0 = failure. For example, a value of 1 indicates success of a permissions check and a value of 0 indicates failure of that check.|
|26|ServerName|Name of the instance of SQL Server being traced.|
|27|EventClass|Type of event = 118.|
|34|ObjectName|Name of the object that is being created, altered, or dropped.|
|35|DatabaseName|Name of the database in which the user statement is running.|
|37|OwnerName|	Database username of the object owner of the object being created, altered, or dropped.|

其中除了帳號來源之外，最重要的就是 EventSubClass = Create，ObjectName = \_WA\_Sys_...。

所以我們知道是 Create Statistics。

看一下統計資料

<img src=https://raw.githubusercontent.com/sujunmin/sujunmin.github.com/master/test/eventclass_118_3.png />

正式當時產生的：某個 Query 需要其他類型的統計資料，所以就產生了。

總結：如果要從 Trace File 挖寶，可以這麼做

1. 找到該 EventClass 的 msdn。
2. 找出該 Event 的資料。
3. 對應 EventClass 的欄位資訊。
4. 依據結果繼續找下去。
