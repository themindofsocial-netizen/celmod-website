-- |CelMod| shared database schema (MySQL 5.7+ / MariaDB 10.3+)
--
-- The game server (addons/sourcemod/plugins/celmod-database.smx) and this
-- website both connect to this database. The game server is authoritative
-- for balances and purchases: the website only *reads* them and queues
-- shop orders in cm_web_orders, which the server delivers in-game.
--
-- The plugin also runs these CREATE TABLE IF NOT EXISTS statements on
-- connect, so importing this file by hand is optional.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS cm_players (
	steamid64        VARCHAR(20)  NOT NULL,
	name             VARCHAR(128) NOT NULL DEFAULT '',
	avatar_url       VARCHAR(255) NOT NULL DEFAULT '',
	balance          BIGINT       NOT NULL DEFAULT 0,
	playtime_seconds INT UNSIGNED NOT NULL DEFAULT 0,
	connections      INT UNSIGNED NOT NULL DEFAULT 0,
	props_spawned    INT UNSIGNED NOT NULL DEFAULT 0,
	cels_spawned     INT UNSIGNED NOT NULL DEFAULT 0,
	effects_spawned  INT UNSIGNED NOT NULL DEFAULT 0,
	entities_removed INT UNSIGNED NOT NULL DEFAULT 0,
	is_online        TINYINT(1)   NOT NULL DEFAULT 0,
	first_seen       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
	last_seen        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (steamid64),
	KEY idx_balance (balance),
	KEY idx_playtime (playtime_seconds),
	KEY idx_props (props_spawned)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Things a player owns. Mirrored from the server's purchases.txt files.
CREATE TABLE IF NOT EXISTS cm_purchases (
	steamid64    VARCHAR(20) NOT NULL,
	item         VARCHAR(64) NOT NULL,
	source       VARCHAR(8)  NOT NULL DEFAULT 'game',
	purchased_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (steamid64, item)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- The celmod-shop catalog (shop.txt + priced player models), synced by the game
-- server on connect, each map and about once a minute.
CREATE TABLE IF NOT EXISTS cm_shop_items (
	item         VARCHAR(64)  NOT NULL,              -- what players type: fire, prop_dog, model_gman
	display_name VARCHAR(64)  NOT NULL DEFAULT '',
	price        INT UNSIGNED NOT NULL DEFAULT 0,
	category     VARCHAR(32)  NOT NULL DEFAULT 'Commands',
	kind         VARCHAR(16)  NOT NULL DEFAULT 'command', -- command, prop, model, emblem, hat, weapon
	target       VARCHAR(64)  NOT NULL DEFAULT '',   -- the command / prop alias / model or item id
	description  VARCHAR(255) NOT NULL DEFAULT '',
	stock_left   INT          NULL,                  -- NULL = no limit
	repeatable   TINYINT(1)   NOT NULL DEFAULT 0,    -- can be bought again (emblems stack)
	sort_order   INT          NOT NULL DEFAULT 0,
	enabled      TINYINT(1)   NOT NULL DEFAULT 1,
	updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (item)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Orders placed on the website. status: pending -> processing -> completed | failed | cancelled
CREATE TABLE IF NOT EXISTS cm_web_orders (
	id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
	steamid64    VARCHAR(20)  NOT NULL,
	item         VARCHAR(64)  NOT NULL,
	price        INT UNSIGNED NOT NULL,
	status       VARCHAR(12)  NOT NULL DEFAULT 'pending',
	message      VARCHAR(255) NOT NULL DEFAULT '',
	server_id    INT          NULL,
	created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
	processed_at DATETIME     NULL,
	PRIMARY KEY (id),
	KEY idx_status_player (status, steamid64)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One row per game server, refreshed by the plugin's heartbeat.
CREATE TABLE IF NOT EXISTS cm_servers (
	server_id   INT          NOT NULL,
	hostname    VARCHAR(128) NOT NULL DEFAULT '',
	address     VARCHAR(64)  NOT NULL DEFAULT '',
	map         VARCHAR(64)  NOT NULL DEFAULT '',
	players     INT          NOT NULL DEFAULT 0,
	max_players INT          NOT NULL DEFAULT 0,
	total_props INT          NOT NULL DEFAULT 0,
	total_cels  INT          NOT NULL DEFAULT 0,
	version     VARCHAR(16)  NOT NULL DEFAULT '',
	map_started DATETIME     NULL,
	updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (server_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Who is on each server right now.
CREATE TABLE IF NOT EXISTS cm_online_players (
	server_id INT          NOT NULL,
	steamid64 VARCHAR(20)  NOT NULL,
	name      VARCHAR(128) NOT NULL DEFAULT '',
	props     INT          NOT NULL DEFAULT 0,
	cels      INT          NOT NULL DEFAULT 0,
	balance   BIGINT       NOT NULL DEFAULT 0,
	joined_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (server_id, steamid64)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cm_sessions (
	id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
	steamid64  VARCHAR(20)  NOT NULL,
	server_id  INT          NOT NULL,
	map        VARCHAR(64)  NOT NULL DEFAULT '',
	started_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
	ended_at   DATETIME     NULL,
	PRIMARY KEY (id),
	KEY idx_player (steamid64),
	KEY idx_started (started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activity feed: connect, disconnect, purchase, web_purchase, map_change
CREATE TABLE IF NOT EXISTS cm_events (
	id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
	server_id  INT          NOT NULL,
	steamid64  VARCHAR(20)  NULL,
	name       VARCHAR(128) NOT NULL DEFAULT '',
	type       VARCHAR(16)  NOT NULL,
	detail     VARCHAR(255) NOT NULL DEFAULT '',
	created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cm_stats_daily (
	day             DATE         NOT NULL,
	server_id       INT          NOT NULL,
	connections     INT UNSIGNED NOT NULL DEFAULT 0,
	peak_players    INT UNSIGNED NOT NULL DEFAULT 0,
	props_spawned   INT UNSIGNED NOT NULL DEFAULT 0,
	cels_spawned    INT UNSIGNED NOT NULL DEFAULT 0,
	effects_spawned INT UNSIGNED NOT NULL DEFAULT 0,
	PRIMARY KEY (day, server_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Player count sampled every few minutes, for the activity chart.
CREATE TABLE IF NOT EXISTS cm_player_samples (
	server_id   INT      NOT NULL,
	recorded_at DATETIME NOT NULL,
	players     INT      NOT NULL,
	PRIMARY KEY (server_id, recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Emblems players designed in the Emblem Editor and suggested. Written by the
-- website only; nothing here is ever copied to the server automatically.
CREATE TABLE IF NOT EXISTS cm_emblem_suggestions (
	id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
	steamid64   VARCHAR(20)  NOT NULL,
	player_name VARCHAR(128) NOT NULL DEFAULT '',
	emblem_id   VARCHAR(64)  NOT NULL,
	title       VARCHAR(64)  NOT NULL DEFAULT '',
	kv          TEXT         NOT NULL,              -- the cleaned emblem block
	note        VARCHAR(500) NOT NULL DEFAULT '',
	status      VARCHAR(12)  NOT NULL DEFAULT 'pending', -- pending, accepted, rejected, withdrawn
	admin_note  VARCHAR(255) NOT NULL DEFAULT '',
	reviewed_by VARCHAR(20)  NULL,
	created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
	reviewed_at DATETIME     NULL,
	PRIMARY KEY (id),
	KEY idx_status (status),
	KEY idx_player (steamid64)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cm_maps (
	map           VARCHAR(64)  NOT NULL,
	times_played  INT UNSIGNED NOT NULL DEFAULT 0,
	last_played   DATETIME     NULL,
	PRIMARY KEY (map)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
