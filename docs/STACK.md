# Stack decisions

Everything this starter decided, and why. Source research: "Fastest stack for a
custom WordPress theme" (2026-10-18). Where that note and this file disagree,
this file wins: cleanup and site-wide speed features moved to the plugin layer.

## Why a block theme

- Block themes render every block before `<head>` prints, so core knows which
  blocks are on the page and loads only their CSS. Classic themes only got this
  in 6.9, through an output buffer.
- theme.json gives one source of design tokens that the editor and the front end
  share as CSS custom properties (`--wp--preset--*`).
- HTML templates, PHP patterns and PHP-rendered blocks need no build step.
- The old "block themes are slower" benchmarks compared bloated themes with lean
  classic ones and predate 6.9. That was theme bloat, not architecture.

## What core already handles (6.9 / 7.0 / 7.1)

The theme relies on these and does not reimplement them:

- **On-demand block CSS.** Only the CSS for blocks that render is loaded.
- **Inline CSS up to 40KB.** Since 6.9 core inlines stylesheets that have `path`
  data until the page total reaches 40KB (up from 20KB). Removing all
  render-blocking CSS from Twenty Twenty-Five improved LCP by about 36% on Fast 4G.
- **Script modules at low priority.** Interactive block modules get
  `fetchpriority="low"` and can print in the footer.
- **Hidden and empty blocks drop their assets** (6.9).
- **Server-side skip link** (7.0). Core inserts "Skip to content" before the
  template and points it at the `<main>` element's `id`. Every template here has
  `<main id="main">`, so it needs no JavaScript.
- **PHP-only block registration** (7.0). `"supports": { "autoRegister": true }`
  plus a `render` file makes a block appear in the editor with auto-generated
  inspector controls (string, number, integer, boolean, enum). All three theme
  blocks use this, so there is no editor JavaScript and no build.
- **Speculative loading** has been in core since 6.8 (tuned by plugin, see below).

## Theme vs plugin layer

**The theme owns its own efficiency only:** lean markup, theme.json tokens, small
inlineable CSS, 0KB JavaScript by default, cheap PHP, and lightweight custom
blocks (popover nav, inline SVG icons, video facade).

**Plugins own site-wide speed and cleanup.** The theme contains none of this:

- removing the emoji script, wp-embed, oEmbed discovery links, RSD, WLW manifest,
  shortlink, generator tag, dashicons, classic-theme-styles, global-styles output
- deregistering jQuery or any core script
- speculative loading or speculation rules config
- view transitions
- preload, preconnect and other resource hints, lazy-load and fetchpriority tweaks
- page caching, minifying core assets, image format conversion
- changing `styles_inline_size_limit`

Why:

- **Swappable.** Hosts differ. LiteSpeed, WP Rocket, Perfmatters or a host cache
  each do this job their own way. The right one is chosen per project.
- **Independent upgrades.** Plugins track core changes on their own release
  cycle. A theme that hardcodes cleanup goes stale and has to be patched in every
  derived theme.
- **No double handling.** A theme that removes emoji or rewrites resource hints
  fights the cache plugin doing the same thing, and debugging which one won is
  wasted time.
- **Portable.** The theme behaves the same on any host and never breaks because a
  plugin was added or removed. The theme depends on no plugin.

What you will see on a bare install with no plugins: core's emoji loader, the
speculation rules script and core's default preset CSS (see Measured baseline).
That is expected. The plugin layer removes them.

## Recommended plugin layer (per project)

Pick per project. The theme ships none of these and requires none of them.

- **Performance Lab feature plugins** (from the WordPress Performance Team):
  - View Transitions: smooth page-to-page transitions.
  - Modern Image Formats: AVIF/WebP uploads.
  - Enhanced Responsive Images: better `sizes` values.
  - Image Prioritizer: fixes lazy-loading and fetchpriority on the real LCP image
    (this covers the video facade thumbnail, which is lazy by default).
  - Optimization Detective: the measurement layer Image Prioritizer uses.
- **Speculative Loading**: in core since 6.8. Install the plugin only to tune
  eagerness or switch to prerender.
- **One cleanup/asset plugin**: Perfmatters, or the host's cache plugin
  (LiteSpeed Cache, WP Rocket). Use it for emoji, dashicons, embeds, head links,
  per-page script control. Only one, so they do not overlap.
- **Server**: PHP 8.4+, OPcache, Redis object cache, server-level page cache
  (nginx fastcgi_cache or LiteSpeed). TTFB is a hosting problem first.

## UI tiers

**Rule: vanilla first.** Build everything with plain HTML, CSS and JS. Only
native browser features (tier 1) and plain CSS (tier 2, first bullet) are allowed
by default. Use anything in tiers 2 to 4 only when the project owner explicitly
asks for it **and** the vanilla version genuinely cannot do the job. Every
exception is logged in `docs/EXCEPTIONS.md`. Never add a library for convenience,
speed of building, or just in case.

The tiers below are copied from the research note.

Sizes are approximate gzipped figures. Check on bundlephobia before adopting anything.

### Tier 1: Native browser features (0KB, default)
- `popover` for menus, dropdowns and toasts
- `<dialog>` for modals and off-canvas panels
- `<details>`/`<summary>` for accordions and FAQs
- CSS anchor positioning for tooltips
- Scroll-snap for sliders and carousels
- Scroll-driven animations and `@starting-style` for reveals and entrance effects
- `@view-transition` for page-to-page transitions
- `:has()`, container queries, native nesting, `@layer`
- Vanilla JS (ES modules, no framework) for any small behaviour native features can't cover

### Tier 2: CSS approaches (no runtime JS). Only plain CSS by default
- **Default:** plain CSS driven by theme.json tokens, written in the CUBE CSS / Every Layout style. Those are ways of writing CSS, not dependencies.
- Utopia: a generator for fluid type and spacing `clamp()` values. The output is pasted into theme.json, so nothing is installed.
- On request only: Open Props, modern-normalize (~1KB), Pico CSS (~10KB), UnoCSS, Tailwind v4 (the last resort, since tokens end up in two places)

### Tier 3: Small JS add-ons. On request only, loaded only on pages that use them
- lite-youtube-embed (~3KB): the player loads only when clicked
- Embla Carousel (~7KB): only if scroll-snap genuinely isn't enough
- Floating UI (~3-5KB): fallback for browsers without anchor positioning
- Motion `animate` (~2.5KB+): only for animations CSS can't do
- PhotoSwipe v5: lightbox that loads its code only when opened. Check core's image lightbox first.
- Icons: hand-placed inline SVG by default. A Lucide, Phosphor or Heroicons sprite only on request. Never icon fonts.
- Fonts: self-host by hand. Fontsource and glyphhanger/subfont are allowed as build-time tools.

### Tier 4: Interactivity frameworks. Only for UI that has to track state
- WP Interactivity API (~13KB, already in core): the only tier-4 option that's pre-approved, for filters, live search and cart, after a vanilla JS attempt has been ruled out
- On request only: Preact (~4KB), Lit (~5KB), petite-vue (~6KB, barely maintained), htmx (~14KB), Alpine.js (~15KB, classic themes only)

### Never use
- jQuery and anything that depends on it
- Swiper, Slick, Owl Carousel
- GSAP sitewide
- Lenis or any smooth-scroll library (hurts INP and accessibility)
- Bootstrap, Bulma, other full UI kits
- Shoelace / Web Awesome as a base layer
- Icon fonts on the front end (Font Awesome, Dashicons)
- instant.page or Quicklink (duplicate core speculative loading)
- React on the front end

### Default build
Tier 1 plus plain CSS with theme.json tokens and inline SVG. No third-party front-end dependencies. Target: 0KB JS on most pages. Any exception has to be requested, justified and documented.


## Rejected options

- **Sage 11 (Blade, Acorn, Vite, Tailwind v4).** Great developer experience, but
  booting the Laravel container adds PHP work to every uncached request, and
  utility classes fight theme.json and editor styles.
- **Tailwind.** Tokens end up in two places (theme.json and the Tailwind config),
  the editor needs its own build, and utility classes do not map to block
  supports. Last resort in tier 2, never the default.
- **Alpine.js.** A second runtime that duplicates the Interactivity API. Only worth
  it on classic themes.
- **Page builders.** Heavy markup and per-page CSS/JS, out of scope.
- **Core Navigation block.** Loads the Interactivity API runtime (about 13KB) for
  its overlay. The theme's `vanilla-starter/nav` block does the same job with a
  native `popover` and no JavaScript.
- **Bundlers.** Nothing to bundle: CSS ships as source and blocks are PHP-only.

## How the theme stays efficient

- **CSS.** `assets/css/global.css` (reset in `@layer reset`, accessibility basics,
  layout primitives) plus one file per restyled core block in
  `assets/css/blocks/core-<block>.css`, attached with `wp_enqueue_block_style()`
  so it loads only where that block renders. Theme block CSS lives next to the
  block (`blocks/*/style.css`) and core registers it from block.json. Every
  stylesheet has `path` data, so core inlines it.
- **JavaScript.** None by default. The only script is the video facade's 715 byte
  module, which core loads only on pages where that block renders.
- **PHP.** No remote requests and no directory scans on a warm request. The
  only queries the theme adds are the menu lookups the nav block needs. The block stylesheet list is cached in a non-expiring transient
  keyed by theme version (autoloaded, so no extra query); `local` and
  `development` environments skip the cache. Theme blocks come from a fixed list.
  Icons come from an allowlist and are cached per request and in the object cache.
- **Markup.** Templates use core blocks and three small theme blocks. No wrapper
  divs for styling hooks.

## Targets

- Theme CSS under 40KB raw, all inlined, 0 render-blocking theme stylesheets.
- 0KB JavaScript on a normal page. Any theme JS file under 2KB, loaded only
  where its block renders.
- Lighthouse mobile: LCP under 1500ms, CLS 0, TBT under 50ms, total JS under 5KB
  (`.lighthouserc.json`).
- AA contrast on the default palette. Lowest pair: accent on surface, 6.16:1.

## Measured baseline (0.1.0, WordPress 7.1.3, PHP 8.4, Playground, no plugins)

- Front page (hero, feature grid, FAQ, CTA): 52.9KB HTML (11.6KB gzip),
  0 render-blocking stylesheets, 37.7KB inline CSS in total, of which 7.2KB is
  the theme. Scripts: only core's speculation rules and emoji loader.
- Single post with the video facade: 46.7KB HTML, 7.9KB theme CSS, plus the
  715 byte facade module.
- `global-styles` from core is the largest inline style (about 17KB). About 6KB
  of it is core's default palette, gradient, shadow and aspect-ratio presets,
  which core keeps printing for older content even with the defaults turned off
  in theme.json. Trimming that is a global-styles filter, which is plugin-layer
  work.
- Headroom: the page is close to core's 40KB inline limit, mostly because of core
  CSS. If you add a lot of block CSS, watch for styles falling back to
  `<link>` tags (see the budget check in `docs/REUSE.md`).

## Open questions

- No independent 2026 benchmark compares block and classic themes after 6.9.
  Measure your own.
- The current gzipped size of the Interactivity API runtime has not been verified.
