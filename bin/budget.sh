#!/usr/bin/env bash
# Theme asset budget. Prints raw and gzip bytes for every CSS/JS file the theme ships.
# Fails when total theme CSS (worst case: every restyled block on one page) is over
# 40KB raw, or any single JS file is over 2KB raw. Raw bytes are what core's inline
# CSS limit counts.
set -euo pipefail
cd "$(dirname "$0")/.."

css_limit=40960
js_limit=2048
css_total=0
css_gzip=0
js_total=0
fail=0

printf '%-46s %8s %8s\n' FILE RAW GZIP
while IFS= read -r file; do
	raw=$(wc -c < "$file" | tr -d ' ')
	gz=$(gzip -9c "$file" | wc -c | tr -d ' ')
	printf '%-46s %8d %8d\n' "$file" "$raw" "$gz"
	case $file in
		*.css)
			css_total=$((css_total + raw))
			css_gzip=$((css_gzip + gz))
			;;
		*.js)
			js_total=$((js_total + raw))
			if [ "$raw" -gt "$js_limit" ]; then
				echo "FAIL: $file is ${raw}B, limit ${js_limit}B"
				fail=1
			fi
			;;
	esac
done < <(find assets blocks -type f \( -name '*.css' -o -name '*.js' \) | sort)

echo
printf 'CSS total: %dB raw, %dB gzip (limit %dB raw)\n' "$css_total" "$css_gzip" "$css_limit"
printf 'JS total:  %dB raw (per-file limit %dB, loaded only where its block renders)\n' "$js_total" "$js_limit"

if [ "$css_total" -gt "$css_limit" ]; then
	echo "FAIL: theme CSS is over budget"
	fail=1
fi

[ "$fail" -eq 0 ] && echo "PASS"
exit "$fail"
