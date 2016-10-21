---
title: Ubuntu Kernel 的 Livepatch 機制 
tags:
  - Ubuntu
date: 2016-10-21 11:15:29
---

最近 Ubuntu 推出一個新的服務，Kernel 的 [Livepatch 服務](https://ubuntu.com/livepatch)，還蠻不錯的，不用重開機就可以更新 Kernel，不過還是有些限制，整理與測試如下。

* 神人的詳細[連結](http://blog.dustinkirkland.com/2016/10/canonical-livepatch.html)。
* 需要訂閱服務，免錢的有 3 個，也有付費服務([Ubuntu Advantage](https://buy.ubuntu.com/))。
* 不過我試一下超過 3 個好像也可以。
* 只有沒被修改的 Kernel 可以用。
* 被修改過的或是想要自己做 Livepatch 很[複雜](http://chrisarges.net/2015/09/21/livepatch-on-ubuntu.html)。
* 支援 x86_64，amd64。
* 支援 16.04 LTS 的 4.4 Kernel。
* 要裝 snapd，沒更新到最新就會有錯。
  <img src=https://raw.githubusercontent.com/sujunmin/sujunmin.github.com/master/test/ubuntu_livekernelpatch_04.png />
* 對象是 CVE 的 Patch，所以效能或是其他增進的還是要走老路 (update and reboot)。

首先要有個 Ubuntu SSO 帳號，登入後就可以獲得 Token 了。

<img src=https://raw.githubusercontent.com/sujunmin/sujunmin.github.com/master/test/ubuntu_livekernelpatch_01.png />

不過這個畫面沒有說到用了幾個 licenses。

接下來到想要用的機器上執行指令。

<img src=https://raw.githubusercontent.com/sujunmin/sujunmin.github.com/master/test/ubuntu_livekernelpatch_02.png />

可以看到現在正在用的 Kernel Patches。

<img src=https://raw.githubusercontent.com/sujunmin/sujunmin.github.com/master/test/ubuntu_livekernelpatch_03.png />

如果不想用就 `canonical-livepatch disable`。
