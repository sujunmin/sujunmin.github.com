---
title: Oracle 12c 的 Database 預設是開 Unified Auditing 的!
date: 2016-02-14 
tags: OracleDB
---

之前才說剛急救一下 Oracle sysaux 滿的[問題][1]， 沒隔幾天又發生了(昨天晚上)。昨天仍透過 exec dbms_stats.purge_stats() 移除部分資料，甚至只留下一天也會發生。急救完以後，想說好好來看看問題到底在哪邊，發現到一個很有趣的問題，[有人(A)][2]認為是文件寫錯，連 Oracle 的人(B)也開文說明[問題][3]在哪邊，以下紀錄一下處理過程。

- Unified Auditing 是 Oracle 12c 的一個[新功能][4]
- 我的規劃是不用他(除了太新之外，因為它會記到 DB 裡，又要考慮會爆的問題，加上專案使用的 Log Solution “有機會” 收舊方式丟到 OS  裡的 Logs)，用 Traditional Auditing (Audit_Trail = XXX 那種)
- 問廠商最近的流量如何，都沒有太大的變化，應該就是系統設定的問題
- 透過這個查詢(C)現在 SYSAUX 中各個 Object 的使用狀態 select bytes/1024/1024 mb, segment_name from dba_segments where tablespace_name = 'SYSAUX' order by mb desc
- 發現到有一大堆 SYS_LOBXXX的 LOB，大小每個都快 1G，但是 SYSAUX 最大只能 32G (block size 4k)
- 反查回去是哪個 TABLE 在用的 select table_name from dba_lobs where segment_name = 'SYS_LOBXXX’; 發現到是 CLI_SWP 開頭的 TABLE
- Google 一下竟然發現那些是 Unified Auditing 用來存資料的 TABLE
- 耶，奇怪，我沒有開呀? (透過這個可以看 select parameter, value from v$option where parameter = 'Unified Auditing';)
- 但是 select count(*) from unified_audit_trail; 卻有資料 (而且很多)
- 跟據 A 提供的方法看一下 Unified Auditing 的選項 select * from audit_unified_enabled_policies;  竟然有東西
- 剛剛的結果根據 B 說的方式，進入 Mix mode 了  (Unified 與 Traditional 的都在跑，Unified 的完全沒清理過，這樣不爆也很困難)
>Mixed mode is intended to introduce unified auditing, so that you can have a feel of how it works and what its nuances and benefits are. Mixed mode enables you to migrate your existing ?applications and scripts to use unified auditing. Once you have decided to use pure unified auditing, you can relink the oracle binary with the unified audit option turned on and thereby enable it as the one and only audit facility the Oracle database runs. If you decide to revert back to mixed mode, you can. 

- 先關掉  noaudit policy ORA_SECURECONFIG; 
- 移除那些沒用的 Audit 資料 (USE_LAST_ARCH_TIMESTAMP 要設成 False，除非想保留部分，要設 Archive Timestamp)
- 重作 C裡提到的看看各 Object 的使用狀況，那堆肥肥的 Objects 應該都不見了

B 說得很有趣 
>Don't trust our slides - only believe what you've verified by yourself  
>...
>Should be ... well ... thanks to Marco Patzwahl who asked me why he still has over 100 audit records in V$UNIFIED_AUDIT_TRAIL? Good question - and I've had no answer. But Carol, my manager, knew the right person to ask. And Naveen replied within minutes (thanks!!!). 

[1]: https://www.facebook.com/sujunmin2008/posts/10209120388553195
[2]: https://community.oracle.com/thread/2622558?start=0&tstart=0
[3]: https://blogs.oracle.com/UPGRADE/entry/unified_auditing_is_it_on
[4]: https://docs.oracle.com/database/121/DBSEG/auditing.htm#DBSEG1023
