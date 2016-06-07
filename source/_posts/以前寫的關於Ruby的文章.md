---
title: 以前寫的關於Ruby的文章
tags:
  - Ruby
date: 2009-05-19 17:24:20
---

<script type="text/javascript" src="https://googledrive.com/host/0B6HWfJSgyadTUzBPMzhVbWN0TzQ/scripts/shCore.js">
</script> <script type="text/javascript" src="https://googledrive.com/host/0B6HWfJSgyadTUzBPMzhVbWN0TzQ/scripts/shBrushRuby.js">
</script> <link rel="stylesheet" type="text/css" href="https://googledrive.com/host/0B6HWfJSgyadTUzBPMzhVbWN0TzQ/styles/shCore.css" /> <link rel="stylesheet" type="text/css" href="https://googledrive.com/host/0B6HWfJSgyadTUzBPMzhVbWN0TzQ/styles/shThemeDefault.css" /> <script type="text/javascript">
		SyntaxHighlighter.config.clipboardSwf = 'https://googledrive.com/host/0B6HWfJSgyadTUzBPMzhVbWN0TzQ/scripts/clipboard.swf';
		SyntaxHighlighter.all();

</script>

之前作一些project的心得，大家勉強看看吧~

使用ruby也有一點時間了，最近大改一個之前寫的程式，發現有很多的心得，想跟之前的心得合起來記在這，供日後的參考。

ruby是一個日本人Yukihiro Matsumoto(matz)開發的Object-Oriented Script&nbsp;Language，在新版的RPGMaker裡可以使用ruby寫腳本。如果你已經會了perl，shell scripts，java等語言，這個東西將很容易上手(我的第一個程式就是改廠商的perl程式成ruby版本，至於為什麼要改，大概是主管對perl 的奇怪偏見吧:))。

以下是使用ruby 1.8(on fedora core 3)來寫作的心得。

一般來說跟perl的寫法幾乎一模一樣，只有一些地方不一樣(我碰到的)：
1\. 變數前面一般來說不用加$，因為加$是global變數。

<pre class="brush: rb;">hello # local variable
$hello2 # global variable
@hello3 # instance variable，可從其包含該值得object得到該值
HELLO4 # constant variable</pre>

2\. 字串裡秀變數值，或是要把值從變數拿出來時，就在變數上加上#{}就可以把值拿出來了。

<pre class="brush: rb;">puts "hello: #{var_1}\n" #印出含var_1的字串</pre>

3\. 敘述句結束也不用加;號。

4\. if-then-else-end，for，loop，更加的靈活。

<pre class="brush: rb;">2000.times {
do_something
} # 做2000次 do_something
for i in collection # collection可以是一堆數字，一堆array...
...
end
</pre>

5\. try-catch exception處理。使用rescue，raise等解決。

<pre class="brush: rb;">begin
f = File.new("abc")
...
rescue
puts "files abc can not be created.\n"
end</pre>

基本上我碰到修改與perl不同的大概就是這些。接下來就是實作程式時得到的一些心得。我把原來的perl程式改寫成了一隻multi-thread的daemon，碰到了不少問題，以下就是我的心得。

1\. ruby連結mysql：裡面並沒內建連結mysql的函式，要去安裝ruby-mysql。
使用方法也很簡單，如下所示。

<pre class="brush: rb;">begin
require "mysql"
...
my = Mysql::new(mysqldbhost, mysqldbuser, mysqldbpasswd, mysqldbname)
res = my.query(sql_query_language)
res.each do |row|
col1 = row[0]
col2 = row[1]
...
end
</pre>

基本上我用的大部分是傳回一個hash，然後再根據名稱找值。

2\. 建立一個daemon：用fork，loop就可以完成。加一個pid比較好控制。使用-kill
只會把之前的process砍掉。

<pre class="brush: rb;"># 外部呼叫砍掉之前的process
if ARGV[0] == "-kill" then
# pidfile存在，先把之前的process砍掉
if File.exists(pidfile) then
f = File.open(pidfile)
previus_pid = f.readlines()[0].to_i
f.close
Process.kill("SIGHUP", previus_pid)
end
exit
end

# pidfile存在，先把之前的process砍掉
if File.exists(pidfile) then
f = File.open(pidfile)
previus_pid = f.readlines()[0].to_i
f.close
Process.kill("SIGHUP", previus_pid)
end

# 寫新的pid到檔案裡
f = File.open(pidfile, "w")
f.write("#{Process.pid}\n")
f.close

#主程式daemon開始
fork do
loop do
daemon_main_procedure
end
end
exit
</pre>

3\. Thread的使用：ruby提供一系列的Thread函式，不過因為主程式沒有等其他thread做完的特性，老是主程式馬上結束了，但是其他thread沒做完(其他thread作一些IO與存取資料庫的動作)。一開始有想要用join，來等其他的thread，但是被發現這樣其實不太像thread(一個一個做)，結果使用array與while loop就可以達成。

<pre class="brush: rb;">to_do_list = ""
...
Thread.new(){|t|
to_do_list.push(t)
do_thread_thing
to_do_list.pop(t)
}
while to_do_list.length!=0
# spin lock
end
</pre>

4\. 使用pop3：因為我的程式有去讀信的動作，因此使用此函式，不過因為後來覺得如果使用者很多的時候pop3 server會爆掉，然後原來的程式在consistence上會有很大的問題，就用其他的方法了。

<pre class="brush: rb;">require 'net/pop'
...
pop = Net::POP3.new(pop3server)
pop.start(pop3user, pop3passwd) do |pop|
msg = pop.mails[0]
header = msg.header
body = msg.gsub("\r\n\r\n") # 兩個enter後為內文
...
pop.mails[0].delete # 砍掉mail
end
pop.finish
..
</pre>

5\. 因為之前4的問題，因此改採直接從本地端的mailbox(/var/mail/username)來讀信。好加在ruby有solution，差點又要回去找perl了:)這樣一來也比較快，而且不會用到pop3 server，也減少被try站的機會。

<pre class="brush: rb;">require 'mailread'
...
mailbox = File.open("/var/mail/username")
until (m = Mail.new(mailbox)).header.empty?
header = m. header
body = m.body
end
File.truncate("/var/mail/username") # 砍掉mail
</pre>

最後，說一下這個ruby其實還蠻好用的，不過文件總是很亂，也沒什麼固定的
(我覺得的)，不過ruby-doc mailling list有人發起重新寫文件的計畫，希望能
寫的更好，讓programmer查的更方便。