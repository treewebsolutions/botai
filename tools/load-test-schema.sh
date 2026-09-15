#!/usr/bin/env bash
# (Re)creates the PHPUnit test databases from their schema sources.
#
#   tools/load-test-schema.sh                 # all three: master, workspace, documentation
#   tools/load-test-schema.sh workspace       # only the given app(s)
#
# Each database is DROPPED and recreated (utf8mb4 / utf8mb4_unicode_ci), then the
# schema is streamed in with --default-character-set=utf8mb4. Schema sources:
#
#   master         -> master/tests/_schema.sql              (mysqldump --no-data of botai_master)
#   documentation  -> documentation/tests/_schema.sql       (mysqldump --no-data of botai_documentation)
#   workspace      -> workspace/install/db/_01_structure.sql (tenant schema; falls back to
#                     workspace/install/db/1/_01_structure.sql while the move is in progress)
#
# Connection (all optional):
#   DB_HOST      default 127.0.0.1
#   DB_PORT      default 3306
#   DB_USER      default root
#   DB_PASSWORD  default mysql  (set DB_PASSWORD= for an empty password)
#   MYSQL_BIN    the mysql client command. Defaults to `mysql` when on PATH, otherwise
#                to `docker exec -i ${DB_CONTAINER:-botai_db} mysql` so the script works on
#                a host without a MySQL client (the local Docker stack).
#   DB_CONTAINER default botai_db (only used for the docker fallback)
#   DB_PREFIX    default botai — databases are named <DB_PREFIX>_<app>_test

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:-root}"
DB_PASSWORD="${DB_PASSWORD-mysql}"
DB_PREFIX="${DB_PREFIX:-botai}"
DB_CONTAINER="${DB_CONTAINER:-botai_db}"

if [ -z "${MYSQL_BIN:-}" ]; then
	if command -v mysql >/dev/null 2>&1; then
		MYSQL_BIN="mysql"
	elif command -v docker >/dev/null 2>&1 && docker ps --format '{{.Names}}' 2>/dev/null | grep -qx "$DB_CONTAINER"; then
		MYSQL_BIN="docker exec -i $DB_CONTAINER mysql"
		# Inside the container the server is local; the published host port is irrelevant.
		DB_HOST="${DB_HOST_IN_CONTAINER:-127.0.0.1}"
		DB_PORT=3306
	else
		echo "load-test-schema: no mysql client on PATH and container '$DB_CONTAINER' is not running (set MYSQL_BIN)." >&2
		exit 1
	fi
fi

# shellcheck disable=SC2206
MYSQL_CMD=($MYSQL_BIN -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER")
if [ -n "$DB_PASSWORD" ]; then
	MYSQL_CMD+=("-p$DB_PASSWORD")
fi

# The client prints "Using a password on the command line interface can be insecure"
# on every call; drop that line and keep every other stderr message.
run_mysql() {
	"${MYSQL_CMD[@]}" "$@" 2> >(grep -v 'Using a password on the command line' >&2 || true)
}

workspace_schema() {
	if [ -f "$ROOT/workspace/install/db/_01_structure.sql" ]; then
		echo "$ROOT/workspace/install/db/_01_structure.sql"
	else
		echo "$ROOT/workspace/install/db/1/_01_structure.sql"
	fi
}

schema_for() {
	case "$1" in
		master) echo "$ROOT/master/tests/_schema.sql" ;;
		documentation) echo "$ROOT/documentation/tests/_schema.sql" ;;
		workspace) workspace_schema ;;
		*) echo "load-test-schema: unknown app '$1' (expected master, workspace or documentation)" >&2; exit 1 ;;
	esac
}

APPS=("$@")
if [ ${#APPS[@]} -eq 0 ]; then
	APPS=(master workspace documentation)
fi

for app in "${APPS[@]}"; do
	db="${DB_PREFIX}_${app}_test"
	schema="$(schema_for "$app")"
	if [ ! -f "$schema" ]; then
		echo "load-test-schema: schema file not found: $schema" >&2
		exit 1
	fi
	echo "load-test-schema: recreating $db from ${schema#"$ROOT"/}"
	run_mysql -e "DROP DATABASE IF EXISTS \`$db\`; CREATE DATABASE \`$db\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
	run_mysql --default-character-set=utf8mb4 "$db" < "$schema"
done

echo "load-test-schema: done."
