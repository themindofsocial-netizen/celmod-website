<?php
/*
 * |CelMod| website configuration.
 *
 * Copy this file to config.php and fill in your details.
 * config.php is git-ignored so your passwords never get committed.
 *
 * The database must be the same one the game server's celmod-database
 * plugin connects to (the "celmod" entry in addons/sourcemod/configs/databases.cfg).
 */
return [
	'db' => [
		'host'    => '127.0.0.1',       // the database server's IP if MySQL is on another machine
		'port'    => 3306,
		'socket'  => '',                // optional: MySQL socket path, used instead of host/port
		'name'    => 'celmod',
		'user'    => 'celmod_web',
		'pass'    => 'change-me',
	],

	// Full public URL of the site, no trailing slash. Used for Steam login.
	'site_url' => 'https://celmod.net',

	// Optional: a Steam Web API key (https://steamcommunity.com/dev/apikey).
	// Only used to fetch Steam profile pictures, 100 at a time. Without it they're still fetched, from the
	// players' public Steam profiles, 10 at a time.
	'steam_api_key' => '',

	// Keep every player's picture up to date with their Steam avatar (see README, "Steam profile pictures").
	'steam_avatars' => true,

	// The server the "Connect" links and live page show by default.
	'server' => [
		'id'      => 1,              // must match cm_db_server_id on the game server
		'address' => '158.69.118.241:27015', // the port is used; the IP is replaced by this network's public IP
		'public_ip' => true,          // false = always show the address above as it is
		'name'    => '|CelMod| Beta Testing Server',
	],

	// Seconds after the last heartbeat before the server is shown as offline.
	'offline_after' => 90,

	// The emblem list (admin panel, Emblem Editor, suggestions) comes from the game server:
	// celmod-database sends its collectibles.txt to the database whenever it changes.
	// Only set this to read the server's file directly (when the site runs on the same machine).
	// Until the server has sent it, the copy in emblem-editor/collectibles.txt is used.
	'collectibles_file' => '',

	// SteamID64s that can see the admin order log on /admin.php
	'admins' => [
		// '76561198000000000',
	],
];
