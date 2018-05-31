---
title: 用 Graylog 取代 Splunk 紀錄 (上)
tags:
  - MIS
  - Networks
date: 2018-05-31 15:43:12
---

## 需求說明
原來負責系統的 Log 架構是這樣的
<img src=https://raw.githubusercontent.com/sujunmin/sujunmin.github.com/master/test/graylog_1.png />

因為 Splunk License 的關係，有時候會因為收的量太多而罷工了。

這次就是想要利用 [Graylog](https://www.graylog.org/) 來取代原有的 Splunk，以避免有 Log 沒分析到的問題。

## 試用經過

# (請注意以下因為一些因素，只換 Splunk 的方式非真正最佳解法，僅提供有類似架構的朋友參考)

因為一開始對 Graylog 不熟，所以當然要有一些測試。從官網上的 [OVA](http://docs.graylog.org/en/latest/pages/installation/virtual_machine_appliances.html) 開始，發現可以有 Log 進來，玩了一下界面，還蠻不錯的。

不過有一個麻煩的事情(這個在之前系統有購買 Arcsight 的 Solution 也有碰到過)，就是資料是亂碼的問題。

原因出在從 [KIWI Syslog Server](https://www.kiwisyslog.com/kiwi-syslog-server) 拋出來的 Syslog 是 Windows 編碼，到 Linux 上就會變亂碼了。

找了一些[方法](https://community.graylog.org/t/syslog-encoding-problem/2253)，沒找到可以從 Graylog 直接 Conversion 的方法，只能前面加個 [logstash](https://www.elastic.co/products/logstash) 轉了。

原來想裝在官網的 OVA 裡，不過官網的 OVA 一來是 Ubuntu 14.04，系統裡的 Ubuntu 16.04 外，裝好起來效能竟然差到沒法 Parsing 資料，於是就想辦法自己裝了。

官網的[手動安裝方法](http://docs.graylog.org/en/2.4/pages/installation/os/ubuntu.html)寫得很詳細，照做就很容易完成了。

## 最後架構
<img src=https://raw.githubusercontent.com/sujunmin/sujunmin.github.com/master/test/graylog_2.png />

因為只有 Windows 傳過來的會亂碼，所以在 Graylog 前面多放一個 logstash 轉檔以後再丟到 Graylog 即可，網路設備的不會這樣，就直接送進 Graylog。

### Graylog Server
#### Ubuntu 16.04

```console
$ sudo apt-get update && sudo apt-get upgrade
$ sudo apt-get install apt-transport-https openjdk-8-jre-headless uuid-runtime pwgen
```

測試一下 Java
```console
sujunmin@graylog:~$ java -version
java version "1.8.0_171"
Java(TM) SE Runtime Environment (build 1.8.0_144-b01)
Java HotSpot(TM) 64-Bit Server VM (build 25.144-b01, mixed mode)
```

#### MongoDB
安裝最新版 MongoDB

```console
$ sudo apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv 2930ADAE8CAF5059EE73BB4B58712A2291FA4AD5
$ echo "deb [ arch=amd64,arm64 ] https://repo.mongodb.org/apt/ubuntu xenial/mongodb-org/3.6 multiverse" | sudo tee /etc/apt/sources.list.d/mongodb-org-3.6.list
$ sudo apt-get update
$ sudo apt-get install -y mongodb-org
$ sudo systemctl enable mongod.service
$ sudo systemctl restart mongod.service
```

#### Elasticsearch
Elasticsearch 要用 5.x 版的 (是說我已經有這個了，然後前面又有 logstash，差一個 Kanban 就變成 [ELK](https://www.elastic.co/webinars/introduction-elk-stack) 了，還要用 Graylog 嗎 XD)

```console
$ wget -qO - https://artifacts.elastic.co/GPG-KEY-elasticsearch | sudo apt-key add -
$ echo "deb https://artifacts.elastic.co/packages/5.x/apt stable main" | sudo tee -a /etc/apt/sources.list.d/elastic-5.x.list
$ sudo apt-get update && sudo apt-get install elasticsearch
```

安裝完以後記得設定檔 `/etc/elasticsearch/elasticsearch.yml` 裡頭的 `cluster.name` 要設為 `graylog`。

```shell
cluster.name: graylog
```

然後設定服務

```
$ sudo systemctl enable elasticsearch.service
$ sudo systemctl restart elasticsearch.service
```

測試一下
```
sujunmin@graylog:~$ curl -XGET 'localhost:9200/?pretty'
{
  "name" : "-kYzFA9",
  "cluster_name" : "graylog",
  "cluster_uuid" : "T3JQKehzSqmLThlVkEKPKg",
  "version" : {
    "number" : "5.5.1",
    "build_hash" : "19c13d0",
    "build_date" : "2018-05-18T20:44:24.823Z",
    "build_snapshot" : false,
    "lucene_version" : "6.6.0"
  },
  "tagline" : "You Know, for Search"
}
```

#### Nginx
作為前端 Reverse Proxy 到 Graylog Web 的媒介

```
sudo apt -y install nginx
```

修改設定檔

```
sudo vi /etc/nginx/sites-available/default
```

```
server
{
    listen 80 default_server;
    listen [::]:80 default_server ipv6only=on;
    server_name <實際 IP>;

    location / {
      proxy_set_header Host $http_host;
      proxy_set_header X-Forwarded-Host $host;
      proxy_set_header X-Forwarded-Server $host;
      proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
      proxy_set_header X-Graylog-Server-URL http://$server_name/api;
      proxy_pass       http://127.0.0.1:9000;
    }
}
```

設定服務與啟動

```
$ sudo systemctl enable graylog-server.service
$ sudo systemctl start graylog-server.service
```

#### Graylog
安裝最新版 Graylog

```
$ wget https://packages.graylog2.org/repo/packages/graylog-2.4-repository_latest.deb
$ sudo dpkg -i graylog-2.4-repository_latest.deb
$ sudo apt-get update && sudo apt-get install graylog-server
```

以下的設定是一定要設的

1. `root_username`，預設是 `admin`，可以改喜歡的
2. `root_password_sha2`，root 的密碼，用以下方式產生
    
    `echo -n yourpassword | sha256sum`
3. `password_secret`，其他用戶的密碼從這個資料加料產生，用以下方式產生

    `pwgen -N 1 -s 96`
4. `rest_listen_uri`，預設是 `0.0.0.0:9000`，因為不管是 Web 還是其他應用程式，跟 Graylog 溝通都是透過 [Graylog RESTFul API](http://docs.graylog.org/en/2.4/pages/configuration/rest_api.html)，這個就是溝通的橋樑

5. `rest_transport_uri`，這個通常跟上面的一樣，不一樣的部份是如果你想要 expose 這個服務到其他不同段的 IP，這邊就要特別設定再外面看到的 IP，前面 Nginx 的 `server_name` 會跟這個一樣

6. `root_timezone` 設定時區
7. `web_enable` 是 `true`
8. `web_listen_uri` 跟 `rest_listen_uri` 一樣

設定服務與啟動
```
$ sudo systemctl enable graylog-server.service
$ sudo systemctl start graylog-server.service
```

登入看看，記得把原來的預設收 514 的 syslog input 的 Port 改成 12345。

### logstash
安裝 logstash

```
sudo apt-get install logstash
```

安裝 `logstash-output-syslog` (轉換過的 syslog 吐給 Graylog)

```
sudo /usr/share/logstash/bin/logstash-plugin install logstash-output-syslog
```

設定 pipeline.conf
```
sudo vi /etc/logstash/conf.d/pipeline.conf
```

Input 從 514 的 syslog 編碼為 CP950 (Windows) 轉到 12345，編碼是 UTF-8
```
input {
    syslog {
      port => 514
      codec => plain {
        charset => "CP950"
      }  
    }
}

output {
    syslog {
        host => "127.0.0.1"
        port => 12345
        codec => plain {
          charset => "UTF-8"
        }
    }
}
```

Input 設在 514 用預設的 logstash 使用者沒法聽，所以要改啟動參數

```
sudo vi /etc/logstash/startup.options
```

找到 `LS_USER` 與 `LS_GROUP` 都設成 `root`

重新安裝設定檔

```
sudo /usr/share/logstash/bin/system-install
```

設定服務與啟動
```
sudo systemctl enable logstash.service
sudo systemctl start logstash.service
```

## 下一步
安裝測試完成以後，就是原來 Splunk 的報表能夠轉移。

剛剛有說到所有跟 Graylog 溝通都是透過 Graylog RESTFul API 來做處理，下一篇文章就是要來說明如何透過 Graylog RESTFul API 來做簡單的報表。
