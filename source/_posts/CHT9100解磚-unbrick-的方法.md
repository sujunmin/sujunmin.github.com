---
title: CHT9100解磚(unbrick)的方法
tags:
  - Windows Mobile
date: 2009-07-23 12:04:07
---

昨天同事義欽兄問我之前CHT9100刷WM6.5的ROM資訊，我就給他[這個](http://www.mobile01.com/topicdetail.php?f=224&amp;t=539086&amp;p=1)，想到我之前也抓了一個WM6.5的最新ROM還沒刷，就拿來刷看看

刷完以後覺得不賴，用之前[SPB Backup](http://www.spbsoftwarehouse.com/products/backup/?en)備份的資料還原回去，因為以為都差不多吧，是用全系統還原回去

結果還原以後是很像之前的沒錯啦，不過因為ROM跟Flash上面太多檔案重複了(Windows下面有的程式其實之前也有灌過，64M的Flash用到只剩下2M![](http://s.blog.xuite.net/_image/emotion/m20.gif))

想說再重新刷一次，下次不要再全系統還原回去(為什麼那時候不用Hard Reset呢![](http://s.blog.xuite.net/_image/emotion/m4.gif))，可能是因為電量不足吧，刷到一半就當機重開了![](http://s.blog.xuite.net/_image/emotion/m14.gif)

接下來就是慘痛的開始，怎麼刷機都沒法刷進去，Soft/Hard Reset也沒用，無法安裝程式，整個手機只能打電話跟用ROM裡面的程式![](http://s.blog.xuite.net/_image/emotion/m11.gif)

上網找Solution([Google查trinity unbrick](http://lmgtfy.com/?q=trinity+unbrick))，其中有一個

[HTC Trinity Stack Overflow exploit - how to unbrick any Trinity stuck in bootloader!!](http://forum.xda-developers.com/showthread.php?t=308691)

這個，寫到如何解磚(unbrick)的方法，二話不說就下載來用

我是下載[.Net](http://forum.xda-developers.com/attachment.php?attachmentid=43847&amp;d=1184390360)的版本，用[splxploit-windows.zip](http://forum.xda-developers.com/attachment.php?attachmentid=43539&amp;d=1184192788)的nb檔(TRIN_HardSPL.nb)，以下就是我的方法：

1.  CHT9100接上電腦，開三色模式
2.  選Connect，他會說Connected
3.  仔細看一下三色螢幕上面的SPL版本，先記起來(我的是SPL-3.08.00)
4.  接下來選Exploit!，會跑一下，訊息會出現在下面，因為你的SPL有問題，所以他會比對一下，比對完會跳一個確認視窗，叫你選要安裝的SPL nb
5.  選擇TRIN_HardSPL.nb(剛剛說的那個)
6.  接下來他會安裝，安裝完如果沒有當掉的話<span style="color: #fe0a00; font-size: 24pt;">不要</span>重開機，看看妳的SPL版本是不是變了(變成SPL-1.30.Olipro)，反正就跟剛剛不一樣就是了，接下來就直接進行ROM的刷機動作(這邊我試好幾次![](http://s.blog.xuite.net/_image/emotion/m16.gif)，開機完就會恢復成原來的SPL)
7.  完成刷機(SPL也會因為你刷的ROM變化)，解磚了![](http://s.blog.xuite.net/_image/emotion/m10.gif)

如果上面的檔案沒法下載，這裡也有[SPL Exploit Tool(.Net版)](https://googledrive.com/host/0B6HWfJSgyadTRndkdHZiSk1sbzA/SPL Exploit Tool.zip)

還是希望以後能夠不要再變磚了![](http://s.blog.xuite.net/_image/emotion/m10.gif)![](http://s.blog.xuite.net/_image/emotion/m10.gif)![](http://s.blog.xuite.net/_image/emotion/m10.gif)![](http://s.blog.xuite.net/_image/emotion/m10.gif)