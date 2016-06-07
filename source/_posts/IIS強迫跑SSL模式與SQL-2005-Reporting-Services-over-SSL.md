---
title: IIS強迫跑SSL模式與SQL 2005 Reporting Services over SSL
tags:
  - .Net
date: 2009-12-18 11:51:43
---

<script type="text/javascript" src="https://googledrive.com/host/0B6HWfJSgyadTUzBPMzhVbWN0TzQ/scripts/shCore.js">

</script> <script type="text/javascript" src="https://googledrive.com/host/0B6HWfJSgyadTUzBPMzhVbWN0TzQ/scripts/shBrushVb.js">

</script> <link href="https://googledrive.com/host/0B6HWfJSgyadTUzBPMzhVbWN0TzQ/styles/shCore.css" type="text/css" rel="stylesheet" /> <link href="https://googledrive.com/host/0B6HWfJSgyadTUzBPMzhVbWN0TzQ/styles/shThemeDefault.css" type="text/css" rel="stylesheet" /> <script type="text/javascript">
		SyntaxHighlighter.config.clipboardSwf = 'https://googledrive.com/host/0B6HWfJSgyadTUzBPMzhVbWN0TzQ/scripts/clipboard.swf';
		SyntaxHighlighter.all();

</script>

<div></div>

公司裡負責的系統有[Web Server Uses Plain Text Authentication Forms](http://www.nessus.org/plugins/index.php?view=single&amp;id=26194)缺失，今天想說把它解決掉。原來覺得可能很簡單的，不過變成要改一些Code，特此記錄下來供以後與網友參考。

原來的環境是這樣，某個AP Server上面跑IIS(http and https)，被nessus偵測出來某個AP的default.aspx，帳號密碼登入是用明碼傳送，可能會有資安風險。

**<span style="color: #ffffff; bgcolor: #fdbe00;"><span style="background-color: #ffff00;">Web Server Uses Plain Text Authentication Forms</span></span>**

**Synopsis :**

The remote web server might transmit credentials in cleartext.

**Description :**

The remote web server contains several HTML form fields containing
an input of type 'password' which transmit their information to
a remote web server in cleartext.

An attacker eavesdropping the traffic between web browser and 
server may obtain logins and passwords of valid users.

**Solution :**

Make sure that every sensitive form transmits content over HTTPS.

**Risk factor :**

Medium / CVSS Base Score : 5.0
(CVSS2#AV:N/AC:L/Au:N/C:P/I:N/A:N)

**Plugin output :**
Page : /css/
Destination page : Default.aspx
Input name : Login1$Password

Nessus ID : [26194](http://www.nessus.org/plugins/index.php?view=single&amp;id=26194)

因此就要去修正(不修正也不行阿)，在網路上找了一下，就決定先把IIS的80 Port(http)先拿掉，都走443 Port (https)。找到這位[大大](http://blogs.microsoft.co.il/blogs/dorr/archive/2009/01/13/how-to-force-redirection-from-http-to-https-on-iis-6-0.aspx)寫的，就來實作一下，步驟如下：

1.  先把原來的網站(通常是預設的網站那個)修改他的內容，把TCP連接埠改成81(或其他外面連不進來的Port，因為IIS一定要設定TCP連接埠，所以就設定成一個連不進來的Port)，SSL連接埠部分填443。按確定以後重啟原來的網站(預設的網站)，現在Port 80應該是不會通了，443那個應該會通(若不通應該是SSL設定問題，這邊不多贅述)。[![](http://e.share.photo.xuite.net/retsamsu/1e23247/3423409/141018774_m.jpg)](http://photo.xuite.net/_r9009/retsamsu/3423409/68.jpg)
2.  接著下來開一個新的網站，讓他聽80 Port，再轉到443 Port上面。網站說明的地方打你想要的字串就可以。TCP連接埠用Port 80就好。主目錄部分隨便找一個就行(因為等一下我們會來設redirect)，允許讀取就好(如果原來的網站有什麼，這邊就設什麼)，完成網站設定的動作。
![](http://e.share.photo.xuite.net/retsamsu/1e23250/3423409/141019039_m.jpg)
[![](http://e.share.photo.xuite.net/retsamsu/1e23252/3423409/141019041_m.jpg)](http://photo.xuite.net/_r9009/retsamsu/3423409/70.jpg)
[![](http://e.share.photo.xuite.net/retsamsu/1e23253/3423409/141019042_m.jpg)](http://photo.xuite.net/_r9009/retsamsu/3423409/71.jpg)
[![](http://e.share.photo.xuite.net/retsamsu/1e23255/3423409/141019044_m.jpg)](http://photo.xuite.net/_r9009/retsamsu/3423409/72.jpg)
[![](http://e.share.photo.xuite.net/retsamsu/1e23258/3423409/141019047_m.jpg)](http://photo.xuite.net/_r9009/retsamsu/3423409/73.jpg)
[![](http://e.share.photo.xuite.net/retsamsu/1e2325a/3423409/141019049_m.jpg)](http://photo.xuite.net/_r9009/retsamsu/3423409/74.jpg)
3.  接下來對剛剛新設的網站做設定，到主目錄的地方，選擇某個URL位置的重新導向，在導向到的地方要轉的網址(https://...)，根據需求勾選下面的選項：

    *   上面所輸入的URL：就是直接轉址，如果有旗下的AP(如http://abc/ap)，他也不會管，還是只有https://...。
    *   輸入的URL下的目錄：如果你想要轉到轉址下的某個檔案或目錄，就用這個。
    *   這個資源的永久重新導向：就是轉到妳的轉址，旗下自動對應轉址下的任何目錄(如http://abc/ap -&gt; [https://.../ap](https://.../ap))

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; [![](http://e.share.photo.xuite.net/retsamsu/1e23212/3423409/141019489_m.jpg)](http://photo.xuite.net/_r9009/retsamsu/3423409/75.jpg)

4.  選完以後重啟這個網頁，就完成了轉址動作，測試一下看有沒有什麼問題。

在我測試的時候，發現原來連結報表伺服器的部份也死了。原因是因為我的報表伺服器也是在同一台，所以http沒了自然都不能用了(找不到檔案)。所以先把報表伺服器的設定先改一下，然後再看程式要改哪邊。

選擇Reporting Services組態，對報表伺服器虛擬目錄那邊做設定，勾選需要安全通訊端層(SSL)連接，需要用於有三個選項可以選：

*   1 - 連接：只有連接的時候使用SSL。
*   2 - 報表資料：在產生報表資料傳送時使用SSL。
*   3 - 所有SOAP API：全部使用SSL(網頁處理)。

憑證名稱部分，就打你IIS用的憑證註冊網址(如abc.com.tw)，按套用就完成了。

[![](http://e.share.photo.xuite.net/retsamsu/1e2326e/3423409/141020349_m.jpg)](http://photo.xuite.net/_r9009/retsamsu/3423409/76.jpg)
來到AP系統看看，發現錯誤又多了一個。

**_基礎連接已關閉_: _無法為SSL/TLS 安全通道建立信任關係_**

這個的原因是因為我們是一個憑證很多伺服器在用，但是在連接時會檢查這個憑證是否有問題，在瀏覽器瀏覽的時候可以按掉解決，這時候就不行了。再上網找解決方案，找到這個[大大](http://sanchen.blogspot.com/2008/04/httpwebrequest-https.html#links)寫的方法，修改自己的程式。不過我是用VB.NET寫的，所以再找一下MSDN，提供解法如下。

<pre class="brush: vb;">Public Shared Function ValidateCert(ByVal sender As Object, ByVal certificate As System.Security.Cryptography.X509Certificates.X509Certificate, ByVal chain As System.Security.Cryptography.X509Certificates.X509Chain, ByVal sslPolicyErrors As System.Net.Security.SslPolicyErrors) As Boolean
       Return True
End Function
</pre>

然後在進入報表之前做下面的[ 動作](http://msdn.microsoft.com/zh-tw/library/system.net.servicepointmanager.servercertificatevalidationcallback%28VS.80%29.aspx)(每次登入只需做一次)即可。

<pre class="brush: vb;">System.Net.ServicePointManager.ServerCertificateValidationCallback = New System.Net.Security.RemoteCertificateValidationCallback(AddressOf ValidateCert)
</pre>

其實就是避掉錯誤的回傳這樣，詳情請看MSDN相關[函式](http://msdn.microsoft.com/en-us/library/system.net.security.remotecertificatevalidationcallback.aspx)介紹。

這樣就完成所有的設定了。

&nbsp;