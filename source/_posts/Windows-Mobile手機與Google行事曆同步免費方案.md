---
title: Windows Mobile手機與Google行事曆同步免費方案
tags:
  - Windows Mobile
date: 2009-11-29 19:35:42
---

我的手機(CHT9100)最常用的功能就是看行事曆，因為事情實在太多了。之前都是用迷版的[Oggsync](http://oggsync.com/)來做同步，不過自從Google行事曆的Protocols[改變](http://oggsync.com/index.php/info/129)後，我的手機就只能同步行事曆內容，但是時間都不對(應該是對不到)。一直有一段時間無法同步行事曆。直到在網路上找到[NuevaSync](https://www.nuevasync.com/)的免費服務，我的手機又活了過來。

現在Windows Mobile手機要與Google行事曆同步，我找到的大概有下列幾個方式：

*   使用[Google Sync](http://www.google.com/mobile/products/sync.html#p=winmo)服務。[Google Sync](http://www.google.com/mobile/products/sync.html#p=winmo)使用Microsoft Exchange Service來做服務，手機端使用[ActiveSync](http://www.google.com/support/mobile/bin/answer.py?answer=138636&amp;topic=14299)來更新，不過有他的[限制](http://www.google.com/support/mobile/bin/answer.py?hl=en&amp;answer=139652)：
<table border="0" style="text-align: left;">
<tbody>
<tr>
<td style="padding-left: 30px;">
_Google Sync for Windows Mobile uses the Microsoft Exchange ActiveSync protocol to synchronize the data on your phone with your Google Account. Google Sync currently supports synchronization with your Gmail, **<span style="background-color: #ffd700;">main Google Calendar</span>**, and the contacts in your My Contacts group._ </td>
</tr>
<tr>
<td style="padding-left: 30px;">
也就是只有Default那個行事曆會被同步，這對我來說(因為各專案或是其他事情有不同的行事曆)，不是太方便，所以不能用(但iphone與黑莓機用戶是可以同步全部的，真是氣人)。</td>
</tr>
</tbody>
</table>

&nbsp;

*   使用[Oggsync](http://oggsync.com/)的產品(beta到[4.21](http://oggsync.com/beta/OggSync.CAB)，stable是4.19.4)來同步，不過Free版也是[只能](http://oggsync.com/index.php/windows-mobile-documentation/detailed-feature-chart/)同步一個行事曆(Default那一個)，Pro版可以同步很多個。

<table border="0" style="text-align: left;">
<tbody>
<tr>
<td style="padding-left: 30px;">

*   _...._
*   **<span style="background-color: #ffd700;">_Sync Multiple Calendars (Pro version)_</span>**
*   _..._&nbsp;
</td>
</tr>
<tr>
<td style="padding-left: 30px;">
至於Pro版要多少錢?OggSyncProYearly每年要$29.95USD...不太便宜，每年還要繳錢，有一些[優惠](http://oggsync.com/volume.html)啦，不過我不需要那麼多，不過我是覺得很蠻不錯的服務(我都拿它加[MortScript](http://www.sto-helit.de/index.php?module=page&amp;entry=ms_x.erview&amp;action=view)就可以做排程與開關網路了)。</td>
</tr>
</tbody>
</table>
&nbsp; 

&nbsp;

*   [GooSync](http://www.goosync.com/)也是根據你的機子是哪一個，灌對應的軟體，之前有免費版的，不過現在只有[Lite版跟Premium版](http://www.goosync.com/Features.aspx)，Lite版比較便宜，但是只能同步一個，Premium版可以同步很多個，但是終身的要&pound;39.95，真是很貴。

&nbsp;

*   Google行事曆-&gt;Microsoft Live行事曆-&gt;Windows Mobile Live Service同步，用ical對傳，不過現在Windows Mobile Live Service還沒支援行事曆同步([這篇](http://social.microsoft.com/Forums/en-US/windowsmobilesuggestions/thread/0fd2a6f3-15b0-4ebd-8dd1-6b7f7994924a)，當然下面的Post直接酸微軟這種事情也不做)。

&nbsp;

*   使用Google行事曆的ical同步(因為我不會改手機端的資料，所以只要讀就可以了)，我找到[GCalSync](http://www.gcalsync.com/)，[GMobileSync](http://rareedge.com/gmobilesync/)，[iCalParse](http://ntbab.dyndns.org/apache2-default/seite/index.htm)，其中[iCalParse](http://ntbab.dyndns.org/apache2-default/seite/index.htm)是有常更新的程式，不過最新版的Google行事曆Protocol還是不支援，一堆錯誤訊息(還沒法控制要轉多少事件，害的我手機要轉下8xx個事件，都當機好幾次)。

&nbsp;

*   在網路上找ical同步的資料，忽然看到不知道哪篇說為何不使用[NuevaSync](https://www.nuevasync.com/)來做同步，想說連過去看看，結果看到竟然支援11個行事曆同步，實在太高興了，因為我的還沒那麼多，趕快來試試，現在在我的手機配合[MortScript](http://www.sto-helit.de/index.php?module=page&amp;entry=ms_x.erview&amp;action=view)就可以排程同步了，希望這個免費服務可以一直下去。

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; [![](http://e.share.photo.xuite.net/retsamsu/1e23294/3423409/139392739_m.jpg)](http://photo.xuite.net/_r9009/retsamsu/3423409/54.jpg)

這篇的剩下部分，就是使用[NuevaSync](https://www.nuevasync.com/)的心得，供大家參考(以下圖片皆各所屬公司所有)。

首先先點[Get a free account](https://www.nuevasync.com/PublicSite/self-signup.htm)，填完就按Submit(他的辨識圖說實在真難看懂)。

[![](http://e.share.photo.xuite.net/retsamsu/1e2329f/3423409/139393774_m.jpg)](http://photo.xuite.net/_r9009/retsamsu/3423409/55.jpg)
接著你的信箱會收到確認信，點選裡面的連結完成認證。

&nbsp;[![](http://e.share.photo.xuite.net/retsamsu/1e23266/3423409/139394229_m.jpg)](http://photo.xuite.net/_r9009/retsamsu/3423409/56.jpg)

接著到[這邊](https://www.nuevasync.com/PublicSite/app-login.htm)登入，打入你的帳號密碼，登入，進入以後你可以對你的行事曆做設定(我的是已經設好了)。可以看得出來，他的服務不只有同步行事曆一項，還有聯絡人、Email、Tasks等。

[![](http://e.share.photo.xuite.net/retsamsu/1e23286/3423409/139394773_m.jpg)](http://photo.xuite.net/_r9009/retsamsu/3423409/57.jpg)

首先選擇Calendar的change，會請你選是要同步哪邊的行事曆，免費版只有Google可以選。

[![](http://e.share.photo.xuite.net/retsamsu/1e2321f/3423409/139395182_m.jpg)](http://photo.xuite.net/_r9009/retsamsu/3423409/58.jpg)
(以下部分流程為與Google要求同意連結的過程，以聯絡人同步來做展示，圖上的是聯絡人)接下來是選要連結的Google帳號([xxx@gmail.com](mailto:xxx@gmail.com))，輸入完成後，點擊Request Account Access。然後會看到Google的認證網頁，選一個你要連結的Google網站，再確認是否要連結，就完成認證連結了。

[![](http://e.share.photo.xuite.net/retsamsu/1e2321f/3423409/139395950_m.jpg)](http://photo.xuite.net/_r9009/retsamsu/3423409/59.jpg)
[![](http://e.share.photo.xuite.net/retsamsu/1e23284/3423409/139396051_m.jpg)](http://photo.xuite.net/_r9009/retsamsu/3423409/60.jpg)

按下Continue以後，回到首頁，選擇Setup，設定你要同步的行事曆，按下Submit就完成需同步的行事曆設定。

[![](http://e.share.photo.xuite.net/retsamsu/1e23217/3423409/139396454_m.jpg)](http://photo.xuite.net/_r9009/retsamsu/3423409/61.jpg)

接下來就是設定手機上的ActiveSync[設定](https://www.nuevasync.com/PublicSite/user/device-setup-help.htm)。首先打開ActiveSync，選擇功能表的新增伺服器來源，首先先打入Google帳號([xxx@gmail.com](mailto:xxx@gmail.com))，記得**<span style="background-color: #ffd700;">嘗試自動偵測Exchange Server設定<span style="font-size: 18pt;">不要</span>打勾</span>**。打完選下一步。

[![](http://e.share.photo.xuite.net/retsamsu/1e2321a/3423409/139399529_m.jpg)](http://photo.xuite.net/_r9009/retsamsu/3423409/62.jpg)

接下來輸入Exchange Server位址：[**<span style="background-color: #ffd700;">www.nvevasync.com</span>**](http://www.nvevasync.com)，下一步繼續(SSL要記得打勾，這是預設的)。

[![](http://e.share.photo.xuite.net/retsamsu/1e232f2/3423409/139399745_m.jpg)](http://photo.xuite.net/_r9009/retsamsu/3423409/63.jpg)

接著輸入之前申請的帳號(不是Email的另一個，Username)，密碼，網域打**<span style="background-color: #ffd700;">notused</span>**，按下一步，選擇同步行事曆(若你有設定其他同步，也可選一下)，按完成，就完成設定。

[![](http://e.share.photo.xuite.net/retsamsu/1e232ff/3423409/139400014_m.jpg)](http://photo.xuite.net/_r9009/retsamsu/3423409/64.jpg)
&nbsp;

接著ActiveSync會自動同步，看看結果就知道有沒有設對啦。

[![](http://e.share.photo.xuite.net/retsamsu/1e23248/3423409/139400343_m.jpg)](http://photo.xuite.net/_r9009/retsamsu/3423409/65.jpg)

配合[MortScript](http://www.sto-helit.de/index.php?module=page&amp;entry=ms_x.erview&amp;action=view)就可以排程同步+開關網路了，下面是我寫的Script(中華電信為連線的Profile，只有一個Exchange Server要連，所以直接按軟右鍵)：

If (NOT InternetConnected("[http://google.com/m](http://google.com/m)"))
&nbsp;connect("中華電信(internet)")
&nbsp;Sleep 500
EndIf
Run( "\windows\async.lnk" )
WaitForActive( "ActiveSync", -1 )
Sleep 3000
SendSpecial( "LeftSoft" )

If (InternetConnected("[http://google.com/m](http://google.com/m)"))
&nbsp;disconnect("中華電信(internet)")
&nbsp;Sleep 500
EndIf

最後還是希望這個服務能夠一直下去，要不然我的手機又要到末日了。

* * *

*   留言者: heavengate
*   Email:*   網址:*   日期: 2009-12-10 11:23:33

唉呀呀

果然是不行

超可惜

&nbsp;

不過這樣我也很滿足囉!

&nbsp;

沒錯~I phone可以~

而且是像google行事曆一樣~

直接各種行事曆,各種顏色Show出來呢!

![](http://s.blog.xuite.net/_image/emotion/hastart/m145.gif)

* * *

*   留言者: 小蘇
*   Email:*   網址:*   日期: 2009-12-10 21:19:53

是阿，iphone用戶實在很棒~

* * *

*   留言者: heavengate
*   Email:*   網址:*   日期: 2009-12-08 06:11:40

哈囉~你好
 首先感謝你發的文章
 教我使用NuevaSync
 我的HD2現在可以看到很多本行事曆的同步了

 說真的...11本已經足夠
 所以可以免費使用
 真的很開心

 這邊想請教你的是
 1.
 我有辦法看到....哪一個行程是哪一本行事曆的內容嗎?!
 2.
 我要開關不同的行事曆是不是一定要透過網路上setup?!

 麻煩您了!

![](http://s.blog.xuite.net/_image/emotion/mazu2/m22.gif)

* * *

*   留言者: 小蘇
*   Email:*   網址:*   日期: 2009-12-08 09:07:31

如果能同步就好～

1\. 好像不行(因為類別沒Sync，聽說iphone好像可以?)。

2\. 對，沒錯，因為他是同一個exchange server輸出的，所以要在網路上設定。

* * *

*   留言者: sevenjay
*   Email: ad1@sevenjay.tw
*   網址:*   日期: 2009-12-02 17:58:24

感謝經驗分享。