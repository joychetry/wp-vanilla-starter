# Rules for coding agents

This block theme is built on a shared efficiency-first starter. Read
`docs/STACK.md` before larger changes. These rules are not optional.

## Never

- Never add cleanup or site-wide speed code to the theme. That is the plugin
  layer. No removing emoji, wp-embed, oEmbed links, RSD/WLW/shortlink/generator,
  dashicons, jQuery, global styles or classic-theme-styles. No speculation rules,
  view transitions, resource hints (preload/preconnect), lazy-load or
  fetchpriority tweaks, caching, minifying core assets, image conversion, or
  `styles_inline_size_limit` changes.
- Never add a third-party front-end dependency (Tailwind, Alpine, jQuery, icon
  fonts, CSS frameworks, npm runtime packages, React on the front end) unless the
  owner explicitly asked and vanilla cannot do it. Log it in `docs/EXCEPTIONS.md`.
- Never add a bundler or a required build step. CSS ships as source. Blocks are
  PHP-only (`autoRegister`), or plain ES modules without JSX.
- Never use core Navigation (it loads the Interactivity API). Use the theme's
  `nav` block.
- Never use em dashes in copy, comments or docs.

## Always

- Block theme, WordPress 7.0+, PHP 8.1+.
- `functions.php` only includes `inc/setup.php`, `inc/assets.php`, `inc/blocks.php`.
- Every stylesheet the theme enqueues sets `path` data so core can inline it.
  Core block styling goes in `assets/css/blocks/core-<block>.css`.
- Theme CSS stays under 40KB raw; JS is 0KB on a normal page and any JS file is
  under 2KB, loaded only where its block renders. Run `bin/budget.sh`.
- No avoidable queries, filesystem scans or remote requests per request. Use known
  lists or cache the result.
- Tokens come from `theme.json` presets (`var(--wp--preset--...)`), never hex
  values in patterns.
- Native first: `popover`, `<dialog>`, `<details>`, scroll-snap, CSS.
- Accessibility: every template has `<main id="main">`, visible `:focus-visible`
  styles, `prefers-reduced-motion` respected, AA contrast, one `<h1>` per page.
- Translatable strings live in patterns (`esc_html_e( '...', 'vanilla-starter' )`),
  not in HTML templates.
- Prefix: PHP namespace `VanillaStarter`, globals and handles `vs_` /
  `vanilla-starter-`, CSS classes `vs-`.
- Check: `php -l` on changed PHP, JSON validity, `bin/budget.sh`, no PHP notices
  with `WP_DEBUG`, and blocks valid in the editor.
