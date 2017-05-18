---
title: SQL Server Management Studio (SSMS) 碰到 Value cannot be null (值不能為 Null) 的解決方式
tags:
  - SQLServer
date: 2017-05-18 14:05:32
---
今天廠商說到他們 SSMS 登入資料庫會有 "值不能為 Null" 的問題，會造成資料表打不開。

找了一下方法，有人說是 [Temp Path](http://stackoverflow.com/questions/15249398/value-cannot-be-null-parameter-name-viewinfo) 的問題，不過後來是按照[這裡面](http://sqlanddotnetdevelopment.blogspot.tw/2014/01/error-showing-while-opening-ssms-2012.html)的方法砍掉設定檔重開就好了，紀錄一下。

真的是很奇怪的問題呀。
