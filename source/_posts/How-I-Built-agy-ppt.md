---
title: How I Built agy-ppt — An Agent Skill That Turns Reports into PowerPoint Decks Without the AI-Slop Look
tags:
  - AI
  - Agent Skill
  - PowerPoint
date: 2026-10-02 17:10:00
---

Every AI-generated slide deck looks the same. You know the look: purple gradient background, three generic icon cards in a row, a stock photo of diverse people pointing at a whiteboard. I call it the AI-slop fingerprint, and once you've seen it, you can't unsee it.

I build a lot of decks with AI agents, and I got tired of apologizing for how they look. So I built [agy-ppt](https://github.com/sujunmin/agy-ppt) — an open-source agent skill that turns reports into PowerPoint decks with actual visual design. It's MIT licensed, and it installs with one command.

But the interesting part of this story isn't the skill. It's the experiment that decided the entire architecture — and it has to do with how image models handle Traditional Chinese text.

## The experiment: Gemini vs GPT on dense Traditional Chinese

In my own testing, I found that Gemini-generated images can barely hold any Traditional Chinese text. Give it a slide layout with a paragraph or two of Chinese and the characters blur, warp, or dissolve into plausible-looking gibberish.

Run the exact same layout through GPT's image models, and it holds dozens of Chinese characters cleanly — dense paragraphs, data tables, small annotations, all legible.

This matters more than it sounds. Slides are the most text-dense visuals in common use. A marketing hero image can survive on five words; a slide with five words is an empty slide. If your renderer can't do dense CJK text, you cannot do slides. Full stop.

So the renderer wasn't chosen by brand loyalty. It was chosen by experiment: GPT image models render every slide.

## One director, strict ownership

With the renderer settled, I split the remaining work by what each agent is actually good at — and I made the ownership brutally explicit, because multi-agent workflows rot the moment two agents both think they're in charge:

- **Antigravity (AGY)** is the sole director. It owns the outline (`outline.md`), the visual spec (`deck_spec.json`), the copy on every slide, the approval gates, and content/visual QA. The workflow is always AGY → worker → AGY. Workers never hand off to each other directly, and nobody moves to the next phase without AGY.
- **Kiro** owns all engineering. Every line of executable code — assembly scripts, validators, schema changes, bug fixes — goes through Kiro. AGY may run existing verified scripts, but it may not modify code just because it can.
- **Codex** renders images. Only images. It is explicitly forbidden from touching the outline, the visual spec, the copy, the page count, the code, or the assembly step — and forbidden from faking AI-generated slides with Pillow, SVG, or python-pptx. This sounds paranoid until you've watched an image agent "helpfully" rewrite your facts.

This strictness is the whole trick. Most agent pipelines fail not on capability but on authority ambiguity. Here there is none.

## Contracts before code

The skill wasn't vibe-coded. Every phase of the build was written up as a contract before implementation — `docs/` holds dozens of them: raster contracts, image contracts, provider UX contracts, grounding/translation contracts, validation contracts, each with its own validation report.

And the contracts are frozen. A CI check called `frozen-contract-guard` blocks any pull request that violates them. Verification runs in three classes: deterministic CI that must pass before any merge, a manual release-readiness pass in a clean-room environment before shipping, and scheduled live validation against a stable public source.

There's also a boundary the project states explicitly and honestly: CI verifies engineering contracts — syntax, schemas, determinism, recovery scenarios. It does not and cannot verify semantic truth, such as whether a claim on a slide actually matches its source document. That judgment stays with AGY's review, by design. Knowing what your automation *can't* prove is part of engineering rigor too.

## No API keys

The skill is OAuth-only. It assumes three CLIs already logged in with their own subscription sessions — Google AI Pro, Kiro Pro, ChatGPT Plus — and it never touches, copies, or forwards any OAuth tokens. No `OPENAI_API_KEY`, no key management, no billing surprises. If you have the subscriptions, you have the pipeline.

## Approval gates before pixels

Nothing renders until you've approved three things: the outline, the visual style, and a real single-slide sample. Only then does the full deck generate, slide by slide, with QA notes at each step. Re-rendering one bad slide is cheap; discovering on slide 18 that the design direction was wrong is expensive.

The output is hybrid PowerPoint: full-page 16:9 rendered images preserve the visual fidelity (this is what kills the AI-slop look), while key text, native charts, and images stay editable and replaceable inside PowerPoint. A typo fix shouldn't require a re-render.

## Try it

```bash
npx skills add sujunmin/agy-ppt
```

It's also on [ClawHub](https://clawhub.ai/sujunmin/skills/agy-ppt). The one hard requirement: your agent environment needs image-generation capability — that's the load-bearing wall of the whole pipeline.

[70-second demo](https://youtu.be/vPSsj7aZMtU) · [GitHub](https://github.com/sujunmin/agy-ppt)

![agy-ppt output preview](https://raw.githubusercontent.com/sujunmin/agy-ppt/main/assets/agy-ppt-teaser.gif)

If you make decks in CJK languages — the use case most slide tools ignore — I'd especially like to hear what breaks for you. Issues welcome; I read every one.
