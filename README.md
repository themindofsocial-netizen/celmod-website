# |CelMod| Website

The |CelMod| website, now in PHP with a database shared with the game server.
It keeps the classic look and adds live data from the server:

| Page | What it shows |
| --- | --- |
| `live.php` | Who is online right now, map, props/cels out, recent activity (refreshes every 5s) |
| `shop.php` | The shop, live from `shop.txt`. Signed-in players can buy straight from the site |
| `statistics.php` | Totals, players-online chart (24h), joins and props per day, top maps, popular items |
| `leaderboard.php` | Richest players, most time played, most props / cels spawned |
| `player.php?id=<steamid64>` | Player profile: balance, playtime, unlocks, recent sessions |
| `emblem-editor/` | Design emblems in 3D and export them for `collectibles.txt`. Opens with the server's current emblems |
| `admin.php` | Web order log (SteamIDs listed in `admins` in `config.php`) |
| `api/status.php` | Live server snapshot as JSON (for Discord bots, widgets...) |

Every other page (commands, props, colors, updates...) is the original content,
just served through PHP. Old `.html` links (including the in-game MOTD URLs)
redirect to the `.php` pages.

## How the shared database works

```
 HL2DM server                          MySQL / MariaDB                     Website (PHP)
 celmod-database.smx  ── writes ──▶  players, sessions, stats,  ◀── reads ──  live / stats / profiles
                                     online players, shop items
                      ◀── reads ───  cm_web_orders (pending)    ◀── writes ── shop.php "Buy"
```

The game server stays in charge of money. When someone buys on the website, an
order is queued; the server picks it up within a few seconds (or the next time
the player joins), re-checks the price, their balance and that they don't
already own it, then charges them in-game and marks the order completed. The
website never edits balances itself, so the two can't fight over them.

## Setup

**Hosting:** this needs a PHP host now (PHP 8.0+ with `pdo_mysql` and `curl`).
GitHub Pages only serves static files, so point the `delaware.rockzehh.net`
DNS at the new host when you switch over.

1. **Database.** Create a MySQL/MariaDB database (the tables are created by
   the game server plugin automatically, or import `sql/schema.sql` yourself):

   ```sql
   CREATE DATABASE celmod CHARACTER SET utf8mb4;
   -- game server: full access to its tables
   CREATE USER 'celmod_server'@'%' IDENTIFIED BY '...';
   GRANT SELECT, INSERT, UPDATE, DELETE, CREATE ON celmod.* TO 'celmod_server'@'%';
   -- website: read everything, only write orders + avatars.
   -- WEBHOST is where the website connects FROM, as MySQL sees it:
   --   same machine as MySQL, config host '127.0.0.1' -> '127.0.0.1'
   --   same machine as MySQL, config host 'localhost' -> 'localhost'
   --   different machine                               -> that machine's IP
   CREATE USER 'celmod_web'@'WEBHOST' IDENTIFIED BY '...';
   GRANT SELECT ON celmod.* TO 'celmod_web'@'WEBHOST';
   GRANT INSERT, UPDATE ON celmod.cm_web_orders TO 'celmod_web'@'WEBHOST';
   GRANT UPDATE (avatar_url) ON celmod.cm_players TO 'celmod_web'@'WEBHOST';
   ```

   MySQL treats `'user'@'localhost'` and `'user'@'127.0.0.1'` as two different
   accounts, so the host must match how the website connects.

2. **Website.** Copy `config.sample.php` to `config.php` and fill in the
   database login, `site_url` (used for Steam sign in) and the server address.
   `config.php` is git-ignored.

3. **Game server.** Install `celmod-database.smx` from the celmod repo and add
   this to `addons/sourcemod/configs/databases.cfg`:

   ```
   "celmod"
   {
       "driver"    "mysql"
       "host"      "your-db-host"
       "database"  "celmod"
       "user"      "celmod_server"
       "pass"      "..."
   }
   ```

   Settings land in `cfg/sourcemod/celmod.database.cfg` (`cm_db_server_id`
   must match `server.id` in `config.php`). `cm_db_status` checks the connection.

### Apache
`.htaccess` is included (needs `mod_rewrite`). It also blocks `includes/`,
`sql/` and the config files.

### nginx
```nginx
location ~ ^/(includes|sql)/ { deny all; }
location ~ ^/config(\.sample)?\.php$ { deny all; }
location ~ \.html$ { try_files $uri @html2php; }
location @html2php { rewrite ^(.+)\.html$ $1.php permanent; }
location / { index index.php; try_files $uri $uri/ =404; }
location ~ \.php$ { include fastcgi_params; fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; fastcgi_pass unix:/run/php/php-fpm.sock; }
```

## Troubleshooting: "The |CelMod| database is offline"

Run the setup check. It names the exact problem and how to fix it:

```
php check.php                      # on the web server
https://your-site/check.php        # in a browser: signed-in admins from config.php only
```

It checks PHP's MySQL extension, that `config.php` exists (without it the
site falls back to the sample's placeholder password), the login, the
tables, and that `server.id` matches the game server's `cm_db_server_id`.
Signed-in admins also see the raw error on any page that says the database
is offline.

Common causes when the game server connects but the website doesn't:

- **The website is on a different machine.** MySQL usually only listens on
  127.0.0.1. Set `bind-address = 0.0.0.0` in `my.cnf`, restart MySQL, allow
  port 3306 from the web server's IP only in the firewall, create the
  `celmod_web` user for the web server's IP, and put the database server's IP
  in `config.php`.
- **User/host mismatch** (error 1045 or 1130), see the note under Setup.
- **`config.php` missing or in the wrong folder.** It must sit next to `index.php`.
- **No `pdo_mysql`.** Install `php-mysql` and restart PHP-FPM/Apache.
- **Different database name** than the game server's `databases.cfg`.

## Signing in
"Sign in through Steam" uses Steam's OpenID, so no API key is needed. Add a
`steam_api_key` to `config.php` if you also want Steam avatars on profiles.
Players need to have joined the server once before the web shop unlocks for them.

## Page layout
Every page is built from `includes/layout.php`: the logo, the navigation bar
(edit `cm_nav_items()` to change the links), the framed page and the footer.

```php
cm_page_start('|CelMod| - My Page');                        // classic 677px frame
cm_page_start('|CelMod| - My Page', '', ['wide' => true]);  // 940px frame for tables and tools
// ... page content ...
cm_page_end();
```

The frame is the original `cmtextbg` artwork sliced into `images/frame/`
(side bars, a seamless centre tile and the bottom cap), so it stretches to the
wide layout and shrinks to fit phones. Useful classes: `tiles`/`tile` (home
page buttons), `btngrid` (rows of small buttons), `cmtable` (striped tables),
`stat` (number tiles), `panel` (dark inset box) and `cmbutton`.
