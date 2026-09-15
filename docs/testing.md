# Teste unitare (PHPUnit)

Fiecare aplicație (`master/`, `workspace/`, `documentation/`) are propria suită
PHPUnit 9, rulată cu PHAR-ul din repo (`tools/phpunit-9.phar`), fără `phpunit.xml`
și fără Codeception (scheletul Codeception din `master/` și `documentation/` este
boilerplate Yii2 neatins). Testele rulează pe baze de date dedicate, `botai_<app>_test`,
care conțin doar schema — fiecare test rulează într-o tranzacție anulată la final,
deci bazele rămân goale.

```
tools/
  phpunit-9.phar             PHPUnit 9.6 (versiune fixată în repo)
  load-test-schema.sh        (re)creează bazele *_test din sursele de schemă
<app>/
  run-tests.sh / .bat        rulează suita aplicației (argumentele merg la PHPUnit)
  tests/bootstrap.php        pornește o aplicație yii\console minimală pe baza *_test
  tests/DatabaseTestCase.php tranzacție + rollback per test, insertRow() cu auto-completare
  tests/WebTestCase.php      (master, workspace) aplicație yii\web reală pentru fluxuri de controller
  tests/support/             dubluri: FakeSettings, FakeSession, FakeAdminWebUser (master)
  tests/unit/                testele propriu-zise (namespace tests\unit)
  tests/_schema.sql          (master, documentation) mysqldump --no-data al bazei live
```

## Bazele de date de test

| Aplicație       | Bază de date               | Sursa schemei                                                                              |
|-----------------|----------------------------|--------------------------------------------------------------------------------------------|
| master          | `botai_master_test`        | `master/tests/_schema.sql` (dump `--no-data` al `botai_master`)                             |
| workspace       | `botai_workspace_test`     | `workspace/install/db/_01_structure.sql` (fallback: `workspace/install/db/1/_01_structure.sql`) |
| documentation   | `botai_documentation_test` | `documentation/tests/_schema.sql` (dump `--no-data` al `botai_documentation`)               |

Toate sunt `utf8mb4` / `utf8mb4_unicode_ci` și se încarcă cu
`--default-character-set=utf8mb4`. Pe un volum MySQL nou, `docker/mysql/init.sql`
creează bazele goale la primul boot; pe un volum deja inițializat rulează o dată:

```bash
docker exec botai_db mysql -uroot -pmysql < docker/mysql/init.sql
```

### Încărcarea schemelor

```bash
tools/load-test-schema.sh                  # toate trei
tools/load-test-schema.sh workspace        # doar una (sau mai multe, separate prin spațiu)
```

Scriptul face `DROP DATABASE` + `CREATE DATABASE` și încarcă schema. Folosește
clientul `mysql` de pe host dacă există; altfel rulează `docker exec -i botai_db mysql`
(cazul obișnuit pe macOS/Windows, unde stack-ul Docker publică MySQL pe `127.0.0.1:3306`,
root / `mysql`). Variabile opționale: `DB_HOST` (127.0.0.1), `DB_PORT` (3306), `DB_USER`
(root), `DB_PASSWORD` (mysql), `MYSQL_BIN` (comanda clientului), `DB_CONTAINER` (botai_db).

Rulează din nou după orice modificare de schemă (o coloană nouă în `_01_structure.sql`,
un tabel nou în `botai_master`). Dump-urile pentru master/documentation se regenerează cu:

```bash
docker exec botai_db mysqldump -uroot -pmysql --no-data --skip-triggers botai_master \
  | sed -E 's/ AUTO_INCREMENT=[0-9]+//; s/DEFINER=[^ ]+ //' > master/tests/_schema.sql
docker exec botai_db mysqldump -uroot -pmysql --no-data --skip-triggers botai_documentation \
  | sed -E 's/ AUTO_INCREMENT=[0-9]+//; s/DEFINER=[^ ]+ //' > documentation/tests/_schema.sql
```

(păstrează antetul de comentariu din fișier; contoarele `AUTO_INCREMENT=N` și clauzele
`DEFINER` se elimină ca dump-ul să fie stabil în git).

## Rularea testelor

### Pe host

Cerințe: PHP (8.3 este contractul de runtime; 8.5 funcționează) cu `pdo_mysql`, `intl`,
`mbstring`; `vendor/` instalat în fiecare aplicație (`composer install`); stack-ul Docker
pornit (MySQL pe `127.0.0.1:3306`).

```bash
master/run-tests.sh
workspace/run-tests.sh
documentation/run-tests.sh --filter PagePersistenceTest     # argumentele merg la PHPUnit
```

sau, din rădăcina repo-ului, direct cu PHAR-ul:

```bash
php tools/phpunit-9.phar --bootstrap master/tests/bootstrap.php master/tests
```

`run-tests.sh` / `run-tests.bat` (Windows) pornesc PHP cu
`error_reporting=E_ERROR|E_PARSE|E_CORE_ERROR|E_COMPILE_ERROR|E_USER_ERROR|E_RECOVERABLE_ERROR`,
același contract ca `docker/php/zzz-error-reporting.ini` și CI: doar erorile reale sunt
raportate, altfel handler-ul Yii transformă warning-urile/deprecation-urile din codul legacy
(sau din vendor pe PHP > 8.3) în `yii\base\ErrorException`. Când rulezi PHAR-ul direct
pe host, adaugă tu `-d "error_reporting=..."` dacă întâlnești astfel de excepții.

### În containerul `botai_web`

Codul este montat în `/var/www/html`, iar MySQL se vede ca host-ul `db`:

```bash
docker exec -e "TEST_DB_DSN=mysql:host=db;dbname=botai_master_test" -w /var/www/html botai_web \
  php tools/phpunit-9.phar --bootstrap master/tests/bootstrap.php master/tests
docker exec -e "TEST_DB_DSN=mysql:host=db;dbname=botai_workspace_test" -w /var/www/html botai_web \
  php tools/phpunit-9.phar --bootstrap workspace/tests/bootstrap.php workspace/tests
docker exec -e "TEST_DB_DSN=mysql:host=db;dbname=botai_documentation_test" -w /var/www/html botai_web \
  php tools/phpunit-9.phar --bootstrap documentation/tests/bootstrap.php documentation/tests
```

### Variabile de mediu

| Variabilă          | Implicit                                                   | Rol                                   |
|--------------------|------------------------------------------------------------|---------------------------------------|
| `TEST_DB_DSN`      | `mysql:host=127.0.0.1;port=3306;dbname=botai_<app>_test`   | DSN-ul bazei de test a aplicației      |
| `TEST_DB_USER`     | `root`                                                     | utilizator MySQL                       |
| `TEST_DB_PASSWORD` | `mysql`                                                    | parolă (setează `TEST_DB_PASSWORD=` pentru parolă goală) |

Atenție: `TEST_DB_DSN` numește o singură bază, deci se setează doar când rulezi suita
unei singure aplicații (nu pentru hook-ul pre-commit, care rulează toate trei).

## Scrierea testelor

- Namespace `tests\unit`, un fișier `<Subiect>Test.php` per subiect, indentare cu tab,
  docblock pe clasă și pe fiecare metodă de test care explică *de ce* există testul.
- Teste pure (fără DB): extinde `PHPUnit\Framework\TestCase`. Aplicația console din
  bootstrap există deja, deci `Yii::t()`, `Yii::$app->cache` etc. funcționează.
- Teste cu DB: extinde `tests\DatabaseTestCase`. `insertRow($table, $attrs)` inserează
  un rând completând automat coloanele `NOT NULL` fără default (respectă lungimea
  `CHAR(2)`, enum-urile, tipurile de dată) și întoarce id-ul `AUTO_INCREMENT` ca `int`
  (sau valoarea cheii naturale furnizate). `fetchColumn($table, $id, $column)` citește
  o coloană după cheia primară. Cheile străine sunt dezactivate în tranzacție, deci
  fixture-ul conține doar rândurile relevante scenariului.
- Fluxuri web (`master` = backend, `workspace` = frontend cu modulul `embed`): extinde
  `tests\WebTestCase` și folosește `runControllerAction($route, $get, $post)`,
  `jsonPayload()`, `redirectUrl()`, `loginAs()` (+ `createUser()` în workspace).
  Setările se pun pe dublură: `Yii::$app->settings->set('cheie', 'valoare', 'categorie')`.
- Nu testa `createCronExpression()` din `ScheduledTask`: generează expresii cu 6 câmpuri
  pe care `dragonmantank/cron-expression` v3 le respinge, deci întoarce mereu `null`
  (bug de aplicație, în afara infrastructurii de test).

## CI (GitHub Actions)

`.github/workflows/tests.yml` rulează la fiecare push / pull request: serviciu
`mysql:8.0` (root / `mysql`, `sql_mode` fără `ONLY_FULL_GROUP_BY`, ca în
`docker-compose.yml`), PHP 8.3 (`shivammathur/setup-php`) cu același `error_reporting`,
`composer install --ignore-platform-reqs --no-security-blocking` per aplicație,
`tools/load-test-schema.sh` pentru cele trei baze, apoi PHAR-ul per aplicație cu
`TEST_DB_*`. La eșec afișează ultimele 200 de linii din log-urile Yii (`*/runtime/logs/app.log`).

## Hook pre-commit

`githooks/pre-commit` rulează cele trei suite înainte de fiecare commit și îl
blochează dacă una este roșie sau dacă o bază de test nu este accesibilă (mesaj clar
cu pașii de provisionare). Activare o singură dată per clonă:

```bash
git config core.hooksPath githooks
```

Ocolire explicită (commit WIP): `SKIP_TESTS=1 git commit ...`. Hook-ul folosește `php`
din PATH (sau `PHP=/cale/php`) cu același `error_reporting` ca scripturile.
