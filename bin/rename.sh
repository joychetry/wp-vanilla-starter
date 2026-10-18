#!/usr/bin/env bash
# Rename the starter for a new project. Run once, right after cloning.
#   bin/rename.sh <new-slug> "<New Name>" [prefix]
# Rewrites slug, text domain, block names, handles, PHP namespace, the vs_/vs- prefix
# and the theme name in code files. docs/ and CHANGELOG.md are left alone because
# they describe the starter and are shared with it.
set -euo pipefail

slug=${1:-}
name=${2:-}
if [[ ! $slug =~ ^[a-z][a-z0-9]*(-[a-z0-9]+)*$ ]] || [ -z "$name" ]; then
	echo 'Usage: bin/rename.sh <new-slug> "<New Name>" [prefix]' >&2
	echo 'Slug: lowercase letters, digits and single hyphens, e.g. acme-theme' >&2
	exit 1
fi

# Default prefix: initials of the slug parts (acme-theme => at).
prefix=${3:-$(echo "$slug" | perl -pe 's/(?:^|-)([a-z])[a-z0-9]*/$1/g')}
if [[ ! $prefix =~ ^[a-z][a-z0-9]*$ ]]; then
	echo "Prefix must be lowercase letters and digits: $prefix" >&2
	exit 1
fi

cd "$(dirname "$0")/.."

export SLUG=$slug NAME=$name PREFIX=$prefix
export UNDER=${slug//-/_}
PASCAL=$(echo "$slug" | perl -pe 's/(?:^|-)([a-z0-9])/\U$1/g')
export PASCAL

if git rev-parse --is-inside-work-tree > /dev/null 2>&1; then
	files=$(git ls-files)
else
	files=$(find . -type f -not -path './.git/*' | sed 's|^\./||')
fi

count=0
while IFS= read -r file; do
	case $file in
		docs/* | CHANGELOG.md | bin/rename.sh | *.svg) continue ;;
	esac
	[ -f "$file" ] || continue
	perl -pi -e '
		s/VanillaStarter/$ENV{PASCAL}/g;
		s/vanilla-starter/$ENV{SLUG}/g;
		s/vanilla_starter/$ENV{UNDER}/g;
		s/Vanilla Starter/$ENV{NAME}/g;
		s/\bvs(?=[_-][a-z])/$ENV{PREFIX}/g;
		s/\bvs(?=[A-Z][a-z])/$ENV{PREFIX}/g;
	' "$file"
	count=$((count + 1))
done <<< "$files"

echo "Renamed to $NAME ($SLUG), namespace $PASCAL, prefix ${PREFIX}_ / ${PREFIX}-, in $count files."
echo "Review with: git diff --stat"
