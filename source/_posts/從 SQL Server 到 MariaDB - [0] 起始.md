---
title: 從 SQL Server 到 MariaDB - [0] 起始
tags:
  - MariaDB
  - MSSQL
date: 2016-08-07 13:32:43
---

## 需求說明
公司因為 SQL Server 的支援快要到期，加上資料的等級與加值性不如預期，所以開始有從 SQL Server 轉到 MariaDB 的計劃。

## 測試環境
1. SQL Server 2008 R2 64 bits (要汰換的)
1. Oracle DB 12c (要汰換的)
2. MariaDB 10.1.16 64 bits
3. MariaDB 10.1.17 64 bits
3. MariaDB 10.1.18 64 bits
3. Windows Server 2012 64 bits (上面的資料庫都跑在這個上面)
4. MariaDB ODBC 2.0.12 64bits
5. Visual Studio 2015 Community

## 移轉紀錄

1. [從 SQL Server 到 MariaDB - [1] 備份與還原](https://sujunmin.github.io/blog/2016/08/08/%E5%BE%9E%20SQL%20Server%20%E5%88%B0%20MariaDB%20-%20[1]%20%E5%82%99%E4%BB%BD%E8%88%87%E9%82%84%E5%8E%9F/)
2. [從 SQL Server 到 MariaDB - [2] 資料庫 ACL 與帳號管理](https://sujunmin.github.io/blog/2016/08/10/%E5%BE%9E%20SQL%20Server%20%E5%88%B0%20MariaDB%20-%20[2]%20%E8%B3%87%E6%96%99%E5%BA%AB%20ACL%20%E8%88%87%E5%B8%B3%E8%99%9F%E7%AE%A1%E7%90%86/)
3. [從 SQL Server 到 MariaDB - [3] 資料庫移轉與系統程式修改](https://sujunmin.github.io/blog/2016/09/06/SQL%20Server%20%E5%88%B0%20MariaDB%20-%20[3]%20%E8%B3%87%E6%96%99%E5%BA%AB%E7%A7%BB%E8%BD%89%E8%88%87%E7%B3%BB%E7%B5%B1%E7%A8%8B%E5%BC%8F%E4%BF%AE%E6%94%B9/)
4. [從 SQL Server 到 MariaDB - [4] SQL Agent 與 MariaDB Event](https://sujunmin.github.io/blog/2016/09/10/%E5%BE%9E%20SQL%20Server%20%E5%88%B0%20MariaDB%20-%20[4]%20SQL%20Agent%20%E8%88%87%20MariaDB%20Event/)
5. [從 SQL Server 到 MariaDB - [5] Log-shipping 與 MariaDB Replication](https://sujunmin.github.io/blog/2016/09/19/%E5%BE%9E%20SQL%20Server%20%E5%88%B0%20MariaDB%20-%20[5]%20Log-shipping%20%E8%88%87%20MariaDB%20Replication/)
6. [從 SQL Server 到 MariaDB - [6] Audit 的實作](https://sujunmin.github.io/blog/2016/10/03/%E5%BE%9E%20SQL%20Server%20%E5%88%B0%20MariaDB%20-%20[6]%20Audit%20%E7%9A%84%E5%AF%A6%E4%BD%9C/)
7. [從 SQL Server 到 MariaDB - [7] Linked Server 與 FederatedX Storage Engine](https://sujunmin.github.io/blog/2016/10/05/%E5%BE%9E%20SQL%20Server%20%E5%88%B0%20MariaDB%20-%20[7]%20Linked%20Server%20%E8%88%87%20FederatedX%20Storage%20Engine/)
8. [從 SQL Server 到 MariaDB - [8] User-defined Functions 與擴增 Event 功能](https://sujunmin.github.io/blog/2017/06/08/%E5%BE%9E%20SQL%20Server%20%E5%88%B0%20MariaDB%20-%20[8]%20User-defined%20Functions%20%E8%88%87%E6%93%B4%E5%A2%9E%20Event%20%E5%8A%9F%E8%83%BD/)
9. [從 SQL Server 到 MariaDB - [9] DBA Daily Check Report 的製作 [1]](https://sujunmin.github.io/blog/2016/12/27/%E5%BE%9E%20SQL%20Server%20%E5%88%B0%20MariaDB%20-%20[9]%20DBA%20Daily%20Check%20Report%20%E7%9A%84%E8%A3%BD%E4%BD%9C%20[1]/)
10. [從 SQL Server 到 MariaDB - [10] DBA Daily Check Report 的製作 [2]](https://sujunmin.github.io/blog/2016/12/27/%E5%BE%9E%20SQL%20Server%20%E5%88%B0%20MariaDB%20-%20[10]%20DBA%20Daily%20Check%20Report%20%E7%9A%84%E8%A3%BD%E4%BD%9C%20[2]/)
