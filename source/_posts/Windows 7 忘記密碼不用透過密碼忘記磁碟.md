---
title: Windows 7 忘記密碼不用透過密碼忘記磁碟
date: 2016-01-27 
tags: MIS
---

今天幫同事解決 Windows 7 忘記密碼的問題，原來是想要用[這個][1]來解決，但是開機會 Kernel Panic @@，網路上找到的一些程式都要 $$，忽然發現到[一篇][2]寫得不賴，拿來用也成功，特別紀錄一下如何使用的。雖然覺得之前看過一樣的方法，今天算是第一次使用XD
這個方法是把 sethc.exe ([Windows 相黏鍵][3]) 功能替換成 cmd.exe (Shell)，進而使用指令修改密碼。

- 重開機到 Windows 修復
- 讓她修復一下
- 修復完成後，點選更多問題選項
- 點選最下面的 Offline Policy 說明
- 會跑出 notepad，我們拿它來替換 sethc.exe
- 開啟檔案到 Windows\system32 底下，選全部檔案
- 找到 sethc.exe，修改為sethc_bak.exe
- 在同個目錄找 cmd.exe，複製一份副本
- 把 cmd.exe 的副本修改為 sethc.exe
- 修改完畢，重開機到登入畫面
- 按 5 次 shift 會跑出 cmd 畫面
- 這個 cmd 有時間性的 (因為原來相黏鍵的說明也有時間性)，所以要趕快輸入
- 執行 net users  看帳號名稱
- 執行 net users <帳號名稱> * 改密碼
- 登入以後再把之前修改的 sethc_bak.exe  修改回來 (如不能修改可複製一份再改回原檔名 sethc.exe )
- 收工

[20170405] 更新

今天改了本機密碼以後，就三不五時帳號被鎖定，可能是有其他需要密碼的程式(例如系統排程)因為一直輸入舊的密碼導致一直被鎖定，本機是 Windows 10，跟 Windows 7 的作法比較不一樣，紀錄如下
- 登入畫面點選重開機，記得按著 Shift
- 會跑出[選擇選項]，點選[疑難排解]\[進階選項]\[命令提示字元] (連修復都不需要了)
- 開始如同之前的複製修改

  `c:\windows\system32>copy sethc.exe sethc_bak.exe`
  
  `c:\windows\system32>copy cmd.exe sethc.exe`
- 重新啟動電腦
- 接下來都跟之前一樣

[1]: http://pogostick.net/~pnh/ntpasswd/
[2]: http://www.oxhow.com/reset-windows-7-password-without-password-reset-disk/
[3]: http://windows.microsoft.com/zh-tw/windows/make-keyboard-easier-to-use#1TC=windows-7
