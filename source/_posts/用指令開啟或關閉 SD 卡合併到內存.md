---
title: 用指令開啟或關閉 SD 卡合併到內存
tags:
  - Android
date: 2016-09-27 20:55:22
---

今天老婆說剛買沒多久的手機又滿了，不能裝東西，想說颱風天來幫她合併 SD 卡內存，這樣她又更能盡情裝 APP 了(?)，結果系統一直顯示無法合併 (因為速度太慢)，但是其實測速以後還好阿，因此用手動的方式來設定合併內存。

這篇有用的對象如下
1. Android 6.0 以上設備
2. 會開啟 adb 跟 debug 模式
3. 不管 SD 卡的速度 (因為合併內存一定會降速度，很差的卡降更多)
4. 合併內存功能被鎖起來，或是因為速度偵測而無法合併的都可以使用

# 合併內存
1. 備份 SD 卡
2. `adb start-server`
3. `adb shell sm list-volumes all` [應該類似 public:179,32 mounted ... 這樣，後面的 179,32 等會會用到]
4. `adb shell sm set-force-adoptable true`
5. `adb shell sm partition disk:179,32 private` [這步將會把 SD 卡合併到內存，包含格式化，等了很久才完成，這時候看手機應該看不到 SD 卡了]
6. `adb shell sm set-force-adoptable false`
7. `adb shell sm list-volumes all` [應該會變成 private:179,32 mounted ... 這樣，原來的 public 不見了]
8. 重開機
9. 檢查一下，到內存那邊移轉資料，還原 SD 卡資料，完成

# 恢復成可攜式 SD 卡
1. 備份 SD 卡
2. `adb start-server`
3. `adb shell sm list-volumes all` [看 ID，179,32]
4. `adb shell sm set-force-adoptable true`
5. `adb shell sm partition disk:179,32 public` [這步會把 SD 設為可攜式 SD 卡，包含格式化]
6. `adb shell sm set-force-adoptable false`
7. `adb shell sm list-volumes all` [應該會變到 public 裡]
8. 重開機
9. 檢查一下，還原 SD 卡資料，完成
