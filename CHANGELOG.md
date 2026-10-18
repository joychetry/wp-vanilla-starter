# Changelog

All notable changes to this starter. Versions follow semver.

## 0.1.0 - 2026-10-18

- Block theme for WordPress 7.0+ and PHP 8.1+, theme.json v3 with a five-color
  palette, Utopia fluid type and space scales, system font stack.
- Templates: index, home, front-page, single, page, archive, search, 404. Parts:
  header, footer.
- Patterns: hero, feature grid, CTA, FAQ (core Details), two headers, two
  footers, hidden 404 and no-results content. Pattern category `vanilla-starter`.
- PHP-only blocks (`autoRegister`): `nav` (popover mobile menu, no JS), `icon`
  (8 inline SVGs), `video-facade` (youtube-nocookie on click, 715 byte module).
- `global.css` with reset, accessibility basics and layout primitives; per-block
  CSS for details, quote and post template. All styles inlined by core.
- Dev tooling: `bin/budget.sh`, `bin/rename.sh`, Playground blueprint,
  `.lighthouserc.json`, `phpcs.xml.dist`.
- Docs: STACK, REUSE, EXCEPTIONS, agent rules.
