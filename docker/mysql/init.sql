-- Runs once, on the first initialisation of the MySQL volume (docker-entrypoint-initdb.d).
-- The application databases (botai_master, botai_documentation, tenant workspaces) are
-- imported separately; this only provisions the empty PHPUnit databases so the suites
-- can run right after `docker compose up`. Load the schemas with:
--   tools/load-test-schema.sh
-- On an already-initialised volume run the same statements by hand:
--   docker exec botai_db mysql -uroot -pmysql < docker/mysql/init.sql

CREATE DATABASE IF NOT EXISTS `botai_master_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `botai_workspace_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `botai_documentation_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
