#!/bin/bash

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

FAILED=0

run_check() {
    local name="$1"
    shift

    printf "${YELLOW}▶ %s${NC}\n" "$name"

    if docker compose exec app "$@"; then
        printf "${GREEN}✔ %s — OK${NC}\n\n" "$name"
    else
        printf "${RED}✘ %s — FAILED${NC}\n\n" "$name"
        FAILED=1
    fi
}

echo ""
echo "═══════════════════════════════════════"
echo "  Перевірка якості коду"
echo "═══════════════════════════════════════"
echo ""

run_check "PHPCS (PSR-12)" vendor/bin/phpcs
run_check "PHPStan (level 6)" vendor/bin/phpstan analyse --memory-limit=512M
run_check "ESLint" npx eslint assets/
run_check "Stylelint" npx stylelint 'assets/styles/**/*.css'
run_check "TwigCS Fixer" vendor/bin/twig-cs-fixer lint

echo "═══════════════════════════════════════"
if [ $FAILED -eq 0 ]; then
    printf "${GREEN}  Усі перевірки пройшли успішно ✔${NC}\n"
else
    printf "${RED}  Деякі перевірки не пройшли ✘${NC}\n"
fi
echo "═══════════════════════════════════════"
echo ""

exit $FAILED
