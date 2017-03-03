---
title: 讓不支援 Web API 的程式支援並使用 LINE Notify 服務
tags:
  - Networks
date: 2017-03-03 13:31:42
---

## 需求說明
在開發某個應用的時候，使用 [LINE Notify](https://notify-bot.line.me/zh_TW/) 作為通知功能，卻發現到該架構沒有使用 Web API 的函式 (`Webrequest`, `HttpWebRequest`, `RestGet`, ...)，透過執行外部程式的方式來達成目的。

## [LINE Notify](https://notify-bot.line.me/zh_TW/)
這個服務蠻方便的，如果要作簡易的通知機器人又不想要作複雜的機器人開發，只要註冊一下就可以使用了，[Google](https://www.google.com.tw/#q=Line+Notify+%E4%BD%BF%E7%94%A8&*) 一下有很多人都有使用經驗，是個不錯的服務。

## 讓不支援 Web API 的程式支援
如果你的架構能支援使用外部程式，或是可以使用其他類 (如 C++ export function) 函式庫的話，以下的說明可以讓該架構可以使用 Web API。

我的架構中是沒有支援使用外部程式，但可以用 C++ 的外部函式庫，在 Windows 上面。

首先，我使用 Windows 都有的 `shell32.dll`。

```c
#import "shell32.dll" 
        int ShellExecuteW( int       hWnd,
                           string    lpVerb,
                           string    lpFile,
                           string    lpParameters,
                           string    lpDirectory,
                           int       nCmdShow
                           );
#import                 

```

其中 [`ShellExecuteW`](https://msdn.microsoft.com/en-us/library/windows/desktop/bb762153(v=vs.85).aspx) 是拿來執行外部程式的函數。

接下來使用 [curl](https://curl.haxx.se/download.html) 來作為發動 Http Request 的外部程式，這個 curl 支援多種平台，真的很方便。

因為環境在 Windows 上，所以下載 Windows 版的 (記得裡頭的檔案都要在同個目錄下才能運作)上面。

最後在要使用 Web API 的地方加

```c
          ...
          string cmd = " -X POST -H \"Authorization: Bearer " + LineNotifyAccessToken + "\" -F \"message=" + msg + "\" -F \"imageFile="+filename+"\" https://notify-api.line.me/api/notify"; 
		  ...
		  ShellExecuteW (0, "Open", "curl.exe", cmd, workpath, 0);
          ...
```

其中 `imageFile` 的格式是 jpeg/png ，自己電腦裡的路徑 `@C:/test/abc.png`，所以就是 `imageFile=@C:/test/abc.png`上面。

另外這個服務是每個小時有一個[上限](https://notify-bot.line.me/doc/en/)，API Rate Limit，可以參考一下。

```
F:\Test>curl -X GET -H "Authorization: Bearer <token>" https://notify-api.line.me/api/status -I
HTTP/1.1 200 OK
Server: nginx
Date: Fri, 03 Mar 2017 02:45:18 GMT
Content-Type: application/json;charset=UTF-8
Transfer-Encoding: chunked
Connection: keep-alive
Keep-Alive: timeout=3
X-RateLimit-Limit: 1000
X-RateLimit-ImageLimit: 50
X-RateLimit-Remaining: 946
X-RateLimit-ImageRemaining: 50
X-RateLimit-Reset: 1488511643
```

這樣一來就讓不支援 Web API 的程式支援了。
