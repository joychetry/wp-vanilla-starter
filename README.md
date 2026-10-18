# Vanilla Starter

An efficiency-first WordPress block starter theme. Lean markup, theme.json
tokens, small CSS that core inlines, zero JavaScript by default, no third-party
front-end dependencies and no build step.

Requires WordPress 7.0+ and PHP 8.1+.

## Quick start

```sh
git clone https://github.com/goodmonks/wp-vanilla-starter.git my-theme
cd my-theme
bin/rename.sh my-theme "My Theme"
npx @wp-playground/cli@latest server --mount=.:/wordpress/wp-content/themes/my-theme \
  --wp=latest --php=8.4 --login --blueprint=bin/playground.json
```

Or drop the folder into `wp-content/themes/` and activate it.

## What is inside

- `theme.json`: palette, fluid type and space scales, layout, element styles.
- `templates/`, `parts/`, `patterns/`: block markup, translatable via patterns.
- `blocks/`: PHP-only blocks: `nav` (popover menu, no JS), `icon` (inline SVG),
  `video-facade` (YouTube player on click, 715 byte module).
- `assets/css/`: `global.css` plus per-block `core-*.css`, all inlined by core.
- `bin/`: `budget.sh`, `rename.sh`, Playground blueprint.

Site-wide speed and cleanup (emoji, speculative loading, caching, image formats)
are deliberately left to plugins chosen per project.

## Docs

- [docs/STACK.md](docs/STACK.md): decisions, theme vs plugin split, UI tiers, targets.
- [docs/REUSE.md](docs/REUSE.md): new projects, local dev, workflow, updates, release.
- [docs/EXCEPTIONS.md](docs/EXCEPTIONS.md): log of approved libraries.
- [CHANGELOG.md](CHANGELOG.md)
