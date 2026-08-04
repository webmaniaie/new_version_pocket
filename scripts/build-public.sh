#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
output_dir="${project_root}/dist"

rm -rf -- "${output_dir}"
mkdir -p "${output_dir}/.well-known"

public_files=(
  .nojekyll
  apple-touch-icon.png
  case-sweet-baking.html
  case-ukrainian-brand.html
  contacts.html
  country-codes.js
  favicon.svg
  fx.css
  fx.js
  index.html
  learn-hooks.html
  learn.html
  product.html
  reels.css
  reels.js
  script.js
  styles.css
  team.html
  work.html
)

for file in "${public_files[@]}"; do
  cp "${project_root}/${file}" "${output_dir}/${file}"
done

cp -R "${project_root}/assets" "${output_dir}/assets"
find "${output_dir}/assets" -type f -name '.DS_Store' -delete
find "${output_dir}/assets/posts" -type f -name '.*' -delete
cp "${project_root}/.well-known/security.txt" "${output_dir}/.well-known/security.txt"

php "${project_root}/scripts/build-static-blog.php" "${output_dir}"

# GitHub Pages must never publish server source, drafts, credentials or docs.
if find "${output_dir}" -type f \( \
  -name '*.php' -o -name '*.md' -o -name '.htaccess' -o \
  -name '.env*' -o -name '*.key' -o -name '*.pem' -o -name '*.sql' \
\) -print -quit | grep -q .; then
  echo "Public build contains a forbidden private/server file." >&2
  exit 1
fi

echo "Public build ready: ${output_dir}"
