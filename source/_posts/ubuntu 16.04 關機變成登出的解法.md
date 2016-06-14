---
title: Ubuntu 16.04 關機變成登出的解法
date: 2016-06-14 11:15:44
tags: Ubuntu
---

現在家裡的機器與公司的工作機大多是用 Ubuntu 來作業的(公司的工作機開 VM)，安裝完 Ubuntu 16.04 後就安裝了 [Macbuntu][1] 妝點一下桌面，結果發現到原來右上角的關機鍵變成了登出了。

![選單](https://docs.google.com/uc?id=0B6HWfJSgyadTcW9JeU1YYkZqbFU "選單")

原來以為是主題的關係，更新了幾次還是這樣，今天就想來抽絲剝繭一下問題到底在哪。

在經過 Google 的處理(XD)以後，發現到是中間的 Plank Docker 衝到系統了，所以要[延遲][2]系統登入時自動執行 Plank 的時間。

在 `~/.config/autostart/plank.desktop` 裡頭裡加入一段 `X-GNOME-Autostart-Delay=20`

其中 20 表示延遲 20 秒載入，這樣就會恢復可以關機了。

[1]: http://www.noobslab.com/2016/04/macbuntu-1604-transformation-pack-for.html
[2]: https://askubuntu.com/questions/621732/shutdown-button-only-logs-out-ubuntu-15-04/655437
