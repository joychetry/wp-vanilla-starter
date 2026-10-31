# Changelog

All notable changes to this starter. Versions follow semver.

## Unreleased

- Forms without a plugin (`inc/forms.php`, `inc/forms-admin.php`, see
  `docs/FORMS.md`): a `forms()` registry, contact and newsletter patterns,
  honeypot, render-time and rate-limit spam checks, private Submissions per form,
  Form settings (notify emails, JSON webhook with `{{field}}` template), Quick
  view, Resend, privacy export and erase. `assets/js/forms.js` (1.3KB module)
  and `assets/css/forms.css` load only where a form renders.
- Appearance > Theme Settings (`inc/setup.php`): one admin page that links to
  every theme control (Styles, Templates, Template parts, Patterns, Fonts,
  Menus, Menu locations) and shows which menu each location uses. No options
  stored, no front-end cost.

## 0.2.0 - 2026-10-18

- `extras/mu-plugins/vs-lean-global-styles.php`: optional must-use plugin (plugin
  layer, not loaded by the theme) that drops core default presets the theme turns
  off in theme.json. Front page inline CSS 37,660 to 29,934 bytes in Playground
  (page weight; global styles do not count toward core's inline limit).
  `extras/` is excluded from the theme zip and from `rename.sh`.
- Comments on single posts: `parts/comments.html` (core Comments title,
  template with threaded replies, pagination and form, no avatars) plus a 1KB
  `core-comments.css`. Core renders nothing when comments are closed and there
  are none; empty template parts are hidden in `global.css`. The Playground
  blueprint adds 2 comments (one reply) to the sample post.
- Icons have one source of truth: `assets/icons/icons.php` (name => SVG
  markup). The Icon block's `enum` is injected from it with the
  `block_type_metadata` filter, so adding an icon is one line. Replaces the
  `ICONS` constant, the block.json enum and the eight `.svg` files; `icon()` no
  longer reads `.svg` files or uses the object cache. Icon block 0.2.0, same output.

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
