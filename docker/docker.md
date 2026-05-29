# Docker Setup (reusable across projects)

Self-contained Docker stack for any Yii2 advanced project (or any PHP +
MySQL project really). Everything Docker-related lives in **this one
directory** — drop the whole `docker/` folder into a new repo, tweak
`.env`, and you're done.

Serves the app at `https://<PROJECT_NAME>/` and phpMyAdmin at
`http://localhost:<PMA_PORT>/`.

Compatible with **Docker Desktop** (macOS/Windows), **OrbStack** (macOS)
and **Docker Engine** (Linux).

## Layout

Everything in `docker/` is self-contained. The only two things that live
outside are the app source (mounted as the project root) and
`_database/` (the SQL dump directory, bind-mounted into phpMyAdmin so
imports go through the filesystem instead of HTTP uploads):

```
<project>/
├── _database/                   ← SQL dumps (import.sql / structure.sql) — mounted into PMA
├── .vscode/settings.json        ← keeps _database/.env/etc. visible in Cursor/VSCode
└── docker/                      ← everything in this folder is reusable
    ├── docker-compose.yml       ← orchestration (run with `cd docker && docker compose ...`)
    ├── Makefile                 ← convenience shortcuts (run with `cd docker && make ...`)
    ├── Dockerfile               ← PHP 8.3 + Apache image used by `web`
    ├── docker.md                ← this file
    ├── setup.sh / setup.bat     ← self-signed SSL cert generator
    ├── .env.example             ← copy to .env in this folder and tweak
    ├── .gitignore               ← keeps .env + generated certs out of git
    ├── apache/
    │   ├── apache.conf          ← vhost using ${PROJECT_NAME}
    │   └── ssl/                 ← generated <PROJECT_NAME>.crt / .key (git-ignored)
    ├── phpmyadmin/
    │   ├── apache.conf          ← vhost exposing / and ${PMA_SUB_URI}
    │   ├── config.user.inc.php  ← PMA overrides (FK disable, server-side UploadDir, …)
    │   └── php.ini              ← PHP overrides (upload/memory/exec limits)
    └── mysql/
        └── init.sql             ← optional SQL run on first boot
```

The Compose project name is set to `${PROJECT_NAME}` via the top-level
`name:` key, so multiple projects with different names coexist as
distinct stacks.

## 1. Configure

From the project root:

```bash
cd docker
cp .env.example .env
```

Then edit `docker/.env`:

| Variable              | Default           | What it does                                                                                                                                        |
|-----------------------|-------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------|
| `PROJECT_NAME`        | `botai`       | Compose project name, container prefix, Apache ServerName, SSL cert filename                                                                        |
| `HTTP_PORT`           | `80`              | Host port for HTTP                                                                                                                                  |
| `HTTPS_PORT`          | `443`             | Host port for HTTPS                                                                                                                                 |
| `DB_PORT`             | `3306`            | Host port for MySQL                                                                                                                                 |
| `PMA_PORT`            | `82`              | Host port for phpMyAdmin                                                                                                                            |
| `DB_NAME`             | `${PROJECT_NAME}` | Database created on first boot                                                                                                                      |
| `DB_USER`             | `root`            | MySQL user (kept at `root` to match Laragon defaults)                                                                                               |
| `DB_PASSWORD`         | *(empty)*         | Password for `DB_USER`                                                                                                                              |
| `DB_ROOT_PASSWORD`    | *(empty)*         | Root password (kept empty in dev)                                                                                                                   |
| `PMA_SUB_URI`         | `/phpmyadmin/`    | Sub-URI under which phpMyAdmin is also reachable                                                                                                    |
| `WEB_DOCROOT`         | `/var/www/html`   | Apache DocumentRoot. Use `/var/www/html/public` for Laravel/Symfony, `/var/www/html/frontend/web` to serve Yii2's frontend directly. No config edit required. |
| `EXTRA_APT_PACKAGES`  | *(empty)*         | Extra apt packages baked into the `web` image (e.g. `libpq-dev libwebp-dev`). Build arg — requires `--build` after changing.                        |
| `EXTRA_PHP_EXTENSIONS`| *(empty)*         | Extra PHP extensions installed on top of the defaults (e.g. `pdo_pgsql redis imagick`). Build arg — requires `--build` after changing.              |

## 2. Start

### macOS / Linux (recommended: use the Makefile)

```bash
cd docker
make init          # generate SSL cert + build image + start stack
make hosts         # prints the /etc/hosts line
echo "127.0.0.1 $(grep PROJECT_NAME .env | cut -d= -f2)" | sudo tee -a /etc/hosts
```

Or without `make`:

```bash
cd docker
bash setup.sh                                  # generates apache/ssl/<PROJECT_NAME>.{crt,key}
echo "127.0.0.1 <PROJECT_NAME>" | sudo tee -a /etc/hosts
docker compose up -d --build
```

**OrbStack** (macOS, drop-in Docker Desktop replacement) works out of
the box — the Compose file is vanilla Docker. See the *OrbStack notes*
section below for the `*.orb.local` auto-domain behaviour.

**Trust the self-signed cert on macOS:** double-click
`docker/apache/ssl/<PROJECT_NAME>.crt` → Keychain Access → locate the
cert → right-click → *Get Info* → *Trust* → set *Secure Sockets Layer
(SSL)* to *Always Trust*. Browsers stop showing the warning.

### Windows + Laragon

Stop Laragon first (tray → *Stop All*) to free ports `80`, `443`,
`3306`.

```powershell
cd docker
.\setup.bat
# Add '127.0.0.1 <PROJECT_NAME>' to C:\Windows\System32\drivers\etc\hosts (Notepad as Admin)
docker compose up -d --build
```

## 3. Access

- Frontend:   `https://<PROJECT_NAME>/`
- Backend:    `https://<PROJECT_NAME>/admin`
- phpMyAdmin: `http://localhost:<PMA_PORT>/` and `http://localhost:<PMA_PORT><PMA_SUB_URI>`

## 4. Database: import / wipe / fresh

Put your dump in `<project>/_database/import.sql` (preferred) or
`structure.sql`. That folder is bind-mounted into both the `db`
container and — read-only — into phpMyAdmin at `/var/uploads`, so you
can also pick files from phpMyAdmin's *Import → Web server upload
directory* dropdown (much faster than HTTP uploads).

```bash
cd docker
make import        # import _database/import.sql (or structure.sql) as-is
make fresh         # DROP all tables, then import — non-interactive, safe to re-run
make wipe          # DROP all tables, keep the database + user (asks for 'yes')
make reset         # docker compose down -v — destroys the MySQL volume entirely
```

All three DB-mutation targets temporarily disable foreign key checks,
so they work even on heavily-related schemas.

Manual one-liner equivalents:

```bash
cd docker
docker compose exec -T db mysql -u"$(grep DB_USER .env | cut -d= -f2)" \
                              "$(grep DB_NAME .env | cut -d= -f2)" \
                              < ../_database/import.sql
```

## 5. Yii2 initialization (first time only)

If this is a fresh clone of a Yii2 advanced project, the `vendor/` and
`frontend/web/index.php` don't exist yet. Populate them from inside the
`web` container so everything uses the containerized PHP 8.3:

```bash
cd docker
make shell                                # opens bash in the web container
# inside the container:
composer install                          # or: composer update --ignore-platform-reqs --no-security-blocking
./init --env=Development --overwrite=All
exit
```

Then point Yii at the Docker MySQL — edit `common/config/main-local.php`:

```php
'db' => [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=db;dbname=botai',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8',
],
```

(The hostname `db` is the Compose service name, resolvable from inside
`web`.)

### Loosening PHP's error behaviour

Yii2 turns every PHP notice/warning into an exception, which on PHP 8+
makes trivial things (like an `$_SESSION['email']` access) crash the
whole page. If you want **only real errors** to break the app, prepend
this to `frontend/web/index.php` and `backend/web/index.php`:

```php
error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);
```

Do the same in `environments/dev/{frontend,backend}/web/index.php` so
the change survives `./init`.

## Services

| Service      | Container                      | Image                | Default ports |
|--------------|--------------------------------|----------------------|---------------|
| `web`        | `${PROJECT_NAME}_web`          | local build (PHP 8.3 + Apache) | 80, 443 |
| `db`         | `${PROJECT_NAME}_db`           | `mysql:8.0`          | 3306          |
| `phpmyadmin` | `${PROJECT_NAME}_phpmyadmin`   | `phpmyadmin:5-apache`| 82 → 80       |

All containers share the `app_net` bridge network.

The `web` container receives `PROJECT_NAME` as an Apache environment
variable, so the vhost (`apache/apache.conf`) can use
`ServerName ${PROJECT_NAME}` and SSL paths
`/etc/apache2/ssl/${PROJECT_NAME}.{crt,key}` with no rewriting when the
project changes.

`db` is launched with dev-friendly MySQL flags (large
`max-allowed-packet`, relaxed fsync) for fast large-dump imports.
phpMyAdmin is configured (via `phpmyadmin/config.user.inc.php`) to:

- disable the `phpmyadmin.net` version check (kills the `200 OK (rejected)` popup);
- extend session lifetime to 8h;
- pre-disable foreign key checks in DROP/TRUNCATE/Import dialogs, so bulk "Drop all tables" from the Structure page actually works;
- surface `<project>/_database/` as a server-side upload directory for instant imports.

## Make targets

Run from inside `docker/`:

```
make init     - first-time setup: SSL cert + build images + start stack
make ssl      - regenerate the self-signed SSL cert
make up       - start containers
make down     - stop containers (keeps DB volume)
make restart  - restart containers
make build    - rebuild the web image after a Dockerfile change
make ps       - list running containers
make logs     - tail all logs
make shell    - bash into the web container
make db       - mysql CLI inside the db container
make import   - import ../_database/import.sql (or structure.sql)
make fresh    - wipe + re-import in one shot (non-interactive)
make wipe     - drop ALL tables in the DB (keeps DB + user), asks for confirmation
make hosts    - print the /etc/hosts line to add
make reset    - destroy everything including the DB volume
```

## Common raw-compose commands

```bash
cd docker

docker compose logs -f web
docker compose logs -f db

docker compose exec web bash
docker compose exec web php yii migrate

docker compose build web && docker compose up -d web

docker compose down      # keep the DB
docker compose down -v   # nuke the DB volume too
```

## OrbStack notes (macOS)

OrbStack auto-generates a hostname for every running container:

- `<container>.orb.local` → e.g. `botai_web.orb.local`
- `<service>.<project>.orb.local` → e.g. `web.botai.orb.local`

Both resolve to the container and are served with a valid TLS cert from
OrbStack's local root CA, so `https://web.botai.orb.local/` opens
without a cert warning — unlike `https://botai/` which uses the
self-signed cert in `docker/apache/ssl/`.

They don't conflict with `https://botai/`; both routes hit the same
Apache. Use whichever you prefer.

To disable the `*.orb.local` auto-domains entirely, it's a **global**
setting (no per-service toggle exists): **OrbStack menu → Settings →
Network → uncheck "Allow access to container domains & IPs"**.

## Switch back to Laragon (Windows)

```powershell
cd docker
docker compose down
# Start Laragon (tray → Start All)
```

The `hosts` entry is harmless — Laragon also serves `<PROJECT_NAME>/`
on `127.0.0.1`, so it just works once Laragon's own Apache is up.

## Troubleshooting

**`ERR_CERT_AUTHORITY_INVALID` in browser.** The cert is self-signed;
trust it via Keychain (macOS) / "Trusted Root Certification Authorities"
(Windows) — see §2 — or just click *Advanced → Proceed anyway*.

**Port already in use.** Stop Laragon / XAMPP / MAMP / system MySQL
(whichever owns the port), or change `HTTP_PORT` / `HTTPS_PORT` /
`DB_PORT` / `PMA_PORT` in `docker/.env` and `docker compose up -d`.

**phpMyAdmin shows "cannot connect".** `docker compose ps` — the `db`
container should be `(healthy)` before phpMyAdmin can log in.

**phpMyAdmin: `Error code: 200 Error text: OK (rejected)`.** The baked-in
`phpmyadmin/config.user.inc.php` already disables the version check
that triggers this. If it still happens, clear the browser cookies for
`localhost:82` and refresh.

**`make import` / phpMyAdmin: `Table 'xxx' already exists`.** You're
importing a schema dump into a non-empty DB. Use `make fresh` (drops
everything first), or in phpMyAdmin check *Disable foreign key checks*
and use the *Empty the database* dialog before importing.

**Can't drop tables due to foreign keys.** Use `make wipe` or
`make fresh` for CLI; in phpMyAdmin the *Drop* dialog ticks *Disable
foreign key checks* by default thanks to the config override.

**`_database/` or other dotfiles hidden in Cursor/VSCode.** A
`.vscode/settings.json` at the project root un-hides them.

## Reusing in another project

1. Copy the whole `docker/` folder into the new repo.
2. `cd docker && cp .env.example .env`, set `PROJECT_NAME=<new-project>`
   and adjust `WEB_DOCROOT` for the framework (defaults work for Yii2
   advanced / plain PHP / WordPress; use `/var/www/html/public` for
   Laravel/Symfony).
3. If the project needs PHP extensions the base image doesn't ship,
   list them in `EXTRA_PHP_EXTENSIONS` (and any required system libs in
   `EXTRA_APT_PACKAGES`) — no `Dockerfile` edits needed.
4. `make init` (or `bash setup.sh && docker compose up -d --build`).
5. Add `127.0.0.1 <new-project>` to your hosts file (`make hosts` prints the exact line).
6. If the project is Yii2 advanced: `make shell` → `composer install` → `./init` → edit `common/config/main-local.php` (host=`db`, user=`root`, empty password) → `make fresh` to load the dump.

No file inside `docker/` needs editing — everything project-specific
flows through `docker/.env`. See `docker/install.md` for a full
portability breakdown.
