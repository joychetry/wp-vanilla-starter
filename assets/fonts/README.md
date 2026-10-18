# Fonts

The theme ships no font files. The default is the system stack in `theme.json`
(`--wp--preset--font-family--sans`), which costs 0 bytes.

## Adding one self-hosted variable font

1. Get a variable WOFF2 (for example from the font's GitHub release or Fontsource).
   Subset it to the scripts you need (glyphhanger or subfont, build-time only).
   Aim for under 50KB.
2. Save it here, e.g. `assets/fonts/inter-var.woff2`.
3. Add it to `settings.typography.fontFamilies` in `theme.json`. Core prints the
   `@font-face` rule for you, so no CSS is needed:

```json
{
	"slug": "sans",
	"name": "Inter",
	"fontFamily": "Inter, system-ui, sans-serif",
	"fontFace": [
		{
			"fontFamily": "Inter",
			"fontStyle": "normal",
			"fontWeight": "100 900",
			"fontDisplay": "swap",
			"src": [ "file:./assets/fonts/inter-var.woff2" ]
		}
	]
}
```

Replace the existing `sans` entry rather than adding a second family, so every
style that uses `var:preset|font-family|sans` picks it up.

Preloading the font is a speed tweak and belongs to the plugin layer (see
`docs/STACK.md`), not the theme.
