---
title: 用 Graylog 取代 Splunk 紀錄 (下)
tags:
  - MIS
  - Networks
date: 2018-06-12 15:50:55
---

## 需求說明
有了前一篇的架設，這邊來說明要怎麼從 Splunk 把報表轉到 Graylog。


##  先前準備
因為在 Graylog 的 Web 界面是沒辦法直接拉報表，必須透過 Graylog Restful API 來做介接取得資料後再自己 Render 出來，所以以下的基本知識需要先了解。

1. [REST/RESTFul API 介紹](https://ithelp.ithome.com.tw/users/20091343/ironman/762)，怎麼操作 API
2. [Lucene Query Syntax](https://blog.csdn.net/caisini_vc/article/details/53377756)，最重要的核心，如何去找到你要的東西
3. 報表系統使用的平台，必須要能夠吃 Graylog 產生出來的 JSON

因為在 [SQL Server](https://github.com/sujunmin/SQLServerTraceFileParser)、[MariaDB](https://github.com/sujunmin/MariaDBDailyCheckScripts) 的經驗，這次還是選擇 Windows 上面的 Powershell 來實做報表。


### Graylog RESTFul API
[官方文件](http://docs.graylog.org/en/2.4/pages/configuration/rest_api.html)就是只叫用戶到 System/Node 底下的 API Browser 去看，說實在的，就只是這樣而已，因此大部分的時間都在在這個用 Swagger 做出來的 API Browser 上。

登入帳號密碼以後可以看到列表，點開每一個項目會有說明，下面的圖應該就是寫報表的主體。

![](https://raw.githubusercontent.com/sujunmin/sujunmin.github.com/master/test/graylog_3.png)

點開一個方法就會有每個方法的介紹。

![](https://raw.githubusercontent.com/sujunmin/sujunmin.github.com/master/test/graylog_4.png)

其中幾個重要的點。

1. Implement Note: 注意說明跟參數格式
2. Response Class: 回傳資料格式
3. Parameters: 參數，注意必須項目
4. Try It Out: 我覺得最棒的項目了，直接試一次，會有以下的結果

![](https://raw.githubusercontent.com/sujunmin/sujunmin.github.com/master/test/graylog_5.png)

1. Request URL: 就是後續用一樣的連結就可以得到一樣的東西，可以加到報表程式裡
2. Response Body: 回應資料，精華所在，測試的時候可以先把他放在檔案裡供程式測試，等到上線測試再走 Request URL。

### 回到搜尋
大家在看到之前的 Search API 的時候，實在是太多了，到底要用哪一個?

其實這個是見仁見智的，要看需求是什麼，但是我覺得 Graylog 開了很多一樣結果的不一樣 API，應該就是要怎麼樣都能搜尋吧 XD

1. 如果很明確的時間就 `/Search/Absolute`
2. 如果不明確的時間 (昨天，兩天前)就 `Search/Keyword`
3. 如果你已經在 Graylog GUI 存了一個覺得不賴的報表，想再叫出來用，就 `Search/Saved`
4. ...

每個項目還有對於原來的值 (`absloute`)，簡單統計(`histogram`，`stats`)都能得到想要的，都要試試看才會知道。

###  Splunk 報表轉到 Graylog
這邊就拿一個最常用跟最基本來分享給大家。

#### 原來的 Splunk 報表
有一個功能是每天凌晨零點寄送某個帳號每一臺機器的登入登出統計，在 Splunk 是這樣做的

![](https://raw.githubusercontent.com/sujunmin/sujunmin.github.com/master/test/graylog_6.jpg)

#### 分析

在查詢一下他的語法以後，我們可以透過`/Search/Absolute`來拿資料 (程式碼請參考[這個](https://gist.github.com/sujunmin/d606bfdfd17e7207eaef48dd0cc66d54))

1. 透過 `/Search/Absolute` 拿昨天的資料
2. 如果 `total_results` 沒東西就結束
3. 反之存一份 JSON 當附件
4. 拿出所有的 `messages.message.full_message`
5. 拿到 IP
6. 放到一個 Dictionary 裡面統計
7. 輸出

以下來說明一下實際上怎麼做

```powershell
...
$UserToken = "token"
$base64AuthInfo = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes(("{0}:token" -f $UserToken)))
...
$rawdata = (Invoke-WebRequest -Uri $uri -Method Get -Headers @{"Content-Type"="application/json";"Authorization" = ("Basic {0}" -f $base64AuthInfo)}).Content
...
```

這個部份是認證，因為有限制系統使用者存取，所以要擺認證資料進去。

可以跟官方文件的資料一樣，送帳號密碼進去也行，但是寫在 Script 裡總是怪怪的。

我們可透過新增 Session Token 的方法來傳送驗證。

可以透過 curl 或是 API Browser 直接來新增 (`Users:/users/{username}/tokens/{name}`)，username 是帳號名稱，name 就是隨便取的好記名字 (例如 report)。

![](https://raw.githubusercontent.com/sujunmin/sujunmin.github.com/master/test/graylog_7.png)

會得到一個 token，就可以拿進來用了。

另外這邊使用到 `Invoke-WebRequest`，為什麼不用 `Invoke-RestMethod` 呢? 因為 `Invoke-RestMethod` 只會取[第一層](https://stackoverflow.com/questions/42889221/invoke-restmethod-in-powershell-only-returning-inner-entry-elements-and-not-feed)的緣故，所以透過 `Invoke-WebRequest` 拿資料。

```powershell
...
$resultcount = ($rawdata | ConvertFrom-Json).total_results
...
$test = $rawdata | ConvertFrom-Json | select -expand messages | select -expand message | select full_message
...
```

這邊就是取實際資料出來的樣子，如果是從檔案(測試資料)出來的，記得再 Convert JSON 先。

```powershell
...
$resultcount = ($rawdata | ConvertTo-Json | ConvertFrom-Json).total_results
...
$test = $rawdata | ConvertTo-Json |  ConvertFrom-Json | select -expand messages | select -expand message | select full_message
...
```

接下來分析 IP，從`Original Address=` 拿出來。用個 Dictionary 統計 (沒有的新增，有的加 1)
```powershell
$test | Foreach-Object {
      $test3 = [regex]::Matches($_.full_message, "Original Address=([^\s]+)")
      $test4 = $test3[0].value.split("=")
      $d[$test4[1]] = $d[$test4[1]] + 1;
}
```

顯示結果與印出來。

```powershell
 $outputdata = "Hi all, <br> $_ 在 " + ('{0:yyyy-MM-dd}' -f $reportdate) + " 事件數量紀錄如下<br/>" 
    $d | Format-Table  @{L='Host(s)';E={$_.key}}, @{L='Event Count(s)';E={$_.value}} -auto | out-file ".\ooo.txt"
    Get-Content ".\ooo.txt" | Foreach-Object {
       $outputdata = $outputdata + $_
       $outputdata = $outputdata + "<br/>"

    }
   
    $outputdata = $outputdata + "<br/>詳細請參考附件資料。"
    
    $d.Clear()

    Send-MailMessage -To $MailTo -From $MailFrom -Subject (('{0:yyyy-MM-dd}' -f $reportdate) + " $_ 事件紀錄統計") -Body "$outputdata" -BodyAsHtml -SmtpServer $MailServer -Encoding ([System.Text.Encoding]::UTF8) -Attachments ".\$_.log"

```

然後設排程跑這個 Script，結果應該是這樣。

![](https://raw.githubusercontent.com/sujunmin/sujunmin.github.com/master/test/graylog_8.jpg)

這個只是個簡單的報表，當然可以再加點 HTML/CSS，不過先知道要做什麼，拿到資料，後面做報表就不是很大的問題了。


