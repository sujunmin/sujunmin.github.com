---
title: Office 365 移除部分功能的方法
date: 2016-04-07 
tags: MIS
---

當使用 Office 365 的時候，不能像以前安裝 Office 一樣可以選擇想要的功能，一股腦的全裝進去，有時候看到不需要的功能在那邊蠻礙眼而且會誤點，這裡查到一個方法可以選擇想要的功能。

- 到[這邊][1]下載需要的 Office Deployment Tool。
- 下載下來可以點兩下解壓縮到想要的地方。
- 下載的連結裡有提到如何在安裝前就設定好，因為我已經安裝了，所以選擇安裝後要移除的方法 (Remove programs from computers that already have Office 365 ProPlus installed)。
- 解壓縮檔案裏頭有個 configuration.xml，裏頭可以設定要移除的功能。
- OfficeClientEdition 如果安裝的是 64 位元 Office，這裡要改成 64。
- 現在的版本是 2016，如不想升級先看看自己的 Office 版本號，再加到 Version 裡，如 Version="15.1.2.3"。
- Language ID=zh-tw。
- 加入 <ExcludeApp ID="XXX” />，其中 XXX 為功能名稱。
- 改好以後儲存，開 cmd (系統管理員模式)。
- setup.exe /configure configuration.xml。
- 等程式跑完就完成了。

[1]: https://technet.microsoft.com/en-us/library/dn745895.aspx
