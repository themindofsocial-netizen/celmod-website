<?php
/*
 * Loaded by every page. Sets up config, session, the database and helpers.
 */
declare(strict_types=1);

define('CM_ROOT', dirname(__DIR__));

$cmConfigFile = CM_ROOT . '/config.php';
// Without config.php the site still runs on the sample settings, but check.php
// and the offline notice say so loudly, since the sample password never works.
define('CM_CONFIG_FOUND', is_file($cmConfigFile));
$CM_CONFIG = CM_CONFIG_FOUND ? require $cmConfigFile : require CM_ROOT . '/config.sample.php';

if (session_status() === PHP_SESSION_NONE) {
	session_name('celmod');
	session_set_cookie_params([
		'httponly' => true,
		'samesite' => 'Lax',
		'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
	]);
	session_start();
}

require __DIR__ . '/helpers.php';
require __DIR__ . '/db.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/layout.php';
