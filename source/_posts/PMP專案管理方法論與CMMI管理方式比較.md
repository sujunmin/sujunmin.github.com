---
title: PMP專案管理方法論與CMMI管理方式比較
tags:
  - PMP
date: 2010-03-19 16:44:33
---

這篇發表在[rogersnote 20100318 電子報](http://www.pmsuccess.net/rogersnotes_V1.asp?Eid=359)裡，拿到10個PDUs:D

想必現在有許多公司在之前的CMMI狂潮中加入CMMI的管理概念與方式，而現今在專案管理的顯學PMP風潮下，帶動許多公司新的管理哲學的使用與導入。但是在這兩個管理專案的方式，是否有異中求同或是相輔相成的地方呢?以下就分別以這兩個方法論對於本公司執行專案的角度來作簡單的觀察。首先來說CMMI，能力成熟度整合模式(CMMI, Capability Maturity ModelR Integration)，是卡內基美隆大學軟體工程學院（SEI）所發展出的標準，適用領域為系統工程（SECM）、軟體工程（SW-CMM）、整合產品與流程開發（IPPD-CMM）、委外作業（SS）等軟體開發的重要領域。在1997 年，SEI將個別的模式整合成能力成熟度整合模式（Capability Maturity Model Integration, CMMI-SW/SW/IPPD/SS）。

CMMI以兩種方式判定軟體廠商的等級，分別以個別領域為主的能力等級（capability level）與相關流程群組綜合的成熟度等級（maturity level）。它還包含一套如何評定流程成熟度的方法與標準，SEI稱它為SCAMPI（the Standard CMMI Assessment Method for Process Improvement），裡面描述了如何鑑定流程的等級。

(以上CMMI資料摘自[這裡](http://zh.wikipedia.org/wiki/%E8%83%BD%E5%8A%9B%E6%88%90%E7%86%9F%E5%BA%A6%E6%A8%A1%E5%9E%8B%E9%9B%86%E6%88%90))

所以CMMI的模式是判定一個公司是否有達到該認證的水準值，與PMP為個人之專案管理能力較為不同。且CMMI較為軟體開發與系統整合，與PMP較為不一樣的是PMP適用於各種專案，非僅指軟體開發。兩者皆對於所分之個別領域(像PMP的9大知識領域)與相關流程領域綜和(像PMP的IPECC)都有像規範那樣的描述(但不是SOP或是說明書)，所以企業在導入時需要就他們的精神，發展出一套屬於該組織的SOP與說明(CMMI裡稱為CPMS，CMMI-based Process Management Standard/System，基於CMMI的管理標準)，進而施行。而PMP對於CMMI來說是在Quality的一個工具。

接下來詳細部分，就以本公司導入之CMMI ML3 DEV 1.2(發展成熟度第3級1.2版)的內容與PMP管理技巧比較：

CMMI的流程類別有：
1\. 流程管理(Process Management)：此部份著重於可使用的組織流程資產，並且讓他更好用，以讓所有的專案在開始時能夠就已定立的流程選一個來用。
其中有三個流程領域，分別為組織流程專注(OPF，Org. Process Focus)，組織流程定義(OPD，Org. Process Definition)，組織訓練(OT，Org. Trainning)。
前兩項比較像是PMP裡的組織流程資產(OPA，Org. Process Assessment)的維護，包含組織流程改造。後面一項(OT)為發展專案團隊時的工具。

2\. 專案管理(Project Management)：此部份著重於專案執行時的模式，藉由組織流程資產，配合調適原則產生出對於專案合宜的執行方式規劃。
其中有五個流程領域，分別為整合專案管理(IPM，Integrated Project Management)，風險管理(RSKM，Risk Management)，專案規劃(PP，Project Plan)，專案監控(PMC，Project Management Control)，供應商協議管理(SAM，Supplier Agreement Management)。
整合專案管理對應者PMP IPECC的P(Plan)與E(Exec.)，藉由組織的流程資產與調適準則來決定並執行本專案的走向(像選套餐一樣，也可以改變套餐的格式)，專案規劃對應著PMP IPECC的P(Plan)，風險管理就對應PMP的風險管理流程，專案監控對應PMP的監控專案工作，供應商協議管理對應PMP的採購管理部分(除了自製與外購分析，CMMI有一個相關流程對應它)。

3\. 工程(Engineering)：此部份為CMMI認為的專案執行面所在，大部分是電腦系統之整合規劃。
其中有六個流程領域，分別為需求發展(RD，Req. Development)，技術解決方案(TS，Tech. Solution)，產品整合(PI，Product Integration)，確認(VER，Verification)，驗證 (VAL，Validation)，需求管理 (REQM，Req. Mgmt.)。
其中需求發展與需求管理在PMP管理方式為需求管理，技術解決方案與產品整合在PMP專案規劃的各項目中，對於產出物的執行方式，被拿來作TT，確認與驗證對應PMP為驗證範疇，但CMMI認為的是驗證為把事情做對了(正確的交付項目)，確認為作了對的事(除了正確的交付項目，連流程的正確性也必須參考)，有流程稽核與經驗教訓的意味。

4\. 支援(Support)：其他有關專案規劃與執行的輔助流程領域。
其中有五個流程領域，分別為決策分析與解決方案(DAR，Decision Analysis and Resolution)，建構管理(CM，Conf. Mgmt.)，度量與分析(MA，Mesurement Analyze)，流程與產品品質保證(PPQA，Process and Product Quality Assurance)。
其中決策分析與解決方案在CMMI裡的決策分析中獨立的一個流程，在於讓每一次的決定都制式化。建構管理相當於PMP的執行整合變更控制，度量與分析相當於PMP裡的報告績效，整理相關的績效資料供相關人員參考。流程與產品品質保證相當於PMP的品質管理。

除了以上以外，PMP管理方法對於溝通管理(利害關係人管理與發佈資訊管理)有專門的方法論，CMMI裡雖沒有專門的流程領域對應，但是在各個流程領域中仍有提及溝通方法用重要性。總而言之，CMMI對於我們組織而言，是把各專案英雄主義似的執行方式轉變成統一輸出量產的方式，專案經理依照組織流程的內容可得到固定的套餐，接下來專案經理只要針對調適原則修改專案執行架構，並且有一套統一的輸出。但是對於專案經理本身的特質仍會影響到企業專案統一管理的原則，因此導入PMP認證就可確保專案經理在調適各套餐時有統一的思考邏輯與方式，進而統一管理專案，實為相輔相成的兩個方法論。