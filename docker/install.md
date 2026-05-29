# AVPS — local install (Docker)

Step-by-step setup for the **botai** repo (`master/`, `workspace/`, `documentation/`)
using the Docker stack in this directory. Run every command from **`docker/`**
unless noted otherwise.

```bash
cd /path/to/botai/docker
```

---

## 1. Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) or [OrbStack](https://orbstack.dev/) (macOS)
- Ports **80**, **443**, **3306**, and **82** free on the host (or change them in `.env`)
- Git clones of the three apps already present at the repo root:

```
botai/
├── .htaccess        ← copy from docker/.htaccess (see section 2)
├── master/
├── workspace/
├── documentation/
└── docker/          ← you are here
```

---

## 2. Root `.htaccess` (routing)

Apache serves the **repo root** (`WEB_DOCROOT=/var/www/html`). URL routing depends on a
root `.htaccess` that sends traffic to `master/`, `workspace/workspaces/<id>/`,
`documentation/`, and `master/api/web/`.

Copy the template shipped with this Docker setup to the project root (run from `docker/`):

```bash
cp .htaccess ../.htaccess
```

From the repo root instead:

```bash
cp docker/.htaccess .htaccess
```

Without this file, `https://botai/` will not rewrite requests into the Yii2 apps and
you will see **403** or directory listing errors instead of the application.

The copy in `docker/.htaccess` is the canonical local-dev version (workspace slug
rules, API path, documentation path, default route to `master/`). Keep your own
root `.htaccess` in sync when those rules change in `docker/.htaccess`.

---

## 3. Configure environment

```bash
cp .env.example .env
```

Edit `docker/.env` if needed. Defaults for this project:

| Variable | Default | Purpose |
|----------|---------|---------|
| `PROJECT_NAME` | `botai` | Container names, Apache `ServerName`, SSL cert name |
| `HTTP_PORT` / `HTTPS_PORT` | `80` / `443` | Web |
| `DB_PORT` | `3306` | MySQL on the host |
| `PMA_PORT` | `82` | phpMyAdmin |
| `DB_NAME` | `botai_master` | Database created on first MySQL start |
| `DB_USER` / `DB_PASSWORD` | `root` / `mysql` | MySQL + phpMyAdmin login |

---

## 4. Hosts file (required for `https://botai/`)

```bash
echo "127.0.0.1  botai" | sudo tee -a /etc/hosts
```

Without this line, the browser shows `DNS_PROBE_FINISHED_NXDOMAIN`.

---

## 5. Start Docker

**First time** (SSL cert + build image + start stack):

```bash
make init
```

Or manually:

```bash
bash setup.sh
docker compose up -d --build
```

**Later** (containers already built):

```bash
make up
# or
docker compose up -d
```

**Restart** after config changes:

```bash
make restart
# or
docker compose restart
```

**Stop** (keeps database volume):

```bash
make down
```

**Stop and wipe database** (destructive):

```bash
make reset
```

**Useful shortcuts:**

```bash
make ps          # container status
make logs        # tail all logs
make shell       # bash inside botai_web
make hosts       # print the /etc/hosts line again
```

**URLs after the stack is up:**

| Service | URL |
|---------|-----|
| Master (default site) | https://botai/ |
| phpMyAdmin | http://localhost:82/ or http://localhost:82/phpmyadmin/ |
| MySQL from host | `127.0.0.1:3306`, user `root`, password `mysql` |

Accept the self-signed certificate warning once in the browser.

---

## 6. Composer install

Run **inside the `botai_web` container** (paths below use `docker compose exec` from `docker/`).

Shared flags (required for this legacy Yii2 stack):

```bash
COMPOSER_FLAGS="--no-security-blocking --ignore-platform-reqs --no-interaction --prefer-dist"
```

| Flag | Why |
|------|-----|
| `--no-security-blocking` | Composer 2.7+ blocks packages with PKSA advisories (old gii, phpunit, tinymce, …) |
| `--ignore-platform-reqs` | Some deps still declare `php: ^7.x` |
| `--no-interaction` | Non-interactive; needed with `exec -T` |
| `--prefer-dist` | Faster installs (zip vs git clone) |

### Master

```bash
docker compose exec -T -w /var/www/html/master web \
  composer install --no-security-blocking --ignore-platform-reqs --no-interaction --prefer-dist
```

### Workspace

```bash
docker compose exec -T -w /var/www/html/workspace web \
  composer install --no-security-blocking --ignore-platform-reqs --no-interaction --prefer-dist
```

If Composer reports the lock file is out of sync with `composer.json`:

```bash
docker compose exec -T -w /var/www/html/workspace web \
  composer update --no-security-blocking --ignore-platform-reqs --no-interaction --prefer-dist
```

### Documentation

```bash
docker compose exec -T -w /var/www/html/documentation web \
  composer update --no-security-blocking --ignore-platform-reqs --no-interaction --prefer-dist
```

(`documentation/` often needs `update` the first time because `composer.lock` may lag `composer.json`.)

### Interactive alternative

```bash
make shell
cd /var/www/html/master    # or workspace / documentation
composer install --no-security-blocking --ignore-platform-reqs --no-interaction --prefer-dist
```

### Composer flag not found?

If you see `The "--no-security-blocking" option does not exist`, you are using host Composer &lt; 2.7. Use the `docker compose exec` commands above, or:

```bash
composer config --global audit.abandoned ignore
composer config --global audit.block-insecure ignore
composer install --ignore-platform-reqs --no-interaction --prefer-dist
```

---

## 7. Yii2 init (Development)

Materialises `frontend/web/index.php`, `backend/web/index.php`, `yii`, `*-local.php` configs, cookie keys, and writable `runtime/` dirs.

```bash
docker compose exec -T -w /var/www/html/master web \
  php init --env=Development --overwrite=All

docker compose exec -T -w /var/www/html/workspace web \
  php init --env=Development --overwrite=All

docker compose exec -T -w /var/www/html/documentation web \
  php init --env=Development --overwrite=All
```

Production templates use the same component layout; for prod deploy:

```bash
php init --env=Production --overwrite=All
```

---

## 8. Database configuration (`main-local.php`)

`php init` copies templates with placeholders (`DATABASE_NAME`, `localhost`, …).
For Docker, point connections at the **`db`** service hostname and credentials from `docker/.env`.

### Master — `master/common/config/main-local.php`

| Component | `dsn` | User / password |
|-----------|-------|-----------------|
| `db` | `mysql:host=db;dbname=botai_master` | `root` / `mysql` |
| `documentationDb` | `mysql:host=db;dbname=botai_documentation` | `root` / `mysql` |

### Workspace — `workspace/common/config/main-local.php`

| Component | `dsn` | User / password |
|-----------|-------|-----------------|
| `masterDb` | `mysql:host=db;dbname=botai_master` | `root` / `mysql` |
| `documentationDb` | `mysql:host=db;dbname=botai_documentation` | `root` / `mysql` |
| `db` | Leave placeholders or set per tenant | Resolved at runtime per workspace |

### Documentation — `documentation/common/config/main-local.php`

| Component | `dsn` | User / password |
|-----------|-------|-----------------|
| `db` | `mysql:host=db;dbname=botai_documentation` | `root` / `mysql` |

Templates live under `*/environments/dev/common/config/main-local.php` (and `prod/`) with placeholder constants — edit the generated `common/config/main-local.php` after each `init`, or patch templates before running init.

---

## 9. Import database

Create extra databases if needed (documentation, tenants):

```bash
docker compose exec -T db mysql -uroot -pmysql -e "
  CREATE DATABASE IF NOT EXISTS botai_master CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE DATABASE IF NOT EXISTS botai_documentation CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
"
```

Import the master dump (from repo root paths). **Use `utf8mb4` for the client
charset** — the `setting` table stores PHP-serialized blobs with Romanian text;
importing with the default `latin1` client corrupts those strings and Yii will
not load settings from the database (`unserialize()` fails silently).

```bash
docker compose exec -T db mysql -uroot -pmysql --default-character-set=utf8mb4 botai_master \
  < ../master/_database/botairo_master.sql
```

After import, clear Yii runtime cache (otherwise an empty settings snapshot from
before import may still be served):

```bash
docker compose exec -T web bash -lc '
  rm -rf /var/www/html/master/{frontend,backend,console,api}/runtime/cache/*
'
```

Or open a MySQL shell:

```bash
make db
# inside mysql: USE botai_master;
```

phpMyAdmin can import from **http://localhost:82/** — SQL files under `master/_database/`, `workspace/_database/`, etc. are visible via the container upload directory.

Makefile helpers (if `botai/_database/import.sql` exists):

```bash
make import    # import into DB_NAME from .env (botai_master)
make fresh     # wipe tables + re-import
```

---

## 10. Verify

```bash
make ps

curl -ksI --resolve botai:443:127.0.0.1 https://botai/
# expect HTTP response (200 once DB + config are correct; 500 until import/patch done)

docker compose exec -T web php -r "echo error_reporting(), PHP_EOL;"
# errors-only overlay from docker/php/zzz-error-reporting.ini
```

---

## 11. Workspace install/uninstall (local vs cPanel)

The backend action **Subscriber → Workspaces → Reinstall / Uninstall**
(`master/backend/modules/subscriber/controllers/WorkspaceController.php`) calls
`Workspace::install()` / `Workspace::uninstall()` in
`master/common/models/Workspace.php`.

Those methods branch on `Workspace::isLocalInstallEnvironment()`:

| Environment | Database create/drop | Crontab |
|-------------|----------------------|---------|
| `YII_ENV_DEV` (Docker, Laragon, OrbStack) | Direct `CREATE DATABASE` / `DROP DATABASE` via PDO on the `db` service | Skipped (returns `true`) |
| Production with `cPanel` component configured | `Yii::$app->cPanel->uapi->Mysql->create_database(...)` and `set_privileges_on_database(...)` | `Yii::$app->cPanel->uapi->Cron->...` |

This is why local reinstalls do **not** require valid `cPanel` credentials in
`master/common/config/main-local.php`. The `cPanel` component can stay with the
placeholder `CPANEL_BASE_URL` in dev — it is never instantiated.

If you ever see `The "baseUrl" property must be a valid URL.` during a local
reinstall, it means the dev branch was skipped. Check:

1. `YII_ENV_DEV` is `true` (default for `php init --env=Development`; verify in
   `master/backend/web/index.php` — must contain
   `defined('YII_ENV') or define('YII_ENV', 'dev');`).
2. Opcache / runtime cache is stale after editing `Workspace.php`:

   ```bash
   docker compose exec -T web bash -lc '
     rm -rf /var/www/html/master/{frontend,backend,console,api}/runtime/cache/*
     rm -rf /var/www/html/workspace/{frontend,backend,console,api}/runtime/cache/*
   '
   docker compose restart web
   ```

For prod deployments on cPanel, populate `master/common/config/main-local.php`
with real values:

```php
'cPanel' => [
    'class'    => 'tws\cpanel\CPanel',
    'baseUrl'  => 'https://your-cpanel-host:2083',
    'username' => 'cpaneluser',
    'password' => 'cpanelpass',
],
```

---

## 12. Next steps

1. **Import schemas/data** — `master/_database/botairo_master.sql`, tenant data from `workspace/_database/` as needed.
2. **Create `botai_documentation`** database and import documentation SQL when available.
3. **Workspace tenants** — URLs like `https://botai/ajvpstimis/` map via root `.htaccess` to `workspace/workspaces/<id>/`; each tenant needs its own MySQL database (see `workspace/_database/store.sql`).
4. **Re-run init carefully** — `php init --overwrite=All` overwrites `common/config/main-local.php`; re-apply Docker DB settings from section 8.
5. **Commit policy** — do not commit `vendor/`, `composer.lock` changes, or `*-local.php` with real passwords unless your team expects it.

**Quick reference — full first-time sequence:**

```bash
cd docker
cp .env.example .env
cp .htaccess ../.htaccess          # root routing (master / workspace / documentation / api)
echo "127.0.0.1  botai" | sudo tee -a /etc/hosts
make init

docker compose exec -T -w /var/www/html/master web \
  composer install --no-security-blocking --ignore-platform-reqs --no-interaction --prefer-dist
docker compose exec -T -w /var/www/html/workspace web \
  composer install --no-security-blocking --ignore-platform-reqs --no-interaction --prefer-dist
docker compose exec -T -w /var/www/html/documentation web \
  composer update --no-security-blocking --ignore-platform-reqs --no-interaction --prefer-dist

docker compose exec -T -w /var/www/html/master web php init --env=Development --overwrite=All
docker compose exec -T -w /var/www/html/workspace web php init --env=Development --overwrite=All
docker compose exec -T -w /var/www/html/documentation web php init --env=Development --overwrite=All

# patch main-local.php (section 8), then:
docker compose exec -T db mysql -uroot -pmysql --default-character-set=utf8mb4 botai_master \
  < ../master/_database/botairo_master.sql
docker compose exec -T web bash -lc 'rm -rf /var/www/html/master/{frontend,backend,console,api}/runtime/cache/*'

open https://botai/
```

---

## Reusing this Docker folder in another project

The stack is generic PHP + MySQL. Copy `docker/` into another repo, set `PROJECT_NAME` and ports in `.env`, run `make init`, and adjust `WEB_DOCROOT`:

```ini
WEB_DOCROOT=/var/www/html                 # Yii2 advanced, root .htaccess (botai default)
# WEB_DOCROOT=/var/www/html/public        # Laravel, Symfony
# WEB_DOCROOT=/var/www/html/frontend/web  # Yii2 frontend only
```

Optional image extras:

```ini
EXTRA_APT_PACKAGES=libpq-dev
EXTRA_PHP_EXTENSIONS=pdo_pgsql
```

Then `docker compose up -d --build`.
