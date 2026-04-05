#!/usr/bin/env bash
# Moodle Plugin Directory Precheck Runner for mod_eledialeitnerflow.
#
# Runs the same checks that the Moodle Plugin Directory Prechecker executes
# before a plugin can be approved, plus a couple of static blockers that are
# not caught by the automated tools (shipping non-EN lang packs, CSS namespace).
#
# Usage:
#   ./bin/precheck.sh                  # run all checks
#   ./bin/precheck.sh phplint phpcs    # run only selected checks
#
# Must be invoked from the host (macOS) — it enters the webserver container
# via docker compose automatically.

set -uo pipefail

PLUGIN_FRANK="mod_eledialeitnerflow"
PLUGIN_REL="public/mod/eledialeitnerflow"
PLUGIN_ABS_IN_CONTAINER="/var/www/site/moodle/${PLUGIN_REL}"
MOODLE_ROOT_IN_CONTAINER="/var/www/site/moodle"
DIRROOT_IN_CONTAINER="${MOODLE_ROOT_IN_CONTAINER}/public"

# ── Locate compose project (assumes script lives in <plugin>/bin/) ──────────
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# Walk up until we find compose.yml.
COMPOSE_DIR="${SCRIPT_DIR}"
while [[ "${COMPOSE_DIR}" != "/" && ! -f "${COMPOSE_DIR}/compose.yml" ]]; do
    COMPOSE_DIR="$(dirname "${COMPOSE_DIR}")"
done
if [[ ! -f "${COMPOSE_DIR}/compose.yml" ]]; then
    echo "ERROR: could not find compose.yml walking up from ${SCRIPT_DIR}" >&2
    exit 2
fi

SERVICE="webserver"
DC=(docker compose -f "${COMPOSE_DIR}/compose.yml")

run_in_container() {
    "${DC[@]}" exec -T -w "${MOODLE_ROOT_IN_CONTAINER}" "${SERVICE}" bash -lc "$1"
}

# ── Output helpers ──────────────────────────────────────────────────────────
RED=$'\e[31m'; GREEN=$'\e[32m'; YELLOW=$'\e[33m'; BLUE=$'\e[34m'; BOLD=$'\e[1m'; RESET=$'\e[0m'
declare -a RESULTS
record() {
    local name=$1 status=$2 detail=${3:-}
    RESULTS+=("${name}|${status}|${detail}")
    case "${status}" in
        PASS) echo "${GREEN}[✓]${RESET} ${BOLD}${name}${RESET} ${detail}" ;;
        WARN) echo "${YELLOW}[!]${RESET} ${BOLD}${name}${RESET} ${detail}" ;;
        FAIL) echo "${RED}[✗]${RESET} ${BOLD}${name}${RESET} ${detail}" ;;
        SKIP) echo "${BLUE}[~]${RESET} ${BOLD}${name}${RESET} ${detail}" ;;
    esac
}

section() { echo; echo "${BOLD}── $1 ──${RESET}"; }

# ── Checks ──────────────────────────────────────────────────────────────────

check_phplint() {
    section "phplint"
    local out
    out=$(run_in_container "find ${PLUGIN_ABS_IN_CONTAINER} -name '*.php' -type f -print0 | xargs -0 -n1 php -l 2>&1 | grep -v '^No syntax errors'") || true
    if [[ -z "${out}" ]]; then
        record phplint PASS "no syntax errors"
    else
        record phplint FAIL "see output below"
        echo "${out}"
    fi
}

check_phpcs() {
    section "phpcs (moodle coding style)"
    # moodle-cs is vendored in Moodle 5.x at vendor/bin/phpcs via composer.
    if ! run_in_container "test -x ${MOODLE_ROOT_IN_CONTAINER}/vendor/bin/phpcs" 2>/dev/null; then
        record phpcs SKIP "vendor/bin/phpcs not installed — run 'composer install' inside container"
        return
    fi
    local out rc
    out=$(run_in_container "vendor/bin/phpcs --standard=moodle --extensions=php,inc --report=full ${PLUGIN_REL}" 2>&1)
    rc=$?
    if [[ ${rc} -eq 0 ]]; then
        record phpcs PASS "0 errors, 0 warnings"
    else
        # Show last 60 lines for context, preserve full output below.
        echo "${out}" | tail -60
        local errors warnings
        errors=$(echo "${out}" | grep -oE '[0-9]+ ERROR' | head -1 | awk '{print $1}')
        warnings=$(echo "${out}" | grep -oE '[0-9]+ WARNING' | head -1 | awk '{print $1}')
        record phpcs FAIL "${errors:-?} errors, ${warnings:-?} warnings"
    fi
}

check_phpdoc() {
    section "phpdoc"
    # moodle-cs already runs phpdoc sniffs, but we double-check missing file/class docblocks
    # with a static grep. A file without @package in the first 40 lines is flagged.
    local missing=0
    while IFS= read -r f; do
        if ! head -40 "${f}" | grep -q "@package"; then
            echo "  missing @package: ${f##*/eledialeitnerflow/}"
            missing=$((missing+1))
        fi
    done < <(find "${COMPOSE_DIR}/site/moodle/${PLUGIN_REL}" -name '*.php' -type f)
    if [[ ${missing} -eq 0 ]]; then
        record phpdoc PASS "all files have @package"
    else
        record phpdoc FAIL "${missing} file(s) missing @package"
    fi
}

check_savepoint() {
    section "savepoint"
    local last_version plugin_version
    last_version=$(sed -nE 's/.*upgrade_mod_savepoint\(true, ([0-9]+).*/\1/p' "${COMPOSE_DIR}/site/moodle/${PLUGIN_REL}/db/upgrade.php" | tail -1)
    plugin_version=$(sed -nE 's/.*\$plugin->version[[:space:]]*=[[:space:]]*([0-9]+).*/\1/p' "${COMPOSE_DIR}/site/moodle/${PLUGIN_REL}/version.php")
    if [[ "${last_version}" == "${plugin_version}" ]]; then
        record savepoint PASS "final savepoint ${last_version} == version.php"
    else
        record savepoint FAIL "final savepoint ${last_version} != version.php ${plugin_version}"
    fi
}

check_js() {
    section "js (eslint)"
    if ! run_in_container "test -d ${MOODLE_ROOT_IN_CONTAINER}/node_modules/.bin" 2>/dev/null; then
        record js SKIP "node_modules missing — run 'npm ci' inside container"
        return
    fi
    local out rc
    out=$(run_in_container "cd ${DIRROOT_IN_CONTAINER} && npx grunt eslint --root=${PLUGIN_REL#public/}" 2>&1)
    rc=$?
    if [[ ${rc} -eq 0 ]]; then
        record js PASS "eslint clean"
    else
        echo "${out}" | tail -40
        record js FAIL "see output above"
    fi
}

check_css() {
    section "css (stylelint + namespace)"
    local styles="${COMPOSE_DIR}/site/moodle/${PLUGIN_REL}/styles.css"
    if [[ ! -f "${styles}" ]]; then
        record css SKIP "no styles.css"
        return
    fi
    # Static namespace check: every top-level class selector should sit under .path-mod-eledialeitnerflow
    # OR have a frankenstyle prefix in its name.
    local bad
    bad=$(grep -nE "^\.[a-zA-Z_-]+" "${styles}" | grep -vE "\.path-mod-eledialeitnerflow|\.eledialeitnerflow|\.lf-" || true)
    if [[ -z "${bad}" ]]; then
        record css PASS "selectors namespaced (lf- prefix or path-mod-*)"
    else
        echo "${bad}" | head -10
        record css WARN "some selectors may leak into global CSS"
    fi
    # stylelint via grunt (if present).
    if run_in_container "test -d ${MOODLE_ROOT_IN_CONTAINER}/node_modules/.bin" 2>/dev/null; then
        local out rc
        out=$(run_in_container "cd ${DIRROOT_IN_CONTAINER} && npx grunt stylelint:css --root=${PLUGIN_REL#public/}" 2>&1)
        rc=$?
        if [[ ${rc} -eq 0 ]]; then
            record css-stylelint PASS "stylelint clean"
        else
            echo "${out}" | tail -30
            record css-stylelint FAIL "see output above"
        fi
    else
        record css-stylelint SKIP "node_modules missing"
    fi
}

check_mustache() {
    section "mustache (template lint)"
    local templates
    templates=$(find "${COMPOSE_DIR}/site/moodle/${PLUGIN_REL}" -name '*.mustache' -type f)
    if [[ -z "${templates}" ]]; then
        record mustache SKIP "no mustache templates"
        return
    fi
    local out rc
    out=$(run_in_container "php admin/tool/templatelibrary/cli/mustachelint.php --filename=${PLUGIN_REL}/templates") 2>&1
    rc=$?
    if [[ ${rc} -eq 0 ]]; then
        record mustache PASS "templates clean"
    else
        echo "${out}"
        record mustache FAIL "see output above"
    fi
}

check_grunt_amd() {
    section "grunt (AMD build)"
    if ! run_in_container "test -d ${MOODLE_ROOT_IN_CONTAINER}/node_modules/.bin" 2>/dev/null; then
        record grunt SKIP "node_modules missing — run 'npm ci' inside container"
        return
    fi
    local out rc
    out=$(run_in_container "cd ${DIRROOT_IN_CONTAINER} && npx grunt amd --root=${PLUGIN_REL#public/}") 2>&1
    rc=$?
    if [[ ${rc} -eq 0 ]]; then
        record grunt PASS "AMD build clean"
    else
        echo "${out}" | tail -40
        record grunt FAIL "see output above"
    fi
}

check_thirdparty() {
    section "thirdparty (thirdpartylibs.xml)"
    local f="${COMPOSE_DIR}/site/moodle/${PLUGIN_REL}/thirdpartylibs.xml"
    if [[ ! -f "${f}" ]]; then
        # OK only if there are no vendored libs.
        local candidates
        candidates=$(find "${COMPOSE_DIR}/site/moodle/${PLUGIN_REL}" -type d \( -name 'vendor' -o -name 'lib' -o -name 'thirdparty' \))
        if [[ -z "${candidates}" ]]; then
            record thirdparty PASS "no vendored libraries, no declaration needed"
        else
            echo "${candidates}"
            record thirdparty WARN "potential third-party dirs without thirdpartylibs.xml"
        fi
        return
    fi
    if run_in_container "xmllint --noout ${PLUGIN_REL}/thirdpartylibs.xml" >/dev/null 2>&1; then
        record thirdparty PASS "thirdpartylibs.xml is valid XML"
    else
        record thirdparty FAIL "thirdpartylibs.xml invalid"
    fi
}

check_phpunit() {
    section "phpunit (plugin test suite)"
    if ! run_in_container "test -x ${MOODLE_ROOT_IN_CONTAINER}/vendor/bin/phpunit" 2>/dev/null; then
        record phpunit SKIP "vendor/bin/phpunit not installed"
        return
    fi
    if ! run_in_container "test -f ${MOODLE_ROOT_IN_CONTAINER}/phpunit.xml" 2>/dev/null; then
        run_in_container "php ${DIRROOT_IN_CONTAINER}/admin/tool/phpunit/cli/init.php" >/dev/null 2>&1 || true
    fi
    local out rc
    out=$(run_in_container "vendor/bin/phpunit --testsuite ${PLUGIN_FRANK}_testsuite 2>&1")
    rc=$?
    if echo "${out}" | grep -q 'No tests executed'; then
        record phpunit FAIL "no tests discovered for suite ${PLUGIN_FRANK}_testsuite"
        return
    fi
    if [[ ${rc} -eq 0 ]]; then
        local summary
        summary=$(echo "${out}" | grep -oE 'OK \([0-9]+ tests?, [0-9]+ assertions?\)' | head -1)
        record phpunit PASS "${summary:-green}"
    else
        echo "${out}" | tail -30
        record phpunit FAIL "tests failing"
    fi
}

# ── Static blockers (run in sandbox, no container needed) ───────────────────
check_lang_packs() {
    section "lang packs (only en allowed for approval)"
    local extra
    extra=$(find "${COMPOSE_DIR}/site/moodle/${PLUGIN_REL}/lang" -mindepth 1 -maxdepth 1 -type d ! -name en)
    if [[ -z "${extra}" ]]; then
        record lang-packs PASS "only lang/en shipped"
    else
        echo "${extra}"
        record lang-packs FAIL "non-en lang packs present (must be removed before upload)"
    fi
}

check_metadata() {
    section "metadata files"
    local base="${COMPOSE_DIR}/site/moodle/${PLUGIN_REL}"
    local missing=()
    [[ -f "${base}/README.md"  ]] || missing+=("README.md")
    [[ -f "${base}/CHANGES.md" || -f "${base}/CHANGES.txt" ]] || missing+=("CHANGES.md")
    [[ -f "${base}/upgrade.txt" ]] || missing+=("upgrade.txt")
    if [[ ${#missing[@]} -eq 0 ]]; then
        record metadata PASS "README + CHANGES + upgrade.txt present"
    else
        record metadata WARN "missing: ${missing[*]}"
    fi
}

# ── Orchestration ───────────────────────────────────────────────────────────
main() {
    local -a to_run
    if [[ $# -eq 0 ]]; then
        to_run=(lang_packs metadata savepoint phpdoc phplint phpcs js css mustache grunt_amd thirdparty phpunit)
    else
        to_run=("$@")
    fi
    for c in "${to_run[@]}"; do
        case "${c}" in
            phplint|phpcs|phpdoc|savepoint|js|css|mustache|grunt_amd|thirdparty|phpunit|lang_packs|metadata)
                "check_${c}"
                ;;
            *)
                echo "unknown check: ${c}" >&2
                ;;
        esac
    done

    # ── Summary ─────────────────────────────────────────────────────────────
    echo
    echo "${BOLD}═══ Precheck Summary ═══${RESET}"
    local pass=0 warn=0 fail=0 skip=0
    for r in "${RESULTS[@]}"; do
        IFS='|' read -r name status detail <<< "${r}"
        case "${status}" in
            PASS) pass=$((pass+1)); printf "  ${GREEN}✓${RESET}  %-18s %s\n" "${name}" "${detail}" ;;
            WARN) warn=$((warn+1)); printf "  ${YELLOW}!${RESET}  %-18s %s\n" "${name}" "${detail}" ;;
            FAIL) fail=$((fail+1)); printf "  ${RED}✗${RESET}  %-18s %s\n" "${name}" "${detail}" ;;
            SKIP) skip=$((skip+1)); printf "  ${BLUE}~${RESET}  %-18s %s\n" "${name}" "${detail}" ;;
        esac
    done
    echo
    printf "  ${GREEN}%d passed${RESET}   ${YELLOW}%d warnings${RESET}   ${RED}%d failed${RESET}   ${BLUE}%d skipped${RESET}\n" \
        "${pass}" "${warn}" "${fail}" "${skip}"
    [[ ${fail} -eq 0 ]]
}

main "$@"
