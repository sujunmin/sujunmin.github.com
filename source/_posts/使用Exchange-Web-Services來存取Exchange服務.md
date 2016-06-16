---
title: 使用Exchange Web Services來存取Exchange服務
tags:
  - .Net
date: 2010-07-19 17:30:59
---
最近公司的信箱因為申請改成了Exchange服務，信箱變大，也有比較好的介面(原來的只有200M，Mail2000的介面)。不過謎之程式(自動轉信程式)因為使用Exchange而不能使用。雖然原來使用的Email函式庫可以直接存取Exchange的信件伺服器，但也因為安全問題而無法直接存取。在網路上找呀找，最後找到[Exchange Web Services](http://msdn.microsoft.com/en-us/library/bb204119.aspx)(EWS)，試了一下又可以繼續Work了，也分享給大家。

首先使用之前，記得原來Exchange的憑證要匯入到執行程式的系統中，並且設定為受信任根憑證，要不然會無法執行。

另外[Outlook Web Access](http://www.microsoft.com/exchange/code/owa/index.html)(OWA)服務記得要先使用過一次，要不然會有奇怪無法執行的問題。我把程式給了同事，在同事的電腦上不能跑，直到上了公司的OWA，才可以跑。

開發部分從[這裡](http://omegacoder.com/?p=454)知道需安裝[Exchange Web Services Managed API](http://www.microsoft.com/downloads/details.aspx?displaylang=en&amp;FamilyID=c3342fb3-fbcc-4127-becf-872c746840e1)，開一個專案，記得要引用安裝目錄下的dll檔(Microsoft.Exchange.WebServices.Data.dll)，並且using/imports到程式裡。

接下來是程式內容的部份。原來的流程是把全部在收件夾的信件，轉到gmail去，並且在Server上不留備份。不過我們公司的EWS好像不支援寄信(擋權限)，所以就交給原來的程式內容繼續做，只需改一開始用POP3 Server接收信件的部份，變成連結EWS取信件。

```vb
Dim service As ExchangeService
service = New ExchangeService(ExchangeVersion.Exchange2007_SP1)
service.Credentials = New NetworkCredential(ChtMailForwarder.My.MySettings.Default.ExchangeUserName, ChtMailForwarder.My.MySettings.Default.ExchangeUserPassword, ChtMailForwarder.My.MySettings.Default.ExchangeDomainName)
service.Url = New Uri(ChtMailForwarder.My.MySettings.Default.ExchangeWS)
```

第2航線在有支援的是Exchange2007 SP1與Exchange 2010，Credentials填寫LDAP的帳號、密碼、與網域，Url是填寫Web Services的asmx位置。如果不知道的話，可以改寫成

```vb
service.AutodiscoverUrl(ChtMailForwarder.My.MySettings.Default.ExchangeMailAddress)
```

這樣就會根據Email去找到asmx。不過每一次都要找的話可能會花很多時間，因此還是找到Url會比較快些。接下來是找新信，方法如下

```vb
Dim itemV As ItemView
Dim findResults As FindItemsResults(Of Item)
itemV = New ItemView(ChtMailForwarder.My.MySettings.Default.ExchangeFetchCount)
findResults = service.FindItems(WellKnownFolderName.Inbox, itemV)
If findResults.TotalCount &lt;&gt; 0 Then
For Each item In findResults.Items
If mail.SendMime(fromAddr, toAddr, item.MimeContent.ToString()) = True Then
item.Delete(DeleteMode.HardDelete)
End If
Next
End If
```

這邊介紹一下：第4行使用FindItems找出在itemV中限制數量的item，信件內容會記錄在裡面。至於要抓哪一個資料夾，第一個參數就是決定要抓哪個，我選擇收件夾那個來抓。第7行是原來用SMTP寄信的程式，只是把MIME內容帶入item裡的MimeContent。如果已經寄出，就在第8行把在Server上的信件砍掉(HardDelete是直接刪除，其他像有移到刪除的資料夾或是標記為刪除等)，就完成了程式。

不過真正在測的時候，老是會當在SendMime的部分，因為MimeContent一直都沒值，但是其他的(Subject, To,...)都有。查了一下網路，[有人說](http://social.technet.microsoft.com/Forums/en-US/exchangesvrdevelopment/thread/a0fe7163-adcd-4fd6-94f1-065ee18135da)可以在FindItem後用LoadPropertiesForItems來把那些屬性讀進item裡，原來不是沒有資料，而是沒有去呼叫讀進去的函式。所以修改如下

```vb
Dim itemV As ItemView
Dim findResults As FindItemsResults(Of Item)
Dim encoding As ASCIIEncoding
itemV = New ItemView(ChtMailForwarder.My.MySettings.Default.ExchangeFetchCount)
itemV.PropertySet = PropertySet.IdOnly
findResults = service.FindItems(WellKnownFolderName.Inbox, itemV)
If findResults.TotalCount &lt;&gt; 0 Then
service.LoadPropertiesForItems(findResults.Items, New PropertySet(ItemSchema.MimeContent))
encoding = New ASCIIEncoding()
For Each item In findResults.Items
If mail.SendMime(fromAddr, toAddr, encoding.GetString(item.MimeContent.Content)) = True Then
item.Delete(DeleteMode.HardDelete)
End If
Next
End If
```

其中第5行IdOnly表示我們只讀Id，因為後來要填MimeContent，第8行LoadPropertiesForItems根據schema把資料讀到item裡，最後第11行用encoding函式轉換成MIME string。這樣就能自動轉寄信件了。