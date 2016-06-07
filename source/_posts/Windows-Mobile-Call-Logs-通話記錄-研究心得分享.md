---
title: Windows Mobile Call Logs (通話記錄) 研究心得分享
tags:
  - Windows Mobile
date: 2010-04-12 14:13:36
---

這一篇分享文的由來，不是因為無聊想研究，而是因為需求而研究的，該換手機啦~~

話說到昨天(4/12)，晚上跟Bonnie一起去東區逛街，在有名的[兔子兔子](http://rabbitrabbit.com.tw/)餐廳吃飯的時候，拍了一些有趣的照片。因為Bonnie的傳輸線壞掉了，所以我們用藍芽來互傳。傳完之後呢，就去逛街。逛到一半，拿出手機來看看，好像當機了。於是就拿對付這台老CHT9100的方法，戳屁屁，可是這一次卻沒辦法一如往常的重開，而是"噹"的一聲，我的手機居然被重置了&gt;///&lt;，失望之餘趕緊找回之前的備份，也帶著失望(應該是說沒心情)回家了(Bonnie的名言：絕不能相信手機等3C用品)。

因為我每個星期都會拿[AddinTimer](http://addintimer.5d6d.com/)的備份聯絡人、簡訊、與通話記錄功能來備份系統，話說這時應該是派上用場的時候。聯絡人、簡訊都回復了，就通話記錄找不到哪邊還原，實在有夠奇怪，怎麼只能備份不能還原呢?上網找了一下，還真的是[沒法還原](http://addintimer.5d6d.com/viewthread.php?tid=658)，沒這個功能。這就很像備了一堆沒用的東西一樣，很笨蛋的感覺。

可是不死心的我找了很多資料，想要恢復通話記錄，但都是碰壁。實在是這樣的應用太少了，所以再更深入找了一下，有下面的一些結論：

*   Windows Mobile有許多的系統資料庫，如行事曆、系統排程等資料庫，有的可以看到，有的是隱藏檔。
*   應用程式透過[Pocket Outlook Object Model (POOM)](http://msdn.microsoft.com/en-us/library/aa914277.aspx)，這個包好API的Framework，來跟這些資料庫溝通。
*   所以不管是新增行事曆、修改電話簿等等作業就可以透過POOM來執行。
*   有許多已經完整的POOM實作可以使用，如[OpenNETCF](http://www.opennetcf.com/)、[Mobile In The Hand](http://inthehand.com/content/Mobile.aspx)、[.NetCF](https://blogs.msdn.com/stevenpr/archive/2004/08/05/209390.aspx)等，都可以在手機上來存取資料。
*   也有許多應用程式已經可以存取，如[SKTools](http://www.s-k-tools.com/index.html?sktools/m_feat.html)、[CeDatabase Manager](http://www.pocketgear.com/en/usd/107981,product-details.html)、[Pocket dbExplorer](http://www.phatware.com/index.php?q=product/details/dbexplorer)等。
*   不過沒有一個程式能大量匯入使用者定義的資料(From AddinTimer的備份)。
*   對於手機的程式要寫起來應該很麻煩。

中間Try and Error的過程就不說了，直接跟大家分享最後的方法：SKTools+寫程式產生Import檔。

SKTools是個很方便的Windows Mobile控管程式，會用到的是他管理資料庫的功能。

![](http://e.blog.xuite.net/e/2/3/2/11844378/blog_1638788/txt/32899274/5.png)

點進去以後就可以看到系統資料庫。今天我們要存取的是clog.db(Call Logs資料庫)。

![](http://e.blog.xuite.net/e/2/3/2/11844378/blog_1638788/txt/32899274/6.png)

可以看到一些重要的資料：屬於[EDB](http://msdn.microsoft.com/en-us/library/aa912256.aspx)格式，存在於pim.vol。

pim.vol存在系統的根目錄下，裡面放著通訊錄、行程、約會等[資料](http://www.pocketpcjunkies.com/Uwe/Forum.aspx/pocketpc-dev/21040/Reading-pim-vol-EDB-File-on-a-PC)，就像是一個磁區(Volume)一樣，所以對應如果要改其他的內容的話，就是去存取在pim.vol下的其他資料庫。

![](http://e.blog.xuite.net/e/2/3/2/11844378/blog_1638788/txt/32899274/7.png)

可以匯入也能匯出，都是xml檔。既然是xml檔，就可以來研究研究是不是可以用程式還是其他方法來加工一下，再匯入進去。xml檔的格式大概是這樣：

![](http://e.blog.xuite.net/e/2/3/2/11844378/blog_1638788/txt/32899274/8.png)

上面那沱東西，不用管他，反正保留在那就好，重要是下面的部份：

![](http://e.blog.xuite.net/e/2/3/2/11844378/blog_1638788/txt/32899274/9.png)

可以看出大概的資料結構，所幸有[這個](http://windows-tech.info/10/646502d8870f8a9b.php)跟[這個](http://www.codeproject.com/tips/68144/Modifying-the-Call-History-on-Windows-Mobile.aspx?display=Print)的幫忙，一方面是知道欄位的格式與內容，另一方面是如果是參數的話是什麼東西，大概是這樣：

*   record oid:沒關係，亂打就好，匯入會重編。
*   field 1:固定是1。
*   field 2:開始時間，這個時間是[SortableDateTimePattern](http://msdn.microsoft.com/zh-tw/library/system.globalization.datetimeformatinfo.sortabledatetimepattern%28v=VS.80%29.aspx)加個Z(與[UniversalSortableDateTimePattern](http://msdn.microsoft.com/zh-tw/library/system.globalization.datetimeformatinfo.universalsortabledatetimepattern%28v=VS.80%29.aspx)類似，真是奇怪的格式)，位數都不要補零。
*   field 3:結束時間，格式跟上面一樣。
*   field 4:來電的格式，有下面幾種，分別是有沒有在電話簿裡的來電與打的方式有代碼。
*   ![](http://e.blog.xuite.net/e/2/3/2/11844378/blog_1638788/txt/32899274/10.png)
*   field 6:電話號碼。
*   field 7:聯絡人名稱。
*   field 10:是什麼樣的電話(行動電話、家用電話還是什麼的)。
*   field 9:固定是0。

然後看看AddinTimer的通話記錄備份裡有什麼欄位。
![](http://e.blog.xuite.net/e/2/3/2/11844378/blog_1638788/txt/32899274/11.png)

*   StartTime:開始時間，就是field 1。
*   Name:如果是在電話簿裡有名字的話，會記錄名稱，field 7。
*   Number:電話號碼，field 6。
*   CallType:經過比對的結果呢(就是打給某人的比較長，就大概知道Type是什麼意思了(笑))，再根據Name就可決定field 4的內容，大概是這樣： 

    *   a是未接來電。
    *   b是已接來電。
    *   c是播出電話。

*   Duration:持續時間，與StartTime配合可以產生field 3。
*   Note:沒東西。

基本上都能配合著作，除了field 10不知道要填什麼(不填也沒關係)，field 3 = field 2 + Duration以外，就是單純的資料輸出了。

做完以後，丟到手機上，Import進去，就可以看到結果啦，完成了還原的工作，下次要記得用其他東西備份啦~