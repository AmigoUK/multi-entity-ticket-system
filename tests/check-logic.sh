#!/bin/bash
#
# METS Plugin Logic/Integration Checks
# Run from repo root: bash tests/check-logic.sh
#
set -euo pipefail

PLUGIN_DIR="multi-entity-ticket-system"
ERRORS=0
WARNINGS=0

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

pass() { echo -e "  ${GREEN}PASS${NC} $1"; }
fail() { echo -e "  ${RED}FAIL${NC} $1"; ERRORS=$((ERRORS + 1)); }
warn() { echo -e "  ${YELLOW}WARN${NC} $1"; WARNINGS=$((WARNINGS + 1)); }

echo "=== METS Logic/Integration Checks ==="
echo ""

# ─── 1. AJAX hooks map to existing methods ─────────────
echo "1. AJAX action hooks map to existing methods"
MISSING_METHODS=0

CORE_FILE="$PLUGIN_DIR/includes/class-mets-core.php"

# Parse AJAX hook registrations:
# Pattern: add_action( 'wp_ajax_XXX', $var, 'method_name' );
while IFS= read -r line; do
    # Extract method name: the last single-quoted string in the line
    METHOD=$(echo "$line" | grep -oE "'[a-z_]+'" | tail -1 | tr -d "'")

    # Extract callback variable: second $var in the line (first is $this->loader)
    CALLBACK_VAR=$(echo "$line" | grep -oE '\$[a-z_]+' | sed -n '2p')

    if [ -z "$METHOD" ] || [ -z "$CALLBACK_VAR" ]; then
        continue
    fi

    # Determine where to search based on callback variable
    case "$CALLBACK_VAR" in
        '$plugin_admin')
            SEARCH_DIR="$PLUGIN_DIR/admin"
            ;;
        '$plugin_public')
            SEARCH_DIR="$PLUGIN_DIR/public"
            ;;
        '$this')
            SEARCH_DIR="$CORE_FILE"
            ;;
        *)
            continue
            ;;
    esac

    # Check if the method exists
    if [ -d "$SEARCH_DIR" ]; then
        FOUND=$(grep -rl "function ${METHOD}[[:space:]]*(" "$SEARCH_DIR" --include="*.php" 2>/dev/null || true)
    elif [ -f "$SEARCH_DIR" ]; then
        FOUND=$(grep -l "function ${METHOD}[[:space:]]*(" "$SEARCH_DIR" 2>/dev/null || true)
    else
        FOUND=""
    fi

    if [ -z "$FOUND" ]; then
        fail "AJAX method not found: ${CALLBACK_VAR}->${METHOD}"
        MISSING_METHODS=$((MISSING_METHODS + 1))
    fi
done < <(grep "add_action.*wp_ajax_" "$CORE_FILE" | grep -v 'nopriv')

if [ "$MISSING_METHODS" -eq 0 ]; then
    pass "All AJAX hooks map to existing methods"
fi
echo ""

# ─── 2. Enqueued script/style files exist ──────────────
echo "2. Enqueued script/style files exist on disk"
MISSING_ASSETS=0

for file in "$PLUGIN_DIR"/admin/class-mets-admin.php "$PLUGIN_DIR"/public/class-mets-public.php; do
    if [ ! -f "$file" ]; then continue; fi

    while IFS= read -r asset_path; do
        FULL_PATH="$PLUGIN_DIR/$asset_path"
        if [ ! -f "$FULL_PATH" ]; then
            fail "Enqueued asset not found: $FULL_PATH (in $(basename "$file"))"
            MISSING_ASSETS=$((MISSING_ASSETS + 1))
        fi
    done < <(grep -oE "METS_PLUGIN_URL[[:space:]]*\.[[:space:]]*'[^']+'" "$file" \
        | sed "s/.*'\([^']*\)'.*/\1/")
done

if [ "$MISSING_ASSETS" -eq 0 ]; then
    pass "All enqueued assets exist on disk"
fi
echo ""

# ─── 3. require_once/include paths exist ────────────────
echo "3. require_once/include paths resolve to existing files"
MISSING_INCLUDES=0

while IFS= read -r -d '' file; do
    while IFS= read -r inc_line; do
        INC_PATH=$(echo "$inc_line" | sed "s/.*METS_PLUGIN_PATH[[:space:]]*\.[[:space:]]*'\([^']*\)'.*/\1/")
        if [ -z "$INC_PATH" ] || [ "$INC_PATH" = "$inc_line" ]; then continue; fi

        FULL_PATH="$PLUGIN_DIR/$INC_PATH"
        if [ ! -f "$FULL_PATH" ]; then
            LINE_NUM=$(echo "$inc_line" | cut -d: -f1)
            SHORT_FILE=$(echo "$file" | sed "s|$PLUGIN_DIR/||")
            fail "Include not found: $INC_PATH (in $SHORT_FILE:$LINE_NUM)"
            MISSING_INCLUDES=$((MISSING_INCLUDES + 1))
        fi
    done < <(grep -n 'require_once\|require \|include_once\|include ' "$file" | grep 'METS_PLUGIN_PATH' || true)
done < <(find "$PLUGIN_DIR" -name "*.php" -not -path "*/vendor/*" -print0)

if [ "$MISSING_INCLUDES" -eq 0 ]; then
    pass "All require/include paths resolve to existing files"
fi
echo ""

# ─── 4. nopriv hooks have matching priv hooks ──────────
echo "4. Public AJAX hooks: nopriv hooks have matching priv hooks"
MISSING_PRIV=0

while IFS= read -r nopriv_line; do
    ACTION_NAME=$(echo "$nopriv_line" | grep -oE "wp_ajax_nopriv_[a-z_]+" | sed 's/wp_ajax_nopriv_//')
    if [ -z "$ACTION_NAME" ]; then continue; fi

    PRIV_MATCH=$(grep "wp_ajax_${ACTION_NAME}'" "$CORE_FILE" | grep -v "nopriv" || true)
    if [ -z "$PRIV_MATCH" ]; then
        fail "nopriv hook 'wp_ajax_nopriv_${ACTION_NAME}' has no matching priv hook"
        MISSING_PRIV=$((MISSING_PRIV + 1))
    fi
done < <(grep "wp_ajax_nopriv_" "$CORE_FILE")

if [ "$MISSING_PRIV" -eq 0 ]; then
    pass "All nopriv hooks have matching priv hooks"
fi

# Check reverse: public priv hooks that may need nopriv
echo "   Checking public AJAX hooks that may need nopriv counterparts..."
while IFS= read -r priv_line; do
    ACTION_NAME=$(echo "$priv_line" | grep -oE "wp_ajax_[a-z_]+" | sed 's/wp_ajax_//')
    if [ -z "$ACTION_NAME" ]; then continue; fi

    NOPRIV_MATCH=$(grep "wp_ajax_nopriv_${ACTION_NAME}" "$CORE_FILE" || true)
    if [ -z "$NOPRIV_MATCH" ]; then
        warn "Public hook 'wp_ajax_${ACTION_NAME}' has no nopriv counterpart (may be intentional)"
    fi
done < <(grep 'wp_ajax_.*plugin_public' "$CORE_FILE" | grep -v 'nopriv')
echo ""

# ─── 5. Nonce action strings match ─────────────────────
echo "5. Nonce action strings: create matches verify"

NONCE_CREATES_FILE=$(mktemp)
NONCE_VERIFIES_FILE=$(mktemp)
trap "rm -f $NONCE_CREATES_FILE $NONCE_VERIFIES_FILE" EXIT

# Find all nonce creation calls and extract action names
grep -rhoE "wp_create_nonce\([[:space:]]*'[^']+'" "$PLUGIN_DIR" --include="*.php" \
    | grep -v '/vendor/' \
    | sed "s/wp_create_nonce([[:space:]]*'//;s/'$//" \
    >> "$NONCE_CREATES_FILE" 2>/dev/null || true

grep -rhoE "wp_nonce_field\([[:space:]]*'[^']+'" "$PLUGIN_DIR" --include="*.php" \
    | grep -v '/vendor/' \
    | sed "s/wp_nonce_field([[:space:]]*'//;s/'$//" \
    >> "$NONCE_CREATES_FILE" 2>/dev/null || true

# Find all nonce verification calls
grep -rhoE "check_ajax_referer\([[:space:]]*'[^']+'" "$PLUGIN_DIR" --include="*.php" \
    | grep -v '/vendor/' \
    | sed "s/check_ajax_referer([[:space:]]*'//;s/'$//" \
    >> "$NONCE_VERIFIES_FILE" 2>/dev/null || true

grep -rhoE "check_admin_referer\([[:space:]]*'[^']+'" "$PLUGIN_DIR" --include="*.php" \
    | grep -v '/vendor/' \
    | sed "s/check_admin_referer([[:space:]]*'//;s/'$//" \
    >> "$NONCE_VERIFIES_FILE" 2>/dev/null || true

grep -rhoE "wp_verify_nonce\([^,]+,[[:space:]]*'[^']+'" "$PLUGIN_DIR" --include="*.php" \
    | grep -v '/vendor/' \
    | sed "s/.*'//;s/'$//" \
    >> "$NONCE_VERIFIES_FILE" 2>/dev/null || true

# Unique sort both
sort -u "$NONCE_CREATES_FILE" -o "$NONCE_CREATES_FILE"
sort -u "$NONCE_VERIFIES_FILE" -o "$NONCE_VERIFIES_FILE"

# Cross-check
while IFS= read -r action; do
    if [ -z "$action" ]; then continue; fi
    if ! grep -qxF "$action" "$NONCE_CREATES_FILE"; then
        warn "Nonce '$action' is verified but not created in PHP (may be in JS)"
    fi
done < "$NONCE_VERIFIES_FILE"

while IFS= read -r action; do
    if [ -z "$action" ]; then continue; fi
    if ! grep -qxF "$action" "$NONCE_VERIFIES_FILE"; then
        warn "Nonce '$action' is created but never verified"
    fi
done < "$NONCE_CREATES_FILE"

pass "Nonce analysis complete"
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
