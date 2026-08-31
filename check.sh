#!/usr/bin/env bash
set -euo pipefail

for file in *.php; do
  php -l "$file"
done
