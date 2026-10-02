---
title: 打造沒有 AI 味的簡報：agy-ppt 幕後
tags:
  - AI
  - Agent Skill
  - PowerPoint
date: 2026-10-02 17:15:00
---

AI 生的投影片，長得都一個樣。你一定看過：紫色漸層背景、三張一模一樣的 icon 卡片，再配一張「多元團隊指著白板」的 stock photo。我叫它 AI 味，一旦看出來就回不去了。

我平常很常用 AI agent 做簡報，做到後來真的受不了，每次都要先跟人道歉「這版醜了點」。所以我做了 [agy-ppt](https://github.com/sujunmin/agy-ppt) — 一個開源的 agent skill，專門把報告變成「看起來像人做的」PowerPoint。MIT 授權，一行指令就能裝。

不過這篇想講的不是 skill 本身，而是決定整個架構的那場實驗 — 跟圖片模型怎麼算繁體中文有關。

## 實測：Gemini 和 GPT，誰算得動繁體中文？

我自己實測的結果是這樣：叫 Gemini 生一張帶繁體中文的圖，字稍微多一點就糊掉、扭曲，或變成看起來像字但根本不是字的東西。

同樣的版面拿給 GPT 的圖片模型算，幾十個中文字都清清楚楚 — 段落、表格、小註解，全部可讀。

這件事比聽起來重要。投影片是日常生活中「文字密度最高」的視覺物。行銷主視覺五個字就能交差，投影片五個字就是一頁空白。如果你的算圖引擎做不出高密度的中日韓文字，那你根本做不了投影片。就這麼簡單。

所以算圖引擎不是憑喜好選的，是實驗選出來的：每一張投影片都用 GPT 圖片模型算。

## 一個導演，權責分明

引擎決定之後，剩下的工作就照各家 agent 擅長的來分 — 而且權責寫得非常死，因為多 agent 流程只要有兩個人都覺得自己是老大，馬上就爛掉：

- **Antigravity（AGY）是唯一導演**。大綱（`outline.md`）、視覺規格（`deck_spec.json`）、每頁文案、審核關卡、內容／視覺 QA，全都歸它。流程永遠是 AGY → 打工仔 → AGY，打工仔之間不准私下交接，沒它點頭誰都不准進下一階段。
- **Kiro 管全部工程**。所有能執行的程式碼 — 組裝腳本、驗證器、schema 修改、修 bug — 一律走 Kiro。AGY 可以執行已經驗證過的腳本，但不准因為自己會寫就手癢改 code。
- **Codex 只負責算圖**。只能算圖。它被明文禁止碰大綱、視覺規格、文案、頁數、程式碼、組裝 — 甚至禁止用 Pillow、SVG、python-pptx 假裝自己是 AI 生圖。聽起來很偏執，直到你親眼看過圖片 agent「好心」幫你改事實。

嚴格就是全部的心法。多 agent 流程通常不是死在能力，是死在權責不清。這裡沒有模糊地帶。

## 先寫合約再寫 code

這個 skill 不是 vibe coding 出來的。每個開發階段都是先寫合約才實作 — `docs/` 底下有幾十份：raster 合約、圖片合約、provider UX 合約、grounding／翻譯合約、驗證合約，每份都有自己的驗證報告。

而且合約是凍結的。CI 有個叫 `frozen-contract-guard` 的檢查，任何違反合約的 PR 直接擋掉。驗證分三級：擋 merge 的確定性 CI、發布前在乾淨環境的手動驗證、排程的 live validation。

另外有一條這個專案明說的界線，我覺得很值得寫出來：CI 只驗證工程合約 — 語法、schema、確定性、災難復原。它不驗證、也驗證不了語意真實，比如「投影片上的說法跟來源文件對不對得上」。那個判斷永遠留在 AGY 的人工審查。這種「知道自己的自動化證明不了什麼」的誠實，也是工程嚴謹的一部分。

## 不用 API key

這個 skill 是純 OAuth 制。假設三個 CLI 都已經用各自的訂閱登入 — Google AI Pro、Kiro Pro、ChatGPT Plus — skill 不碰、不複製、不轉傳任何 OAuth token。沒有 `OPENAI_API_KEY`，沒有 key 管理，沒有帳單驚喜。有訂閱，就有整條 pipeline。

## 先審核，再算圖

在你點頭之前，什麼都不會算：大綱、視覺風格、一張真實的單頁樣張，三關都過了才開始整份產出，一頁一頁生，每步都有 QA。重算一頁很便宜，第 18 頁才發現設計方向錯了很貴。

輸出是混合式 PowerPoint：整頁 16:9 的算圖保留視覺完整度（這就是 AI 味消失的原因），關鍵文字、原生圖表、圖片在 PowerPoint 裡照樣可以編輯替換。改個錯字不應該要重算整頁。

## 來玩玩看

```bash
npx skills add sujunmin/agy-ppt
```

[ClawHub](https://clawhub.ai/sujunmin/skills/agy-ppt) 上也有。唯一的硬需求：你的 agent 環境要有圖片生成能力 — 這是整條 pipeline 的承重牆。

[70 秒 demo](https://youtu.be/vPSsj7aZMtU) · [GitHub](https://github.com/sujunmin/agy-ppt)

![agy-ppt 實際輸出](https://raw.githubusercontent.com/sujunmin/agy-ppt/main/assets/agy-ppt-teaser.gif)

如果你也在做中日韓文的簡報 — 就是那些主流工具都不理的使用情境 — 很想聽聽你那邊會壞在哪裡。歡迎開 issue，我每則都會看。
