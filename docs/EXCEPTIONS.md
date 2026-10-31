# Exceptions log

Every third-party front-end library in this theme must have an entry here.
The default is none. See "UI tiers" in `docs/STACK.md` for the rule.

Copy this template for each exception:

```md
- **Library:** name and version
  - **Why vanilla failed:** what was tried and what it could not do
  - **Size:** gzipped bytes
  - **Pages loaded on:** which templates or blocks, how it is enqueued
  - **Approved by:** name
  - **Date:** YYYY-MM-DD
```

## Log

- **Library:** Cloudflare Turnstile `api.js` (v0, served and versioned by Cloudflare)
  - **Why vanilla failed:** a bot check needs a third-party challenge service.
    The honeypot, render time and rate limit stay on for every form; Turnstile
    is an extra layer the owner asked for. Cloudflare does not allow
    self-hosting the script.
  - **Size:** 27.6KB gzipped (86.7KB raw, measured 2026-10-31), plus the widget iframe
  - **Pages loaded on:** only pages that render a form with Turnstile ticked in
    Submissions > Form settings and both keys saved. `add_turnstile()` in
    `inc/forms.php` enqueues it async in the footer. Off by default.
  - **Approved by:** Joy Chetry
  - **Date:** 2026-10-31
