---
title: 在 Pinebook 上安裝 Visual Studio Code
tags:
  - Pinebook
  - Ubuntu
date: 2017-07-02 23:53:43
---
[Visual Studio Code](https://code.visualstudio.com/)是一個很好用的工具，剛好看到[論壇](https://forum.pine64.org/showthread.php?tid=4491)有人如何安裝，但是實際上卻碰釘子了，因此做這篇文章來紀錄如何解決。 

# 原來的方式
文章的意思就是如下
1. 設定 MultiArch
2. 到網站下最新的安裝包
3. 安裝它

在 3 的地方發生了一個很嚴重的問題
```
> sudo dpkg -i code-oss_1.14.0-1497990172_armhf.deb 
[sudo] password for sujunmin: 
選取了原先未選的套件 code-oss:armhf。
（讀取資料庫 ... 目前共安裝了 193100 個檔案和目錄。）
準備解開 code-oss_1.14.0-1497990172_armhf.deb ...
解開 code-oss:armhf (1.14.0-1497990172) 中...
dpkg: 因相依問題，無法設定 code-oss:armhf：
 code-oss:armhf 相依於 apt.

dpkg: error processing package code-oss:armhf (--install):
 相依問題 - 保留未設定
Processing triggers for desktop-file-utils (0.22-1ubuntu5.1) ...
Processing triggers for bamfdaemon (0.5.3~bzr0+16.04.20160824-0ubuntu1) ...
Rebuilding /usr/share/applications/bamf-2.index...
Processing triggers for mime-support (3.59ubuntu1) ...
處理時發生錯誤：
 code-oss:armhf
```

然後要使用 `sudo apt install -f` 來處理的時候

```
> sudo apt install -f
正在讀取套件清單... 完成
正在重建相依關係          
正在讀取狀態資料... 完成
正在修正相依關係... 完成
下列的額外套件將被安裝：
  apt:armhf
建議套件：
  aptitude:armhf | synaptic:armhf | wajig:armhf apt-doc:armhf python-apt:armhf
下列套件將會被【移除】：
  apt apt-utils
下列【新】套件將會被安裝：
  apt:armhf
【警告】：下列的基本套件都將被移除。
除非您很清楚您在做什麼，否則請勿輕易嘗試！
  apt
升級 0 個，新安裝 1 個，移除 2 個，有 0 個未被升級。
1 個沒有完整得安裝或移除。
需要下載 1,014 kB 的套件檔。
此操作完成之後，會空出 1,042 kB 的磁碟空間。
您所進行的操作可能會帶來危險。
請輸入 'Yes, do as I say!' 這個句子以繼續進行
 ?] 
```

天阿，竟然會移除掉 `apt`，那時想這只是不同的 arch 應該還是可以用，結果就繼續下去，變成更多的 error

```
> sudo apt upgrade
正在讀取套件清單... 完成
正在重建相依關係          
正在讀取狀態資料... 完成
您也許得執行 'apt-get -f install' 以修正這些問題。
下列的套件有未滿足的相依關係：
 account-plugin-facebook : 相依關係: libaccount-plugin-generic-oauth 但它卻尚未安裝或
                                         ubuntu-system-settings-online-accounts 但它卻尚未安裝
 account-plugin-flickr : 相依關係: libaccount-plugin-generic-oauth 但它卻尚未安裝或
                                       ubuntu-system-settings-online-accounts 但它卻尚未安裝
 account-plugin-google : 相依關係: libaccount-plugin-google 但它卻尚未安裝或
                                       ubuntu-system-settings-online-accounts 但它卻尚未安裝
 adduser : 相依關係: perl-base (>= 5.6.0) 但它卻尚未安裝
           相依關係: passwd (>= 1:4.1.5.1-1.1ubuntu6) 但它卻尚未安裝
 adwaita-icon-theme : 相依關係: libgtk-3-bin 但它卻尚未安裝
                      相依關係: librsvg2-common 但它卻尚未安裝
 alsa-base : 相依關係: kmod (>= 17-1) 但它卻尚未安裝
             相依關係: udev 但它卻尚未安裝
...
```

幾乎整個系統都要轉成 `armhf` 架構了，真糟糕。

# 解決的方法
中間有試過很多方法(重編，安裝 arm64 版本...)都失敗。最後還是想到這樣的方式解決。

首先要把 `apt:arm64` 裝回來，然後確定 apt database 沒問題以後，解開下載的安裝包，手動放在該放的目錄底下。

```
> dpkg -x code-oss_1.14.0-1497990172_armhf.deb code
> cd code
> sudo cp -R usr/* /usr/
> sudo ln -s /usr/share/code-oss/code-oss /usr/bin/code-oss
> sudo wget https://raw.githubusercontent.com/stevedesmond-ca/vscode-arm/master/code.png -O /usr/share/code-oss/resources/app/resources/linux/code.png 
```

最後一個因為我還是比較喜歡 Windows 上面的 Icon，不一定要改。

接著把它加入到 WM 的功能表裡就完成了。

你可以加入他們家的 repository，不是要真的更新，而是有更新了可以知道，然後用這樣的方式更新 XD

```
> curl -s https://packagecloud.io/install/repositories/headmelted/codebuilds/script.deb.sh | sudo bash
```
