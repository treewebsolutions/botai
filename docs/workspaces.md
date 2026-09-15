# Workspace-uri (tenanți) — provizionare

Fiecare abonat primește un *workspace*: o instanță separată a aplicației
`workspace/` (frontend + backend + console), cu propria bază de date și propriul
director. Master-ul (`master/`) creează, reinstalează și șterge aceste instanțe din
**Subscriber → Workspaces → Reinstall / Uninstall**
(`master/backend/modules/subscriber/controllers/WorkspaceController.php`), care
apelează `Workspace::install()` / `Workspace::uninstall()` din
`master/common/models/Workspace.php`.

## Unde stă un tenant

```
<root>/
├── master/                    aplicația master (hub)
├── workspace/                 codul partajat al aplicației de tenant + șabloanele de instalare
│   └── install/
│       ├── dir/               scheletul de director (partajat de toate tipurile)
│       │   ├── 1/             overlay pentru tipul 1 (Subscriber) — doar fișierele diferite
│       │   └── 2/             overlay pentru tipul 2 (Demo)
│       └── db/                SQL-urile partajate + seed-uri per tip
│           ├── 1/_02_permissions.sql
│           └── 2/_02_permissions.sql
└── workspaces/                tenanții instalați (ignorat de git, doar .gitkeep)
    └── <domeniu>/             ex. workspaces/primadentalclinic.ro/
```

Directorul tenantului este `<root>/workspaces/<domeniu>/` (alias `@base/workspaces`
în master; în aplicația de workspace alias-ul `@workspaces` indică același director).
Cheia directorului este dată de `Workspace::getDirectoryName()`:

- coloana `domain`, redusă la host-ul simplu, lowercase, fără `www.`
  (`https://www.primadentalclinic.ro/` → `primadentalclinic.ro`);
- dacă `domain` este gol, slug-ul din coloana `url` (`demo` → `workspaces/demo`).

`{{ID}}` din configurări rămâne id-ul numeric al workspace-ului (`app-frontend-<id>`),
așa că aplicația de tenant își găsește în continuare înregistrarea din master după id.

## Rutare

Master-ul scrie în `.htaccess`-ul din rădăcină câte o regulă per tenant, între
marcajele `# BEGIN Workspace Rules` / `# END Workspace Rules`
(`Workspace::updateHtaccess()`; regula nouă se adaugă imediat după `BEGIN`):

```apache
	RewriteRule ^primadentalclinic/?(.*)$ workspaces/primadentalclinic.ro/$1 [NC,L]
```

Tenantul răspunde deci la `https://<host>/<url>/` (frontend) și
`https://<host>/<url>/admin/` (backend). `.htaccess`-ul din directorul tenantului
trimite `admin/` la `backend/web/`, `uploads/` la `uploads/` și restul la
`frontend/web/`. La schimbarea slug-ului (`WorkspaceForm::saveModel()` →
`Workspace::saveUrl($urlVechi)`) se rescriu regula și valorile `baseUrl` din
configurările tenantului; dacă directorul era cheiat după `url` (fără domeniu), el
este redenumit.

`docker/.htaccess` este șablonul local al fișierului din rădăcină și conține doar
marcajele (fără reguli de tenant).

## Ce se instalează — `Workspace::install()`

1. **`installDatabase()`** — creează baza `<prefix>_<code>` (numele se derivă din
   DSN-ul master-ului, ultimul segment fiind înlocuit cu `code`), apoi importă cu
   `importSqlFile()` (fiecare instrucțiune este verificată; prima eroare oprește
   instalarea cu mesajul real, nu lasă schema pe jumătate):

   | # | Fișier | Observații |
   |---|--------|------------|
   | 1 | `workspace/install/db/_01_structure.sql` | structura tabelelor |
   | 2 | `workspace/install/db/_03_common.sql` | date comune |
   | 3 | `workspace/install/db/_04_translations.sql` | traduceri |
   | 4 | `workspace/install/db/_05_data.sql` | opțional, ignorat de git; lipsa lui nu este eroare |
   | 5 | `workspace/install/db/<tip>/*.sql` (ordonate după nume) | azi `_02_permissions.sql`, diferit între tipul 1 și 2 |

   La final userul abonatului din master este copiat în tabela `user` a tenantului
   (același id și hash de parolă) și primește rolul `superAdmin`.

2. **`installDirectory()`**
   - copiază scheletul partajat `workspace/install/dir/` în `workspaces/<domeniu>/`,
     **excluzând** directoarele per tip (`1/`, `2/` — pattern-urile sunt neancorate,
     `"1/"`, pentru că `matchPathname` nu potrivește pattern-uri ancorate la rădăcina
     copierii);
   - copiază peste el overlay-ul `workspace/install/dir/<tip>/` (dacă există; azi doar
     `uploads/.gitkeep` — aici se pun upload-urile seed specifice tipului);
   - creează link-urile simbolice din `getSymlinkMap()` cu
     `common\helpers\FileHelper::symlink()` — link-uri **relative** (rămân valide
     indiferent de rădăcina de montare: host, container, cPanel), iar pe Windows
     *directory junctions* (`mklink /J`, nu necesită drepturi de administrator):

     ```
     workspace/frontend/modules      → workspaces/<domeniu>/frontend/modules
     workspace/frontend/views        → workspaces/<domeniu>/frontend/views
     workspace/backend/modules       → workspaces/<domeniu>/backend/modules
     workspace/backend/views         → workspaces/<domeniu>/backend/views
     workspace/backend/web/{assets,audio,img,css,js,fonts}
                                     → workspaces/<domeniu>/backend/web/…
     workspace/frontend/web/{assets,img,css,fonts,js}
                                     → workspaces/<domeniu>/frontend/web/…
     ```
   - înlocuiește placeholder-ele din `common|backend|frontend|console/config/main.php`:
     `{{DB_HOST}}`, `{{DB_NAME}}`, `{{DB_USERNAME}}`, `{{DB_PASSWORD}}`, `{{ID}}`,
     `{{NAME}}` (= `code`), `{{URL}}` (folosit în `request.baseUrl` și
     `urlManager.baseUrl`: `/<url>` pentru frontend/console, `/<url>/admin` pentru backend).

3. **`updateCrontab()`** — doar pe cPanel: adaugă
   `/usr/local/bin/php <root>/workspaces/<domeniu>/yii schedule/run` la fiecare minut.
   Local este no-op; serviciul `scheduler` din `docker/docker-compose.yml` rulează
   la fiecare 60 s `yii schedule/run` pentru master și pentru fiecare
   `workspaces/*/yii`.

4. **`updateHtaccess()`** — regula de rutare de mai sus.

Orice pas eșuat este logat (`Yii::error`) și pus pe model (`$model->getErrors()`);
`actionReinstall` afișează cauza în mesajul flash / răspunsul JSON.

### Local vs cPanel

`Workspace::isLocalInstallEnvironment()` este adevărat când `YII_ENV_DEV` este activ
sau când nu există o componentă `cPanel` utilizabilă (`isCPanelConfigured()`).
Local baza de date se creează/șterge direct prin PDO (`CREATE DATABASE` /
`DROP DATABASE`; `GRANT`-ul este best-effort, MySQL 8 refuză forma veche cu
`IDENTIFIED BY`), iar crontab-ul este sărit. Verificarea `YII_ENV_DEV` se face
prima, ca să nu fie instanțiată componenta `cPanel` cu valorile placeholder din dev.

## Scheletul de instalare — adâncimea căilor

Tenantul stă cu un nivel mai jos față de vechea locație `workspace/workspaces/<id>/`,
așa că toate `require`-urile din șablon indică explicit `workspace/`:

```php
// workspaces/<domeniu>/frontend/web/index.php
require __DIR__ . '/../../../../workspace/vendor/autoload.php';   // <root>/workspace/vendor/autoload.php
require __DIR__ . '/../../common/config/bootstrap.php';           // <root>/workspaces/<domeniu>/common/config/bootstrap.php

// workspaces/<domeniu>/yii
require __DIR__ . '/../../workspace/vendor/autoload.php';         // <root>/workspace/vendor/autoload.php
```

Aplicația de tenant nu are nivel `api/`; scheletul conține doar
`common`, `backend`, `frontend`, `console`, `uploads`, `.htaccess`, `yii`, `yii.bat`.

## Dezinstalare — `Workspace::uninstall()`

Șterge baza de date (PDO sau cPanel), scoate cron-ul și regula din `.htaccess`, apoi
**întâi** elimină link-urile din `getSymlinkMap()` (`unlink`/`rmdir`, ca ștergerea
recursivă să nu intre în sursele partajate din `workspace/`, mai ales pe Windows unde
`is_link()` nu detectează junction-urile) și abia apoi `FileHelper::removeDirectory()`.

## Reîmprospătarea link-urilor

**Subscriber → Workspaces → Symlink update** (`actionSymlinkUpdate`, doar `superAdmin`)
recreează link-urile pentru toți tenanții activi care au directorul provizionat pe
mediul curent, din același `getSymlinkMap()`; link-urile existente sunt șterse și
refăcute relative (o provizionare veche le putea crea absolute).

## Fișiere relevante

- `master/common/models/Workspace.php` — `getDirectoryName()`, `getDirectoryPath()`,
  `getRelativeDirectoryPath()`, `getSymlinkMap()`, `getHtaccessRewriteRule()`,
  `isCPanelConfigured()`, `isLocalInstallEnvironment()`, `installDatabase()`,
  `importSqlFile()`, `installDirectory()`, `updateCrontab()`, `updateHtaccess()`,
  `saveUrl()`, `install()`, `uninstall()`.
- `master/common/helpers/FileHelper.php` — `symlink()` relativ / junction.
- `workspace/install/dir/`, `workspace/install/db/` — șabloanele.
- `workspace/common/config/bootstrap.php` — `@workspaces` → `<root>/workspaces`.
- `.htaccess`, `docker/.htaccess`, `docker/docker-compose.yml` (`scheduler`),
  `.gitignore` (`/workspaces/*`, `!/workspaces/.gitkeep`, `/workspace/runtime/`).
- `docker/install.md` §11 — pașii pentru mediul local.
