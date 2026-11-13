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
	'site_url' => 'https://delaware.rockzehh.net',

	// Optional: a Steam Web API key (https://steamcommunity.com/dev/apikey).
	// Only used to fetch avatars. Leave empty to skip.
	'steam_api_key' => '',

	// The server the "Connect" links and live page show by default.
	'server' => [
		'id'      => 1,              // must match cm_db_server_id on the game server
		'address' => '158.69.118.241:27015',
		'name'    => '|CelMod| Beta Testing Server',
	],

	// Seconds after the last heartbeat before the server is shown as offline.
	'offline_after' => 90,

	// Emblem Editor: path to the game server's collectibles.txt, if this site runs
	// on the same machine, so the editor always shows the live emblems.
	// Leave empty to use the copy in emblem-editor/collectibles.txt.
	'collectibles_file' => '',

	// SteamID64s that can see the admin order log on /admin.php
	'admins' => [
		// '76561198000000000',
	],
];
