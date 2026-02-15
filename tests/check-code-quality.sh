#!/bin/bash
#
# METS Plugin Code Quality Checks
# Run from repo root: bash tests/check-code-quality.sh
#
set -euo pipefail

PLUGIN_DIR="multi-entity-ticket-system"
ERRORS=0
WARNINGS=0

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

pass() { echo -e "  ${GREEN}PASS${NC} $1"; }
fail() { echo -e "  ${RED}FAIL${NC} $1"; ERRORS=$((ERRORS + 1)); }
warn() { echo -e "  ${YELLOW}WARN${NC} $1"; WARNINGS=$((WARNINGS + 1)); }

echo "=== METS Code Quality Checks ==="
echo ""

# ─── 1. PHP Syntax Check ───────────────────────────────
echo "1. PHP Syntax Check"
PHP_BIN="${PHP_BIN:-php}"
SYNTAX_ERRORS=0
while IFS= read -r -d '' file; do
    if ! "$PHP_BIN" -l "$file" > /dev/null 2>&1; then
        fail "Syntax error in: $file"
        SYNTAX_ERRORS=$((SYNTAX_ERRORS + 1))
    fi
done < <(find "$PLUGIN_DIR" -name "*.php" -print0)

if [ "$SYNTAX_ERRORS" -eq 0 ]; then
    pass "All PHP files pass syntax check"
fi
echo ""

# ─── 2. No debug statements in production code ─────────
echo "2. No debug statements in production code"
DEBUG_HITS=$(grep -rn 'error_log\s*(' "$PLUGIN_DIR" --include="*.php" \
    | grep -v '/vendor/' \
    | grep -v '/tests/' \
    | grep -v 'class-mets-smtp-logger' \
    | grep -v '// error_log' \
    || true)

if [ -n "$DEBUG_HITS" ]; then
    COUNT=$(echo "$DEBUG_HITS" | wc -l | tr -d ' ')
    warn "$COUNT error_log() call(s) found in production code:"
    echo "$DEBUG_HITS" | head -10 | sed 's/^/    /'
else
    pass "No error_log() calls in production code"
fi

VARDUMP_HITS=$(grep -rn 'var_dump\s*(\|print_r\s*(' "$PLUGIN_DIR" --include="*.php" \
    | grep -v '/vendor/' \
    | grep -v '/tests/' \
    | grep -v '// var_dump\|// print_r' \
    || true)

if [ -n "$VARDUMP_HITS" ]; then
    fail "var_dump/print_r found in production code:"
    echo "$VARDUMP_HITS" | head -10 | sed 's/^/    /'
else
    pass "No var_dump/print_r in production code"
fi
echo ""

# ─── 3. AJAX handlers use wp_send_json ─────────────────
echo "3. AJAX handlers use wp_send_json_success/error (not echo/die)"
AJAX_ECHO=$(grep -rn 'function ajax_' "$PLUGIN_DIR" --include="*.php" -l \
    | grep -v '/vendor/' \
    | while read -r file; do
        # Find ajax_ functions that use echo or die (excluding those in comments)
        grep -n '\becho\b\|die(' "$file" \
            | grep -v '^\s*//' \
            | grep -v 'wp_die\|echo wp_json_encode\|echo.*esc_html\|echo.*esc_attr\|ob_start\|echo.*$' \
            | while read -r line; do
                echo "  $file:$line"
            done
    done || true)

if [ -n "$AJAX_ECHO" ]; then
    warn "AJAX handlers with potential echo/die usage (review manually):"
    echo "$AJAX_ECHO" | head -10
else
    pass "AJAX handlers appear to use wp_send_json consistently"
fi
echo ""

# ─── 4. No PHP short tags ──────────────────────────────
echo "4. No PHP short tags"
SHORT_TAGS=$(grep -rn '<?\s' "$PLUGIN_DIR" --include="*.php" \
    | grep -v '<?php\|<?=\|<?xml' \
    | grep -v '/vendor/' \
    || true)

if [ -n "$SHORT_TAGS" ]; then
    fail "PHP short tags found:"
    echo "$SHORT_TAGS" | head -10 | sed 's/^/    /'
else
    pass "No PHP short tags found"
fi
echo ""

# ─── 5. Textdomain consistency ─────────────────────────
echo "5. Textdomain consistency"
# Find translation calls that don't use the correct textdomain
WRONG_DOMAIN_ACTUAL=$(grep -rnE "[^a-z](__| _e| _n| _x| _nx)\(" "$PLUGIN_DIR" --include="*.php" \
    | grep -v '/vendor/' \
    | grep -v '/tests/' \
    | grep -v 'METS_TEXT_DOMAIN' \
    | grep -v "'multi-entity-ticket-system'" \
    | grep -v '^\s*//' \
    | grep -vE 'esc_html__|esc_attr__|wp_kses' \
    | grep -vE '^\s*\*' \
    || true)

if [ -n "$WRONG_DOMAIN_ACTUAL" ]; then
    warn "Translation calls possibly using wrong textdomain:"
    echo "$WRONG_DOMAIN_ACTUAL" | head -10 | sed 's/^/    /'
else
    pass "All translation calls use correct textdomain"
fi
echo ""

# ─── 6. AJAX handlers have security checks ─────────────
echo "6. AJAX handlers have nonce/capability checks"
INSECURE_AJAX=0
while IFS= read -r -d '' file; do
    # Find all ajax_ function definitions
    while IFS= read -r func_line; do
        func_name=$(echo "$func_line" | sed 's/.*function \([a-zA-Z_]*\).*/\1/')
        line_num=$(echo "$func_line" | cut -d: -f1)

        # Read next 15 lines to check for security
        NEXT_LINES=$(sed -n "$((line_num+1)),$((line_num+15))p" "$file")
        if ! echo "$NEXT_LINES" | grep -q 'check_ajax_referer\|wp_verify_nonce\|current_user_can'; then
            fail "No nonce/cap check in $file:$func_name (line $line_num)"
            INSECURE_AJAX=$((INSECURE_AJAX + 1))
        fi
    done < <(grep -n 'function ajax_' "$file" || true)
done < <(find "$PLUGIN_DIR" -name "*.php" -not -path "*/vendor/*" -print0)

if [ "$INSECURE_AJAX" -eq 0 ]; then
    pass "All AJAX handlers have security checks"
fi
echo ""

# ─── Summary ───────────────────────────────────────────
echo "=== Summary ==="
if [ "$ERRORS" -eq 0 ] && [ "$WARNINGS" -eq 0 ]; then
    echo -e "${GREEN}All checks passed!${NC}"
elif [ "$ERRORS" -eq 0 ]; then
    echo -e "${YELLOW}$WARNINGS warning(s), 0 errors${NC}"
else
    echo -e "${RED}$ERRORS error(s), $WARNINGS warning(s)${NC}"
fi

exit "$ERRORS"
