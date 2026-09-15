@echo off
rem Runs the PHPUnit suite for the workspace app, using the repo-pinned PHPUnit 9
rem PHAR (tools\phpunit-9.phar); arguments are passed explicitly so no phpunit.xml
rem is needed. Extra arguments are forwarded to PHPUnit.
rem
rem The suite needs the botai_workspace_test database (schema only):
rem   bash tools\load-test-schema.sh workspace
rem Connection overrides: TEST_DB_DSN, TEST_DB_USER, TEST_DB_PASSWORD (see tests\bootstrap.php).
rem
rem error_reporting matches docker\php\zzz-error-reporting.ini and the CI workflow.

php -d "error_reporting=E_ERROR|E_PARSE|E_CORE_ERROR|E_COMPILE_ERROR|E_USER_ERROR|E_RECOVERABLE_ERROR" "%~dp0..\tools\phpunit-9.phar" --bootstrap "%~dp0tests\bootstrap.php" --colors=always "%~dp0tests" %*
