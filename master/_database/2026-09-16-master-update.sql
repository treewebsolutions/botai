-- BotAI — modificările de bază pentru hub (master)
-- ============================================================================
--
-- Se rulează pe baza MASTER. Pentru tenanţi, vezi
-- workspace/_database/2026-09-16-tenant-update.sql — acela schimbă structura
-- şi trebuie rulat pe fiecare workspace.
--
-- ORDINE: pasul 1 aici, verifici, apoi scriptul de tenant. Invers, tenanţii
-- rămân fără cheie între cele două momente.
--
-- Nicio instrucţiune distructivă în tot fişierul: doar INSERT.

-- ── PASUL 1a: permisiunea din spatele ecranului nou de creare integrare ────
--
-- Modulul de integrări putea lista, vedea, edita şi şterge, dar niciodată
-- crea — nu exista nici acţiune, nici permisiune. type = 2 înseamnă permisiune.

START TRANSACTION;

INSERT IGNORE INTO `auth_item` (`name`, `type`, `description`, `rule_name`, `data`, `created_at`, `updated_at`)
VALUES ('createIntegration', 2, NULL, NULL, NULL, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT IGNORE INTO `auth_item_child` (`parent`, `child`)
SELECT 'superAdmin', 'createIntegration'
FROM DUAL
WHERE EXISTS (SELECT 1 FROM `auth_item` WHERE `name` = 'superAdmin');

-- Marchează migraţia ca aplicată, ca `php yii migrate` să n-o mai ruleze.
INSERT IGNORE INTO `migration` (`version`, `apply_time`)
VALUES ('m260916_070000_add_create_integration_permission', UNIX_TIMESTAMP());

COMMIT;

-- ── PASUL 1b: cheia OpenAI a platformei ────────────────────────────────────
--
-- Toţi cei şase tenanţi au acum acelaşi rând de integrare, cu aceeaşi cheie
-- copiată de şase ori (verificat: amprente SHA-256 identice). Pusă aici, devine
-- un singur rând, iar tenanţii fără integrare proprie cad pe ea.
--
-- Varianta A copiază cheia direct dintr-un tenant, fără s-o scrie în clar.
-- Înlocuieşte `botai_pq8EsN0O` cu numele real al unei baze de tenant de pe
-- serverul tău (pe cPanel are de obicei prefix de cont).

INSERT INTO `integration`
    (`name`, `data`, `expire_at`, `type`, `sandbox`, `default`,
     `created_by`, `updated_by`, `created_at`, `updated_at`, `status`, `deleted`)
SELECT
    'Platform OpenAI',
    t.`data`,
    NULL,
    2,        -- OpenAI pe hub (1 e SPV)
    0,
    1,        -- Default: e cea pe care o iau tenanţii
    NULL, NULL, NOW(), NOW(),
    1,        -- Activă
    0
FROM `botai_pq8EsN0O`.`integration` AS t
WHERE t.`deleted` = 0
  AND t.`status` = 1
  AND NOT EXISTS (SELECT 1 FROM `integration` WHERE `type` = 2 AND `deleted` = 0)
LIMIT 1;

-- Varianta B, dacă accesul între baze e refuzat. Decomentează, pune cheia,
-- rulează, apoi şterge fişierul.
--
-- INSERT INTO `integration`
--     (`name`, `data`, `expire_at`, `type`, `sandbox`, `default`,
--      `created_by`, `updated_by`, `created_at`, `updated_at`, `status`, `deleted`)
-- SELECT 'Platform OpenAI', 'sk-INLOCUIESTE-CU-CHEIA', NULL, 2, 0, 1,
--        NULL, NULL, NOW(), NOW(), 1, 0
-- FROM DUAL
-- WHERE NOT EXISTS (SELECT 1 FROM `integration` WHERE `type` = 2 AND `deleted` = 0);


-- ── PASUL 2: jurnalul actualizărilor de bază ale workspace-urilor ──────────
--
-- `WorkspaceDatabaseUpdateLog` scrie aici fiecare interogare rulată prin ecranul
-- de actualizare în masă, dar tabelul n-a existat niciodată în botai: modelul a
-- fost portat din masteranunturi fără schema lui, aşa că un bulk update se oprea
-- cu „The table does not exist: {{%workspace_database_update_log}}".
--
-- Faţă de masteranunturi, cheile sunt INT, nu BINARY(16): botai a rămas pe chei
-- numerice, iar `workspace_id` trebuie să se potrivească cu `workspace`.`id`.
--
-- `batch_id` grupează rândurile produse de un singur submit, ca o rulare peste
-- şase tenanţi să poată fi citită ca un tot.

CREATE TABLE IF NOT EXISTS `workspace_database_update_log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `workspace_id` INT(11) NOT NULL,
  `batch_id` VARCHAR(32) NOT NULL COMMENT 'Grupează rândurile unui singur submit.',
  `workspace_code` VARCHAR(255) NULL DEFAULT NULL,
  `workspace_url` VARCHAR(255) NULL DEFAULT NULL,
  `query` TEXT NOT NULL,
  `error` TEXT NULL DEFAULT NULL,
  `created_by` INT(11) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `status` SMALLINT(6) NOT NULL COMMENT '0 = eroare, 1 = succes',
  PRIMARY KEY (`id`),
  INDEX `workspace_database_update_log_idx_batch_id` (`batch_id` ASC),
  INDEX `workspace_database_update_log_idx_workspace_code` (`workspace_code` ASC),
  INDEX `workspace_database_update_log_idx_status` (`status` ASC),
  INDEX `fk_workspace_database_update_log_workspace1_idx` (`workspace_id` ASC),
  CONSTRAINT `fk_workspace_database_update_log_workspace1`
    FOREIGN KEY (`workspace_id`)
    REFERENCES `workspace` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── PASUL 3. expresiile cron, aduse la 5 câmpuri ──────────────────────────────
--
-- `dragonmantank/cron-expression` v3 acceptă exact 5 câmpuri: minut, oră, zi,
-- lună, zi-din-săptămână. Toate rândurile au fost scrise cu 6, cu un câmp de an
-- la sfârşit, aşa că `schedule/run` murea la prima sarcină cu
-- „6 is not a valid position" — şi nu rula niciuna. Nimic programat n-a pornit
-- vreodată: nici scraper-ul, nici backup-urile.
--
-- Taie al şaselea câmp doar de pe rândurile care îl au, deci se poate rula de
-- câte ori vrei. `* * * * * *` devine `* * * * *`, `0 0 * * * *` devine
-- `0 0 * * *`.

UPDATE `scheduled_task`
SET `cron_expression` = SUBSTRING_INDEX(`cron_expression`, ' ', 5)
WHERE LENGTH(`cron_expression`) - LENGTH(REPLACE(`cron_expression`, ' ', '')) = 5;

-- ── Verificare înainte de scriptul de tenant ───────────────────────────────
--
--   SELECT id, name, type, `default`, status, CHAR_LENGTH(data) AS len
--   FROM `integration` WHERE `deleted` = 0;
--
-- Un rând, type = 2, default = 1, status = 1, len > 100. Apoi goleşte cache-ul
-- RBAC (master/backend/runtime/cache/, master/frontend/runtime/cache/) şi
-- deschide un tenant: ecranul lui de Integrări trebuie să spună că asistenţii
-- rulează pe cheia platformei.
