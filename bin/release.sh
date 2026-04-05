#!/usr/bin/env bash
# Build a clean release ZIP for the Moodle Plugins Directory.
#
# Honors .gitattributes export-ignore entries, so non-English lang packs,
# dev tooling, and CI scaffolding are stripped automatically.
#
# Usage:
#   ./bin/release.sh            # writes eledialeitnerflow-<version>.zip to plugin root
#   ./bin/release.sh /tmp/out   # writes into given directory

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
cd "${PLUGIN_DIR}"

VERSION=$(sed -nE "s/.*\\\$plugin->release[[:space:]]*=[[:space:]]*'([^']+)'.*/\1/p" version.php)
OUT_DIR="${1:-${PLUGIN_DIR}}"
OUT_FILE="${OUT_DIR}/eledialeitnerflow-${VERSION}.zip"

mkdir -p "${OUT_DIR}"

# git archive honors .gitattributes export-ignore. The --prefix ensures the
# ZIP root is eledialeitnerflow/ (Moodle installer requires this).
git archive --format=zip --prefix=eledialeitnerflow/ --output="${OUT_FILE}" HEAD

echo "Wrote ${OUT_FILE}"
unzip -l "${OUT_FILE}" | tail -5
