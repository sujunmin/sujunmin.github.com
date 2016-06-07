---
title: 紀錄一下解決 SSL Medium Strength Cipher Suites Supported 問題
date: 2016-01-20 
tags: VMWare
---

最近幫忙解決 VMWare ESXi 老是出現的 SSL Medium Strength Cipher Suites Supported 弱點，問了前因後果與找了一下方法，不外乎如連結的方式，不過好像很難成功的樣子，這邊紀錄一下實際上怎麼做的，給需要的人參考。

- SSH 連到該台 ESXi
- 修改  /etc/vmware/rhttpproxy/config.xml
- <cipherList> 一節須修改成你想要的，譬如說 High Cipher Method 就是 +HIGH:!MEDIUM:!LOW
- <cipherList> 裡頭可用到的參數
- <cipherList> 裡頭只能調整 Cipher Suites，不能調整 Protocol
- 前頭的 TLSv1.2 要加(指所有 Protocol 會用到的 Cipher 都先加進去)
- 用:隔開，+的意思是一定要在候選 List 裡，且優先權最高，!的意思是一定不會在候選 List裡，沒寫的是在候選 List 裡，但會在 + 的後面
- 設定原則為沒寫的一定會在候選 List 裡，除非明示
- 所以  SSL Medium Strength Cipher Suites Supported 要用 +HIGH:!MEDIUM:!LOW 解決(只用HIGH，不用MEDIUM 與 LOW)
- /etc/init.d/rhttpproxy restart 
