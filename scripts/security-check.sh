#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${project_root}"

while IFS= read -r file; do
  php -l "${file}" >/dev/null
done < <(find . -type f -name '*.php' -not -path './dist/*' -print | sort)

while IFS= read -r file; do
  node --check "${file}" >/dev/null
done < <(find . -maxdepth 2 -type f -name '*.js' -not -path './dist/*' -print | sort)

if rg -n --hidden -g '!.git/**' -g '!assets/**' -g '!_archive/**' \
  "eval\\(|new Function\\(|document\\.write\\(|(?:href|src)[[:space:]]*=[[:space:]]*['\\\"]javascript:" .; then
  echo "Unsafe executable-code pattern found." >&2
  exit 1
fi

if rg -n --hidden -g '!.git/**' -g '!assets/**' -g '!_archive/**' \
  'BEGIN (RSA|OPENSSH|EC) PRIVATE KEY|bearer[[:space:]]+[A-Za-z0-9._-]{20,}' .; then
  echo "Possible credential found." >&2
  exit 1
fi

for page in index.html work.html product.html learn.html team.html contacts.html \
  case-sweet-baking.html case-ukrainian-brand.html; do
  grep -q "Content-Security-Policy" "${page}"
  # PHP-rendered static pages HTML-encode the single quotes inside the
  # attribute; browsers decode them back to the same CSP directive.
  grep -q "require-trusted-types-for" "${page}"
done

"${project_root}/scripts/build-public.sh"

echo "Security checks passed."
