---
title: Ubuntu 16.04 安裝與使用 Flashcache 流程紀錄
tags:
  - MIS
  - Ubuntu
date: 2017-08-31 12:31:57
---

因為系統效能的關係，嘗試一下在 Ubuntu 16.04 下用 SSD 當硬碟的快取碟，看效能有沒有比較好，[這篇](http://wubx.net/ubuntu-flashcache/)已經寫的十分清楚，但是有一些我做起來不太一樣的，以下紀錄安裝與使用方式。

## 安裝
原文中使用原碼編譯安裝，但其實 Ubuntu 16.04 裡頭已經有這個 Package 了。

`sudo apt install flashcache-dkms flashcache-utils`

載入 flashcache

`sudo modprobe flashcache`

看有沒有成功

`sudo lsmod |grep flashcache`

開機自動載入

`echo 'flashcache' | sudo tee --append /etc/modules`

## 使用
如同原文方式，首先要確認 Partitions，原來的磁碟跟要作為快取的 SSD 磁碟。

假設原來的狀態如下

原來的磁碟 `/dev/sdb1`，UUID 為 `[UUID_sdb1]` (可從`ls -la /dev/disk/by-uuid/`查看對應) mount 在 `/test` 上

要做快取的 SSD 磁碟 `/dev/sdc1`，未來名稱為 `sdb1_cache`

1. 把 `/test` 裡頭的資料移到其他地方，因為要清空
2. 對 SSD 磁碟做 `flash_create`，這個會依照大小等一段時間

   `sudo flashcache_create -v -p back sdb1_cache /dev/sdc1 /dev/disk/by-uuid/[UUID_sdb1]`
3. 試著 mount 看看

   `sudo mount /dev/mapper/sdb1_cache /test`
4. 成功就可以試著寫寫看 (把先前移到別的地方的資料移回來)，透過 `flashstat` 可以看使用狀況
5. 如果要開機可以 mount，寫入 `/etc/fstab` 就可以了 (範例)

   `/dev/mapper/sdb1_cache /test ext4 defaults 0 0`

如果要打掉的話就
1. `/test` 的資料移動到別的地方
2. `sudo umount /test`
3. `sudo dmsetup remove sdb1_cache` 
4. `sudo flashcache_destory /dev/sdc1`
5. `/test` 的 mounting point 改回原來的 (`/dev/sdb1`)
6. 資料搬回 `/test`

## 後記
我覺得這個還蠻好用的，它是對 Partition 做 cache，都不用管實際怎麼運作就能用了，十分方便好用。
