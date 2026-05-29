# -----------------------------------------------------------------------------
# Convenience shortcuts for the Docker stack. Works on macOS, Linux and
# Git Bash on Windows. Run from inside this `docker/` directory:
#
#   cd docker && make <target>
#
# Everything is driven by the .env file that lives next to this Makefile
# (copy .env.example to .env and tweak).
#
# Quick reference:
#   make init    - first-time setup (SSL cert + builds images + starts stack)
#   make up      - start all containers in the background
#   make down    - stop all containers (keeps the DB volume)
#   make reset   - stop and WIPE the DB volume (destructive)
#   make logs    - tail logs from all containers
#   make shell   - open a bash shell inside the web container
#   make db      - open a mysql client on the db container
#   make import  - import ../_database/import.sql (or structure.sql) into the DB
#   make fresh   - wipe + re-import in one shot
#   make wipe    - drop every table in the DB (keeps the DB + user)
#   make hosts   - print the line to add to /etc/hosts
# -----------------------------------------------------------------------------

# Project root (one level up from this file).
PROJECT_ROOT := $(abspath $(dir $(lastword $(MAKEFILE_LIST)))..)

# Load .env from this dir if present, fall back to .env.example.
ifneq (,$(wildcard .env))
include .env
export
else ifneq (,$(wildcard .env.example))
include .env.example
export
endif

PROJECT_NAME ?= botai
DB_NAME      ?= $(PROJECT_NAME)
DB_USER      ?= root

COMPOSE      = docker compose

.DEFAULT_GOAL := help

.PHONY: help init ssl up build down stop restart ps logs shell db import fresh wipe hosts reset

# SQL file used by `make import` / `make fresh`. Prefers import.sql
# (developer-managed full dump) and falls back to structure.sql. Both live
# in <project>/_database/, which is also mounted into phpMyAdmin.
SQL_FILE := $(firstword $(wildcard $(PROJECT_ROOT)/_database/import.sql $(PROJECT_ROOT)/_database/structure.sql))

help:
	@echo "Usage: make <target>"
	@echo ""
	@echo "  init     First-time setup: SSL cert + build images + start stack"
	@echo "  ssl      Generate a self-signed SSL cert for https://\$(PROJECT_NAME)/"
	@echo "  up       Start all containers in the background"
	@echo "  build    Rebuild the web image (after Dockerfile changes)"
	@echo "  down     Stop all containers (keeps DB volume)"
	@echo "  stop     Alias for down"
	@echo "  restart  Restart all containers"
	@echo "  ps       List running containers for this project"
	@echo "  logs     Tail logs from all containers"
	@echo "  shell    Open a bash shell inside the web container"
	@echo "  db       Open the mysql CLI inside the db container"
	@echo "  import   Import ../_database/import.sql (or structure.sql) into the DB"
	@echo "  fresh    Wipe all tables, then run import (non-interactive)"
	@echo "  wipe     Drop ALL tables in the DB (keeps the database + user)"
	@echo "  hosts    Print the line to add to your hosts file"
	@echo "  reset    Destroy everything including the DB volume"

ssl:
	bash setup.sh

init: ssl build up
	@echo ""
	@$(MAKE) hosts

up:
	$(COMPOSE) up -d

build:
	$(COMPOSE) build

down:
	$(COMPOSE) down

stop: down

restart:
	$(COMPOSE) restart

ps:
	$(COMPOSE) ps

logs:
	$(COMPOSE) logs -f

shell:
	$(COMPOSE) exec web bash

db:
	$(COMPOSE) exec db mysql -u$(DB_USER) $(DB_NAME)

import:
	@if [ -z "$(SQL_FILE)" ]; then \
		echo "[error] Neither ../_database/import.sql nor ../_database/structure.sql found"; exit 1; \
	fi
	@echo "[info] Importing $(SQL_FILE) into $(DB_NAME) as $(DB_USER)..."
	$(COMPOSE) exec -T db mysql -u$(DB_USER) $(DB_NAME) < $(SQL_FILE)
	@echo "[ok] Import complete."

fresh:
	@if [ -z "$(SQL_FILE)" ]; then \
		echo "[error] Neither ../_database/import.sql nor ../_database/structure.sql found"; exit 1; \
	fi
	@echo "[info] Wiping $(DB_NAME) and re-importing from $(SQL_FILE)..."
	@printf '%s\n' \
	  'SET FOREIGN_KEY_CHECKS = 0;' \
	  'SET GROUP_CONCAT_MAX_LEN = 1048576;' \
	  'SET @tbls = (SELECT GROUP_CONCAT("`", table_name, "`") FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = "BASE TABLE");' \
	  'SET @s = IF(@tbls IS NULL, "SELECT 1", CONCAT("DROP TABLE ", @tbls));' \
	  'PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;' \
	  'SET FOREIGN_KEY_CHECKS = 1;' \
	  | $(COMPOSE) exec -T db mysql -u$(DB_USER) $(DB_NAME)
	@$(COMPOSE) exec -T db mysql -u$(DB_USER) $(DB_NAME) < $(SQL_FILE)
	@echo "[ok] Fresh import complete."

wipe:
	@echo "[warn] This will DROP every table in database '$(DB_NAME)'."
	@read -p "Type 'yes' to continue: " CONFIRM; \
	if [ "$$CONFIRM" != "yes" ]; then echo "[aborted]"; exit 1; fi; \
	echo "[info] Dropping all tables in $(DB_NAME)..."; \
	printf '%s\n' \
	  'SET FOREIGN_KEY_CHECKS = 0;' \
	  'SET GROUP_CONCAT_MAX_LEN = 1048576;' \
	  'SET @tbls = (SELECT GROUP_CONCAT("`", table_name, "`") FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = "BASE TABLE");' \
	  'SET @s = IF(@tbls IS NULL, "SELECT 1", CONCAT("DROP TABLE ", @tbls));' \
	  'PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;' \
	  'SET FOREIGN_KEY_CHECKS = 1;' \
	  | $(COMPOSE) exec -T db mysql -u$(DB_USER) $(DB_NAME); \
	echo "[ok] All tables dropped."

hosts:
	@echo "---"
	@echo "Add this line to your hosts file (requires admin/sudo):"
	@echo "  127.0.0.1  $(PROJECT_NAME)"
	@echo ""
	@echo "  macOS / Linux:  sudo nano /etc/hosts"
	@echo "  Windows:        Notepad as Admin -> C:\\Windows\\System32\\drivers\\etc\\hosts"
	@echo "---"

reset:
	@echo "[warn] This will destroy the DB volume and all its data."
	@read -p "Are you sure? Type 'yes' to continue: " CONFIRM; \
	if [ "$$CONFIRM" = "yes" ]; then \
		$(COMPOSE) down -v; \
		echo "[ok] Reset complete."; \
	else \
		echo "[aborted]"; \
	fi
