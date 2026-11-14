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
| `emblem-editor/` | Public: design emblems in 3D and suggest them to the admins. Edits stay in the player's browser; the server's emblems can't be changed from it |
| `admin.php` | Web order log, emblem suggestion review and in-game announcements (SteamIDs listed in `admins` in `config.php`) |
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
   GRANT INSERT, UPDATE ON celmod.cm_emblem_suggestions TO 'celmod_web'@'WEBHOST';
   GRANT INSERT, UPDATE, DELETE ON celmod.cm_announcements TO 'celmod_web'@'WEBHOST';
   -- admin panel: posting updates and choosing the seasonal theme
   -- (these two tables are created by the game server's celmod-database the first time it connects)
   GRANT INSERT, UPDATE, DELETE ON celmod.cm_updates TO 'celmod_web'@'WEBHOST';
   GRANT INSERT, UPDATE ON celmod.cm_site_settings TO 'celmod_web'@'WEBHOST';
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
- **Unraid: the website container is on `br0` and MariaDB runs on the same
  Unraid server.** Containers on `br0` (macvlan/ipvlan, with their own LAN
  IP) cannot reach their own host's IP, so the site gets "Host is unreachable"
  for the server's address while other machines (like the game server)
  connect fine. Either:
  - **Allow it:** Settings > Docker > stop Docker > Advanced View >
    *Host access to custom networks* = **Enabled** > Apply, start Docker.
    Keep `host` = the Unraid server's IP in `config.php`.
  - **Or connect container to container**, skipping the LAN: create a shared
    network (`docker network create celmod`), set MariaDB's *Network Type* to
    `celmod` (its published port keeps working for the game server), add
    `&& docker network connect celmod <web container name>` to the web
    container's *Post Arguments*, then use the MariaDB container's name as
    `host` in `config.php`. MySQL then sees the site's address on the
    `celmod` network, so create `celmod_web` for that range, e.g.
    `'celmod_web'@'172.%'`.
- **"Host is unreachable" from a website running in Docker.** A Docker
  network on the web server is using an address range that covers your LAN
  (Docker takes 192.168.x.0/20 ranges once its 172.x ones run out), so the
  container looks for the database inside Docker. `check.php` shows the route
  it takes. On the web server host, list the networks' ranges:

  ```
  docker network inspect -f '{{.Name}} {{range .IPAM.Config}}{{.Subnet}}{{end}}' $(docker network ls -q)
  ```

  Any subnet that contains your LAN (e.g. `192.168.0.0/20` contains
  192.168.2.x) is the culprit. Move it: set an explicit subnet in that
  stack's compose file and recreate it (`docker compose down && docker compose up -d`)

  ```yaml
  networks:
    default:
      ipam:
        config:
          - subnet: 172.30.0.0/24
  ```

  and stop Docker choosing LAN ranges again with `/etc/docker/daemon.json`
  (then `systemctl restart docker`):

  ```json
  { "default-address-pools": [ { "base": "172.17.0.0/12", "size": 24 } ] }
  ```
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
