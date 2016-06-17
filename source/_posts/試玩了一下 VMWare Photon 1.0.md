---
title: 試玩了一下 VMWare Photon 1.0
tags:
  - VMWare
date: 2016-06-17 17:39:25
---
今天早上看到[這個][1]，想說有空來試玩一下，以下是我的心得。

1. [官網][2]。
2. 我是下載 [Photon OS, Version 1.0 — OVA with virtual hardware v11][3]，懶得自己裝了。
3. 用 VMWare Workstation 匯入，看起來蠻小的 (2G Ram, 1 Core)。
4. 跑起來以後會先設定 root 密碼 (預設是 **changeme**，登入以後馬上會修改)。
5. 預設有開 ssh，可用其他程式連線。
6. **Yum** 系的 package manager，但是改成了 **tdnf** 。
7. [設定 IP][4] /etc/systemd/network/10-dhcp-en.network 裡頭修改 [Network][5]。
8. 開始玩 docker，[Q. I just booted into freshly installed Photon OS instance, why isn't "docker ps" working?][6]
9. 啟動 docker 與 port exposed。
 ```Bash
    docker run -it -p 8000:8000 microsoft/dotnet:latest
 ```
10. 在上面起 asp.net core 1.0 程式，推荐[這個][7]，記得 Program.cs 裡頭要多加東西再 build。
 ```Bash
    .UseUrls("http://conatiner_ip:8000")
 ```
11. 用瀏覽器測試一下。
12. 基本上啟動還蠻快的，kernel 不大，不知道匯到 vSphere/Esxi 上有沒有什麼神奇功能，有時間再試試看。

[1]: http://blogs.vmware.com/cloudnative/vmwares-photon-os-1-0-now-available/
[2]: https://vmware.github.io/photon/
[3]: https://bintray.com/artifact/download/vmware/photon/photon-custom-hw11-1.0-13c08b6-GA.ova
[4]: https://github.com/vmware/photon/issues/205
[5]: http://www.linuxfromscratch.org/lfs/view/systemd/chapter07/network.html
[6]: https://github.com/vmware/photon/wiki/Frequently-Asked-Questions#q-i-just-booted-into-freshly-installed-photon-os-instance-why-isnt-docker-ps-working
[7]: https://www.sesispla.net/blog/language/en/2016/05/running-asp-net-core-1-0-rc2-in-docker/
