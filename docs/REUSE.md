# Reusing the starter

How to start a project from this theme, work on it day to day, and keep it in
sync with the starter.

## Start a new project

```sh
git clone https://github.com/goodmonks/wp-vanilla-starter.git acme-theme
cd acme-theme
git remote rename origin starter        # keep the starter as a remote for updates
bin/rename.sh acme-theme "Acme Theme"   # optional 3rd arg: PHP/CSS prefix (default: initials, "at")
git commit -am "Rename starter to Acme Theme"
git remote add origin <your project repo URL>
```

`rename.sh` rewrites the slug, text domain, block names, style handles, PHP
namespace, the `vs_`/`vs-` prefix and the theme name in every tracked code file.
It leaves `docs/` and `CHANGELOG.md` alone because they describe the starter.
Check the result with `git diff --stat`, then:

1. **Tokens.** Edit `theme.json`: palette (keep `base`, `surface`, `muted`,
   `contrast`, `accent` slugs so patterns keep working), `layout.contentSize` and
   `wideSize`. Check AA contrast for every text/background pair you use.
2. **Type and space scales.** Generate new values at https://utopia.fyi (type and
   space calculators) and paste the `clamp()` strings into `fontSizes` and
   `spacingSizes`. Keep the slugs.
3. **Fonts.** System stack by default. For one self-hosted variable font, follow
   `assets/fonts/README.md`.
4. **style.css header.** Description, Theme URI, Author.
5. **Plugin layer.** Set it up per host (see below).

## Local development

### WordPress Playground CLI (no local server needed)

From the theme folder:

```sh
npx @wp-playground/cli@latest server \
  --mount=.:/wordpress/wp-content/themes/vanilla-starter \
  --wp=latest --php=8.4 --login \
  --blueprint=bin/playground.json
```

Open http://127.0.0.1:9400. The blueprint turns on `WP_DEBUG`, activates the
theme, and creates a Home page built from the patterns, an About page, a Blog
page, a sample post with the video facade, and a primary menu with a submenu.
Theme files are mounted live, so edits show on reload. If you get a login
screen, the user is `admin` / `password`. The site is thrown away when the
process stops.

Add `--define WP_ENVIRONMENT_TYPE local` to skip the block stylesheet cache while
you add new `core-*.css` files.

### A normal local WordPress (LocalWP, DDEV, wp-env, MAMP)

Symlink or clone the theme into `wp-content/themes/` and activate it. In
`wp-config.php` set:

```php
define( 'WP_ENVIRONMENT_TYPE', 'local' ); // new core-*.css files show up without a cache bump
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

Install Query Monitor to see queries and enqueued assets per page.

## Day-to-day: where things go

| You want to add | Put it in | Notes |
| --- | --- | --- |
| A page layout | `templates/<name>.html` | Keep `<main id="main">` (the skip link targets it). Header and footer via `wp:template-part`. |
| Header or footer variant | `patterns/header-*.php`, `patterns/footer-*.php` | `Block Types: core/template-part/header` makes it a swap option for the part. |
| A section | `patterns/<name>.php` | Add `vanilla-starter` to `Categories`. Wrap strings in `esc_html_e()`. Use preset slugs, not hex values. |
| Styles for a core block | `assets/css/blocks/core-<block>.css` | File name is the block name without `core/`, e.g. `core-cover.css`. Picked up automatically and loaded only where the block renders. Bump `Version` in `style.css` on production so the cache refreshes. |
| Site-wide CSS | `assets/css/global.css` | Only rules every page needs. Prefer theme.json first. |
| A custom block | `blocks/<name>/` with `block.json`, `render.php`, optional `style.css` | Add the folder name to the list in `inc/blocks.php`. Use `"autoRegister": true` for editor controls with no JS. |
| Behaviour that needs JS | `blocks/<name>/view.js` as `viewScriptModule` | Vanilla ES module, under 2KB, try native HTML first. |
| An icon | `assets/icons/<name>.svg` | 24x24 viewBox, `stroke="currentColor"`, `width="1em" height="1em"`. Add the name to `ICONS` in `inc/blocks.php` and to the `enum` in `blocks/icon/block.json`. |
| Layout helpers | classes `stack`, `cluster`, `sidebar`, `grid`, `center`, `cover` | Add in the block's "Additional CSS class(es)". Tune with `--space`, `--min`, `--measure`, etc. |

`front-page.html` shows the content of the static front page (Settings >
Reading > "A static page"). If a site shows latest posts on the front page,
delete `front-page.html` so `home.html` takes over.

## Pulling improvements from the starter

```sh
git fetch starter
git log --oneline HEAD..starter/main          # what changed upstream
git merge starter/main                        # take everything, or
git cherry-pick <sha>                         # take one fix
```

Because `rename.sh` changed the slug everywhere, expect conflicts on lines that
contain the slug, prefix or namespace. Keep your side for the name, take the
starter's side for the logic. Running `bin/rename.sh` again after a merge is safe:
it only rewrites leftover starter identifiers.

To keep merges cheap:

- Keep project-specific work out of shared files where you can. Add new patterns,
  templates, `core-*.css` files and blocks instead of editing the starter's.
- Put project tokens in `theme.json` (expected to diverge) and project CSS in new
  block files rather than `global.css`.
- Do not reformat starter files.

## Checks before shipping

```sh
bin/budget.sh                                  # theme CSS under 40KB, each JS file under 2KB
find . -name '*.php' -not -path './vendor/*' -exec php -l {} \;
npx @lhci/cli@latest autorun                   # optional, against a running site
```

`.lighthouserc.json` asserts mobile LCP under 1500ms, CLS 0, TBT under 50ms and
under 5KB of JS on the front page and post 1. Change the `url` list to your
staging URLs. Run it against a logged-out, cached page with the plugin layer
active, since that is what visitors get.

Also check in the browser: view source and confirm there is no
`<link rel="stylesheet">` from the theme (everything should be inline
`<style id="vanilla-starter-...">`), and that no `<script>` loads on pages that
do not use the video facade.

Optional: `phpcs` with WordPress-Extra (`phpcs.xml.dist`) if you have
`wp-coding-standards/wpcs` installed globally.

## Plugin layer setup per project

The theme does no cleanup and no site-wide speed work. On each project:

1. Server: PHP 8.4+, OPcache, Redis object cache, page cache.
2. Performance Lab, then activate: Image Prioritizer (installs Optimization
   Detective), Modern Image Formats, Enhanced Responsive Images, View Transitions.
3. Speculative Loading plugin only if you want to change core's defaults.
4. One cleanup/asset plugin: Perfmatters or the host's cache plugin. Turn off
   emoji, embeds, dashicons for visitors, unused head links. Leave CSS inlining
   alone: core already inlines the theme's CSS.

See `docs/STACK.md` for the reasons.

## Release checklist

- [ ] `Version` bumped in `style.css` (also refreshes the block stylesheet cache)
      and in each changed `blocks/*/block.json`.
- [ ] `CHANGELOG.md` updated.
- [ ] `bin/budget.sh` passes.
- [ ] `php -l` clean, no notices with `WP_DEBUG` on.
- [ ] No em dashes in copy or docs.
- [ ] Every template and pattern opens in the editor without "unexpected content".
- [ ] Keyboard check: skip link, menu button, popover close, submenus, FAQ.
- [ ] Lighthouse budgets pass on staging with the plugin layer active.
- [ ] Any new library is logged in `docs/EXCEPTIONS.md`.
- [ ] Build the zip without dev files: `git archive -o theme.zip HEAD`
      (`.gitattributes` excludes `bin/`, `docs/` and dotfiles).

## Logging an exception

A third-party library is allowed only when the project owner asks for it and
the vanilla version genuinely cannot do the job. Before merging, add an entry to
`docs/EXCEPTIONS.md` with: library and version, why vanilla failed (what you
tried), gzipped size, which pages load it, who approved it, and the date. Load it
only on the pages that need it (a block `viewScriptModule`, never a global
enqueue).

## Proposing an improvement back to the starter

If a fix or feature is not project-specific:

1. Make the change on a branch of the starter (not the derived theme), using the
   starter's names (`vanilla-starter`, `vs_`).
2. Keep it generic: no client copy, tokens or plugins.
3. Run the checks above, add a `CHANGELOG.md` line, open a PR on the starter.
4. Once merged, pull it into derived themes with `git fetch starter` and
   `git cherry-pick`.
