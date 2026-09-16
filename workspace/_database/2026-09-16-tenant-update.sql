-- BotAI — aducerea bazelor de TENANT la schema pe care o aşteaptă codul
-- ============================================================================
--
-- Se rulează pe FIECARE bază de workspace. Nu pe master.
--
-- Producţia a rămas pe schema dinainte de portarea din masteranunturi: `thread`
-- se numea aşa, iar knowledge base-ul nu exista. Codul actual caută
-- `conversation`, `knowledge_base` şi patru coloane în plus pe `assistant` —
-- fără ele, `validate()` pe un asistent aruncă UnknownPropertyException, deci
-- ecranele de asistenţi sunt rupte.
--
-- ISTORICUL DE CHAT SE PĂSTREAZĂ. Redenumire, nu recreare: conversaţiile şi
-- mesajele existente rămân legate între ele.
--
-- FĂ BACKUP ÎNAINTE. E singurul script din set care schimbă structura.
--
-- Rulează prin: Master > Abonaţi > Workspace > Actualizare bază de date.
-- Idempotent: fiecare pas verifică întâi dacă mai are ce face.

-- ── 1. thread -> conversation, cu istoricul intact ─────────────────────────

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = DATABASE() AND table_name = 'thread') > 0
    AND (SELECT COUNT(*) FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = 'conversation') = 0,
    'RENAME TABLE `thread` TO `conversation`',
    'DO 0');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'conversation' AND column_name = 'openai_id') > 0,
    'ALTER TABLE `conversation` CHANGE `openai_id` `openai_conversation_id` VARCHAR(255) NULL',
    'DO 0');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'conversation' AND column_name = 'summary') = 0,
    'ALTER TABLE `conversation` ADD `summary` VARCHAR(255) NULL AFTER `id`',
    'DO 0');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ── 2. message.thread_id -> message.conversation_id ────────────────────────
--     CHANGE păstrează valorile, deci mesajele rămân legate de conversaţii.

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'message' AND column_name = 'thread_id') > 0,
    'ALTER TABLE `message` CHANGE `thread_id` `conversation_id` INT NOT NULL',
    'DO 0');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ── 3. coloanele noi de pe assistant ───────────────────────────────────────

SET @sql := IF((SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'assistant' AND column_name = 'model') = 0,
    'ALTER TABLE `assistant` ADD `model` VARCHAR(255) NULL AFTER `name`', 'DO 0');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'assistant' AND column_name = 'provider') = 0,
    'ALTER TABLE `assistant` ADD `provider` TINYINT NULL AFTER `model`', 'DO 0');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'assistant' AND column_name = 'max_tokens') = 0,
    'ALTER TABLE `assistant` ADD `max_tokens` INT NULL', 'DO 0');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'assistant' AND column_name = 'type') = 0,
    'ALTER TABLE `assistant` ADD `type` TINYINT NULL', 'DO 0');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Asistenţii existenţi sunt OpenAI de tip Chat — codul citeşte aceste valori,
-- iar NULL le-ar face să pice din filtrele de provider.
UPDATE `assistant` SET `provider` = 1 WHERE `provider` IS NULL;   -- PROVIDER_OPENAI
UPDATE `assistant` SET `type` = 1 WHERE `type` IS NULL;           -- TYPE_CHAT
UPDATE `assistant` SET `model` = `gpt_model` WHERE `model` IS NULL AND `gpt_model` IS NOT NULL;

-- ── 4. tabelele noi de knowledge base / vector store ───────────────────────

CREATE TABLE IF NOT EXISTS `knowledge_base` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `provider` tinyint DEFAULT NULL,
  `embedding_model` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vector_store_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chunk_size` int DEFAULT '1000',
  `chunk_overlap` int DEFAULT '200',
  `tokens_per_file` int DEFAULT '0',
  `expire_at` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `status` tinyint NOT NULL,
  `deleted` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `deleted` (`deleted`),
  KEY `status` (`status`),
  KEY `provider` (`provider`)
) ENGINE=InnoDB AUTO_INCREMENT=183 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE IF NOT EXISTS `knowledge_base_document` (
  `id` int NOT NULL AUTO_INCREMENT,
  `knowledge_base_id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `extension` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` int DEFAULT '0',
  `openai_file_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vector_store_file_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vector_store_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `index_status` tinyint NOT NULL DEFAULT '0' COMMENT '0=not indexed,1=indexed,2=error',
  `indexed_at` datetime DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `status` tinyint NOT NULL,
  `deleted` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `deleted` (`deleted`),
  KEY `index_status` (`index_status`),
  KEY `fk_knowledge_base_document_knowledge_base1_idx` (`knowledge_base_id`),
  CONSTRAINT `fk_knowledge_base_document_knowledge_base1` FOREIGN KEY (`knowledge_base_id`) REFERENCES `knowledge_base` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE IF NOT EXISTS `assistant_knowledge_base` (
  `assistant_id` int NOT NULL,
  `knowledge_base_id` int NOT NULL,
  `sort_order` int DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`assistant_id`,`knowledge_base_id`),
  KEY `sort_order` (`sort_order`),
  KEY `fk_assistant_knowledge_base_knowledge_base1_idx` (`knowledge_base_id`),
  CONSTRAINT `fk_assistant_knowledge_base_assistant1` FOREIGN KEY (`assistant_id`) REFERENCES `assistant` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_assistant_knowledge_base_knowledge_base1` FOREIGN KEY (`knowledge_base_id`) REFERENCES `knowledge_base` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE IF NOT EXISTS `record_vector_index` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_id` int NOT NULL COMMENT 'page.id',
  `openai_file_id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vector_store_file_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vector_store_id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '0=inactive,1=active,2=error',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `indexed_at` datetime DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `record_id_UNIQUE` (`record_id`),
  KEY `openai_file_id` (`openai_file_id`),
  KEY `status` (`status`),
  KEY `deleted` (`deleted`),
  CONSTRAINT `fk_record_vector_index_page1` FOREIGN KEY (`record_id`) REFERENCES `page` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;


-- ── 4b. knowledge base-ul implicit ─────────────────────────────────────────
--
-- Indexarea paginilor scrape-uite merge în knowledge base-ul marcat implicit, la
-- fel cum cheia OpenAI vine din integrarea marcată implicită. Fără coloană,
-- alegerea cădea pe „prima activă după id", adică pe ordinea de creare.

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'knowledge_base' AND column_name = 'default') = 0,
    'ALTER TABLE `knowledge_base` ADD `default` TINYINT(1) NOT NULL DEFAULT 0 AFTER `tokens_per_file`, ADD INDEX `default` (`default`)',
    'DO 0');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Dacă există deja exact un knowledge base activ şi niciunul nu e marcat, el este
-- cel implicit: e ceea ce codul alegea oricum înainte de coloană.
UPDATE `knowledge_base` SET `default` = 1
WHERE `deleted` = 0 AND `status` = 1
  AND (SELECT * FROM (SELECT COUNT(*) FROM `knowledge_base` WHERE `deleted` = 0 AND `status` = 1) AS c) = 1
  AND (SELECT * FROM (SELECT COUNT(*) FROM `knowledge_base` WHERE `deleted` = 0 AND `default` = 1) AS d) = 0;

-- ── 4c. textul curat al paginii ────────────────────────────────────────────
--
-- `page`.`content` e răspunsul HTTP brut — HTML cu tot cu script şi style. În
-- vector store urcă doar textul vizibil, extras la fiecare indexare şi nevăzut
-- de nimeni între timp. Stocat, se vede în interfaţă ce anume va fi indexat şi
-- nu se mai recalculează la fiecare sincronizare.
--
-- Rândurile existente rămân NULL: serviciul extrage din HTML ca până acum, iar
-- coloana se completează la următoarea trecere a scraper-ului peste pagină.

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'page' AND column_name = 'text') = 0,
    'ALTER TABLE `page` ADD `text` MEDIUMTEXT NULL DEFAULT NULL AFTER `content`',
    'DO 0');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ── 5. integrările locale, şterse definitiv ────────────────────────────────
--
--     Rulează DOAR după ce cheia e pe hub şi ai confirmat că un tenant o vede
--     (pasul 1 din master/_database/2026-09-16-master-update.sql).
--
--     `type IN (1, 2)` acoperă ambele numerotări: în producţie OpenAI este încă
--     1, iar codul nou îl scrie ca 2. Aşa scriptul e corect indiferent de
--     ordinea în care ajung codul şi baza.
--
--     Ştergere definitivă, cerută explicit. Dacă preferi ceva reversibil,
--     înlocuieşte linia cu:
--         UPDATE `integration` SET `deleted` = 1 WHERE `type` IN (1, 2);
--     Modelul filtrează deja pe `deleted = 0`, deci efectul e acelaşi, dar
--     rândul şi cheia rămân recuperabile.

DELETE FROM `integration` WHERE `type` IN (1, 2);

-- ── Verificare ─────────────────────────────────────────────────────────────
--   SELECT COUNT(*) FROM `conversation`;          -- istoricul, neatins
--   SELECT COUNT(*) FROM `message`;               -- idem
--   SELECT COUNT(*) FROM `integration`;           -- aşteptat: 0
--   SELECT provider, type, model FROM `assistant`;
--
-- `thread`, `vector_store` şi `vector_store_file` nu sunt atinse: primul a fost
-- redenumit, celelalte două rămân pe loc ca plasă de siguranţă. Le poţi şterge
-- manual după ce eşti sigur.
