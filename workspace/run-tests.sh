#!/usr/bin/env bash
# Runs the PHPUnit suite for the workspace app with the repo-pinned PHPUnit 9 PHAR
# (tools/phpunit-9.phar); arguments are passed explicitly so no phpunit.xml is needed.
# Extra arguments are forwarded to PHPUnit (e.g. --filter CountryPersistenceTest).
#
# The suite needs the botai_workspace_test database (schema only), provisioned with:
#   tools/load-test-schema.sh workspace
# Connection overrides: TEST_DB_DSN, TEST_DB_USER, TEST_DB_PASSWORD (see tests/bootstrap.php).
#
# error_reporting matches the documented runtime contract (docker/php/zzz-error-reporting.ini
# and .github/workflows/tests.yml): only true errors are reported, so PHP warnings/notices/
# deprecations in the legacy code are not turned into yii\base\ErrorException by Yii's handler.

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
exec php -d "error_reporting=E_ERROR|E_PARSE|E_CORE_ERROR|E_COMPILE_ERROR|E_USER_ERROR|E_RECOVERABLE_ERROR" \
	"$DIR/../tools/phpunit-9.phar" --bootstrap "$DIR/tests/bootstrap.php" --colors=always "$DIR/tests" "$@"
