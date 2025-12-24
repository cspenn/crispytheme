#!/bin/zsh
# CrispyTheme Quality Assurance Runner
# Runs all quality checks and reports results
#
# Usage: ./bin/qa.sh

set -o pipefail

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Track results
declare -A results
overall_exit=0

echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}  CrispyTheme Quality Assurance${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# Run a single check
run_check() {
    local name=$1
    local cmd=$2
    echo -e "\n${YELLOW}▶ Running: ${name}${NC}"
    echo ""
    eval "$cmd"
    local exit_code=$?
    results[$name]=$exit_code
    if [[ $exit_code -ne 0 ]]; then
        overall_exit=1
        echo -e "\n${RED}✗ ${name} failed${NC}"
    else
        echo -e "\n${GREEN}✓ ${name} passed${NC}"
    fi
}

# Run all quality checks
run_check "Lint (PHPCS)" "composer lint"
run_check "Analyze (PHPStan)" "composer analyze"
run_check "Architecture (Deptrac)" "composer architecture"
run_check "Test (Pest)" "composer test"

# Print summary
echo -e "\n${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}  Summary${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

for check in "Lint (PHPCS)" "Analyze (PHPStan)" "Architecture (Deptrac)" "Test (Pest)"; do
    if [[ ${results[$check]} -eq 0 ]]; then
        echo -e "  ${GREEN}✓${NC} ${check}"
    else
        echo -e "  ${RED}✗${NC} ${check}"
    fi
done

echo ""
if [[ $overall_exit -eq 0 ]]; then
    echo -e "${GREEN}All checks passed!${NC}"
else
    echo -e "${RED}Some checks failed.${NC}"
fi
echo ""

exit $overall_exit
