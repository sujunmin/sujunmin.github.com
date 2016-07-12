---
title: 一些小 Scripts (PowerShell)
date: 2016-03-14 
tags: MIS
---

上星期五幫同事生出一些 Scripts，因為急著下班就沒分享了，今天分享給大家。
1. 在一個目錄裡的檔案 Syslogyyyy-mm-dd-hh.txt 檔案分時間壓縮成一個檔案，並放在同名的資料夾裡面。
假設 Syslogyyyy-mm-dd-hh.txt 在 $src_dir，要放到 $dst_dir 裡，壓縮程式是 $7zexe
```powershell
    $ErrorActionPreference = "Stop" #20160712 修改，當有發生錯誤馬上停止
    $src_dir = "C:\Users\sujunmin\Desktop\x"
    $dst_dir = "C:\Users\sujunmin\Desktop\y"
    $7zexe = "C:\Program Files\7-Zip\7z.exe"
    dir $src_dir | %{ 
    $id = $_.Name.SubString(6,10); 
    if(-not (Test-Path "$dst_dir\$id")) {mkdir "$dst_dir\$id"}; 
    &$7zexe a -tzip "$dst_dir\$id\$id.zip" "$src_dir\$_" ;
    del "$src_dir\$_";}
```
2. 每天將前一天的 Syslogyyyy-mm-dd-*.txt 壓起來放在並放在同名的資料夾裡面。
假設 Syslogyyyy-mm-dd-hh.txt 在 $src_dir，要放到 $dst_dir 裡，壓縮程式是 $7zexe
```powershell
    $ErrorActionPreference = "Stop" #20160712 修改，當有發生錯誤馬上停止
    $src_dir = "C:\Users\sujunmin\Desktop\x"
    $dst_dir = "C:\Users\sujunmin\Desktop\y"
    $7zexe = "C:\Program Files\7-Zip\7z.exe"
    $id = (get-date).AddDays(-1).ToString("yyyy-MM-dd")
    mkdir "$dst_dir\$id"
    &$7zexe a -tzip "$dst_dir\$id\$id.zip" "$src_dir\Syslog$id*.txt";
    del "$src_dir\Syslog$id*.txt"
```
