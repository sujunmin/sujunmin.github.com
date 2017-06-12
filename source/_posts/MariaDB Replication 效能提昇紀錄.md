---
title: MariaDB Replication 效能提昇紀錄
tags:
  - MariaDB
date: 2017-06-12 10:50:01
---
最近管理 MariaDB 因為有更多的資料庫移轉進駐，Replication Site 開始力不從心，甚至跟 Log 到超過 Expire Day 導致 Master 沒有 Log 的問題，因此是時候來好好解決這個問題了，畢竟 Replication Slave 如果真的差那麼多天其實真的就沒有實際上的意義了，發生問題要使用備援區資料差那麼多不能立刻使用。

## 觀察 Replication 的作業情形 ##
Master Site 是由數個獨立的資料庫組合而成，Slave Site 只讀不寫 (有稽核資料庫用途，但與原來的資料沒有關係)。

Master Binlog 寫入依照 OS 的處理處理之 (`innodb_flush_log_at_trx_commit=1`)，每個 Binlog 大小都是預設的 1G，約 2.5 - 3 天換檔一次。

預設的 Replication 只有一個 Thread 在跑，所以通常都是 Find Raw for Update/Delete Events。 

`show slave status` 看就只看到 Relay Log 與 Master 越差越多(從檔名看出來)。

## Parallel Repliation ##
從 MariaDB 的官方 blog 看到這篇 [Goodbye Replication Lag!](https://mariadb.com/resources/blog/goodbye-replication-lag)，原來 Replication 也有平行處理的，但是設定不是像那篇文章寫的那麼簡單。

### `slave_parallel_threads = # of cores` ###
這個從[官方文件](https://mariadb.com/kb/en/mariadb/replication-and-binary-log-server-system-variables/#slave_parallel_threads)可以了解，依樣畫葫蘆設定完以後得到以下的結果。

> 都在 `Waiting for room in worker thread event queue.`

這邊先來說明一下這些 workers 是在幹什麼用的。

執行 Replication 時，會有兩個 Thread，一個是 `SQL_THREAD`，另一個是 `IO_THREAD`，顧名思義，`SQL_THREAD` 就是處理 SQL 作業的 Thread，`IO_THREAD` 就是管理 Binlog 從 Master 拿來然後放在自己的 Relay Log 的 Thread。

在沒有 Parallel Replication 時，`SQL_THREAD` 就要自己分配給自己，也因為沒法先看一下 Binlog 後面的 Transation，也許後面可以先做的，但是還是一個一個做，自然很慢。

當有 Parallel Replication 時，原來的 `SQL_THREAD` 除了自己變成分配工作的 Thread 以外，會產生出其他 `slave_parallel_threads` 數量的 Thread 來處理 Transations，在分配工作石就能知道哪些作業沒有順序關係，哪些一定有順序，這樣就會快多了。

但是怎麼都會是 `Waiting for room in worker thread event queue.` 呢? 根本沒有平行處理阿?

### `slave-parallel-max-queued` ###
文件看一半就不會注意到這個。如果你的 Transation 都在每一個 `slave-parallel-max-queued` 大小的 Transation Logs 裡都被看透，這樣就有助於平行處理。反之如果都沒法看透那就只能等讀到的先做完，有空間了再讀後面的 Log 並且繼續執行，這樣也許有機會平行處理的都被打亂了。

所以適當的設定 `slave-parallel-max-queued` 是必須的，但是它跟前一個參數 `slave_parallel_threads` 與整體的記憶體使用是有關係的 (`slave_parallel_threads` * `slave_parallel_max_queued` 結果太大會導致記憶體用盡)。

在測試的過程中，有要求過把備援機的記憶體調高 (到 10G)，8 個 Parallel Threads，每個 Thread 512MB，加上原來 Innodb 的設定與作業系統的記憶體使用，在開始 Replication 時 Out of Memory 好幾次，這邊是需要微調的。

### 結果 ###
應該期望的是像這樣

<img src="https://github.com/sujunmin/sujunmin.github.com/blob/master/test/DBYksplUAAAtgek.jpg-large?raw=true" />

最重要的兩個參數，MariaDB [文件](https://mariadb.com/kb/en/mariadb/parallel-replication/)裡也寫得很清楚，節錄出來給大家參考。

>If this value is set **too high**, and the slave is **far** (eg. gigabytes of binlog) behind the master, then the SQL threads can quickly read all of that and fill up memory with huge amounts of binlog events **faster** than the worker threads can consume them.

>On the other hand, if set **too low**, the SQL thread might **not** have sufficient space for queuing enough events to keep the worker threads busy, which could **reduce performance**.

## 監控 Replication 的相關參數與 `Seconds_Behind_Master` ##
`show slave status` 應該是大家一定會用到的，不外乎 `Master_Log_File`，`Relay_Log_File`，`Last_IO[SQL]_Error[Errno]`，`Gtid_IO_Pos` 這些，其中有一個參數 `Seconds_Behind_Master` 原來我是把它當作是大海裡的一塊浮木，想說已經有這麼方便的參數可以拿來看了，應該是很方便監控了，經過幾天看的經驗其實不然。首先來看他的[定義](https://mariadb.com/kb/en/mariadb/show-slave-status/)。

>Difference between the timestamp logged on the master for the event that the slave is currently processing, and the current timestamp on the slave. Zero if the slave is not currently processing an event. From MariaDB 10.0.23 and MariaDB 10.1.9, with parallel replication, seconds_behind_master is updated only after transactions commit.

有[一篇](https://dba.stackexchange.com/questions/21443/when-is-seconds-behind-master-too-big)寫的很好。

>`Seconds_Behind_Master is really a double-edged sword.`

因為它只是當下 Slave 讀到的 Relay Log 與正在從 Master 拿到的 Binlog 的時間差，假設我的 Binlog 做的不是現在 Master 那個 (Master 在 123，Slave 在 100)，那些多的差異不會被計算入。

不過最後的每日資料庫狀態報告還是把它拿進來參考，不能當主要因素，但是能參考一下。

## `Update[Delete]_rows_log_evet::find_row()` 到底在存取哪個資料庫 ##
如果你的 Slave 也像我這麼簡單，系統的資源監視器蠻好用的，補齊 `SHOW PROCESSLIST` 這時候都沒有資料的問題。

<img src="https://github.com/sujunmin/sujunmin.github.com/raw/master/test/2017061202.png" />

不過我的觀察，大部分都是卡在這個作業上很久，有時候還會卡住其他的 Transaction 無法平行處理。

但是實際上 Master 更新資料也沒那麼久阿?

看一下 Binlog Format 是 `MIXED`，會跑出這個應該是變成 [Row-based](https://mariadb.com/kb/en/mariadb/binary-log-formats/) 來儲存了。

>The default format from MariaDB 10.2.4. A combination of statement and row-based logging. In mixed logging, statement-based logging is used by default, but when MariaDB determines a statement **may not be safe** for statement-based replication, it will use the **row-based** format instead.

Row-based 好處就是更能完整保存交易先後秩序，但是真的[很慢](https://mariadb.com/kb/en/mariadb/row-based-replication-with-no-primary-key/)阿，這個需要資料分析找出可以做 unique 或是 primary key 的資料欄位才能加速。

>Beginning in MariaDB 5.3, the slave will try to choose a good index among any available:

>1. The primary key is used, if there is one.
>2. Else, the first unique index without NULL-able columns is used, if there is one.
>3. Else, a choice is made among any normal indexes on the table (e.g. a FULLTEXT index is not considered).

[這裡](https://www.percona.com/blog/2014/05/02/how-to-identify-and-cure-mysql-replication-slave-lag/)有 script 可以找出哪些連一個 Unique 或是 Primary Key 都沒有的。
