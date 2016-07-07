---
title: VMWare 的 VM 備份時針對沒有支援 Application Awarness 的解決方法
tags:
  - VMWare
date: 2016-07-07 13:51:09
---
最近在研究資料庫從 MSSQL 轉到 MariaDB，其中有一項備份的選擇，備份軟體 (Acronis vm Protect 9) 的 Application Awarness 不支援 MySQL/MariaDB，因此如果要移轉至 MariaDB，勢必要解決這個問題。

在這邊找到一篇[文章][1]，提到透過 VMWare Tools 備份的備份軟體 (Agent-Free) 可以在 Quiece (凍結)該 VM 前與後都可以做些什麼，這真是太好了，趕緊來測試一下。

# 測試環境
* ESXi 6.0
* 一個 Windows VM，VMWare Tools 要更新到跟該台 ESXi 一樣新
* Acronis vm Protect 9 備份環境

# 測試方式
* 在該台 VM 的 ```C:\WINDOWS``` 下新增兩個檔案，內容如下
   
   ``` C:\WINDOWS\<pre-freeze-script.bat> ``` 裡頭是 ``` echo Start > C:\backup.txt ``` (寫個東西到檔案裡)

   ``` C:\WINDOWS\<post-thaw-script.bat> ``` 裡頭是 ``` echo End >> C:\backup.txt ``` (加個東西到原來的檔案裡)
* 執行備份
* 看一下 ``` C:\backup.txt ```，裏頭應該是
  
  ``` Start ```
  
  ``` End ```
* 如此一來，就可以利用這兩個 Script 先把 Application 的資料 (Cache, Memory Data...) Sync 到 Disk 上(譬如說手動暫停服務)，就不會因為資料的關係造成備份出來的系統有問題了

* 另外這兩個 Script 的執行時間不能超過 10 分鐘，要不然會導致備份失敗 (Quiece 失敗) 

[1]:https://kb.vmware.com/selfservice/microsites/search.do?language=en_US&cmd=displayKC&externalId=1006671
