<?php
/*
 * Emblem suggestions: reading the server's emblem list and cleaning player submissions.
 *
 * Nothing here ever writes to collectibles.txt. A suggestion is parsed, every key is checked
 * against a whitelist, every value against its expected format, and the block is rebuilt from
 * scratch, so what an admin copies to the server can only ever be one well-formed emblem.
 */

/** The file the server's emblems are read from (read only). */
function cm_collectibles_file(): string
{
	return cm_config('collectibles_file') ?: CM_ROOT . '/emblem-editor/collectibles.txt';
}

/**
 * Minimal Valve KeyValues reader: returns a list of [key, value] where value is a string or a
 * nested list. Comments (//) are skipped. Throws on malformed input.
 */
function cm_kv_parse(string $text): array
{
	preg_match_all('#//[^\n]*|"((?:[^"\\\\]|\\\\.)*)"|([{}])|([^\s{}"]+)#', $text, $m, PREG_SET_ORDER);
	$tokens = [];
	foreach ($m as $t) {
		if (strncmp($t[0], '//', 2) === 0) {
			continue;
		}
		if (isset($t[2]) && $t[2] !== '') {
			$tokens[] = ['brace' => $t[2]];
		} elseif ($t[0][0] === '"') {
			$tokens[] = ['str' => $t[1]];
		} else {
			$tokens[] = ['str' => $t[3] ?? ''];
		}
	}

	$i = 0;
	$block = static function () use (&$block, &$tokens, &$i): array {
		$out = [];
		while ($i < count($tokens) && ($tokens[$i]['brace'] ?? '') !== '}') {
			$key = $tokens[$i++];
			if (isset($key['brace'])) {
				throw new RuntimeException('Expected a key name.');
			}
			$next = $tokens[$i++] ?? null;
			if ($next === null) {
				throw new RuntimeException('"' . $key['str'] . '" has no value.');
			}
			if (($next['brace'] ?? '') === '{') {
				$out[] = [$key['str'], $block()];
				if (($tokens[$i]['brace'] ?? '') !== '}') {
					throw new RuntimeException('Missing a closing brace.');
				}
				$i++;
			} elseif (isset($next['brace'])) {
				throw new RuntimeException('Unexpected brace after "' . $key['str'] . '".');
			} else {
				$out[] = [$key['str'], $next['str']];
			}
		}
		return $out;
	};

	$tree = $block();
	if ($i < count($tokens)) {
		throw new RuntimeException('Unexpected closing brace.');
	}
	return $tree;
}

/** Ids of the emblems on the server (lowercase), from collectibles.txt. */
function cm_server_emblem_ids(): array
{
	static $ids = null;
	if ($ids !== null) {
		return $ids;
	}
	$ids = [];
	$text = @file_get_contents(cm_collectibles_file());
	if ($text === false) {
		return $ids;
	}
	try {
		$find = static function (array $tree) use (&$find): ?array {
			foreach ($tree as [$k, $v]) {
				if (is_array($v) && strtolower($k) === 'emblems') {
					return $v;
				}
			}
			foreach ($tree as [, $v]) {
				if (is_array($v) && ($hit = $find($v)) !== null) {
					return $hit;
				}
			}
			return null;
		};
		foreach ($find(cm_kv_parse($text)) ?? [] as [$k, $v]) {
			if (is_array($v)) {
				$ids[strtolower($k)] = true;
			}
		}
	} catch (RuntimeException $e) {
		error_log('|CelMod| could not read the emblem list: ' . $e->getMessage());
	}
	return $ids;
}

/** Allowed keys and how to check each value. Order is the order they are written in. */
function cm_emblem_rules(): array
{
	$num = static fn (float $min, float $max) => ['num', $min, $max];
	$int = static fn (int $min, int $max) => ['int', $min, $max];
	return [
		'name'              => ['text', 48],
		'model'             => ['regex', '#^models/[A-Za-z0-9_\-/]{1,120}\.mdl$#'],
		'skin'              => $int(0, 31),
		'color'             => ['rgba'],
		'scale'             => $num(0.01, 20),
		'handscale'         => $num(0.01, 20),
		'handoffset'        => ['vec', 500],
		'handangles'        => ['vec', 360],
		'power'             => ['enum', ['none', 'shot', 'flamethrower', 'spray', 'medic', 'magnitude', 'portal', 'chaos', 'blast', 'demon', 'blink', 'flash', 'smog', 'nuke', 'speed', 'sprint', 'gravity', 'jump', 'feather', 'heal', 'armor', 'resist', 'cloak', 'drain', 'gold']],
		'value'             => $num(-100000, 100000),
		'cooldown'          => $num(0, 600),
		'duration'          => $num(0, 600),
		'range'             => $num(0, 100000),
		'dmgtype'           => ['enum', ['generic', 'burn', 'drown', 'poison', 'shock', 'dissolve', 'energy']],
		'ignite'            => $num(0, 600),
		'slow'              => $num(0, 600),
		'knockback'         => $num(0, 100000),
		'speed'             => $num(0.1, 10),
		'fx'                => ['enum', ['beam', 'flame', 'ice', 'water', 'energy', 'smoke', 'orb', 'model', 'spray', 'laser', 'plasma', 'rainbow', 'none']],
		'sprite'            => ['regex', '#^(sprites|models|materials)/[A-Za-z0-9_\-/]{1,120}\.(vmt|mdl)$#'],
		'secondary'         => ['enum', ['fireball', 'icestorm', 'bigshot']],
		'secondaryvalue'    => $num(0, 100000),
		'secondarycooldown' => $num(0, 600),
		'beamcolor'         => ['rgba'],
		'jumpboost'         => $num(0, 5000),
		'falldamage'        => $num(0, 10),
		'playercolor'       => ['rgba'],
		'glow'              => $int(0, 1),
		'upgradesto'        => ['regex', '#^[a-z0-9_]{2,32}$#'],
		'upgradecost'       => $int(1, 100),
		'dropweight'        => $int(0, 100),
	];
}

/**
 * Cleans one suggested emblem. Returns ['id' => ..., 'name' => ..., 'kv' => rebuilt text,
 * 'dropped' => [keys left out]] or throws RuntimeException with a message for the player.
 */
function cm_emblem_clean(string $text): array
{
	if (strlen($text) > 6000) {
		throw new RuntimeException('That emblem is too long.');
	}

	$tree = cm_kv_parse($text);
	$blocks = array_values(array_filter($tree, static fn ($e) => is_array($e[1])));
	if (count($tree) !== 1 || count($blocks) !== 1) {
		throw new RuntimeException('Suggest one emblem at a time (one "id" { ... } block).');
	}

	[$id, $rows] = $blocks[0];
	$id = strtolower($id);
	if (!preg_match('/^[a-z0-9_]{2,32}$/', $id)) {
		throw new RuntimeException('The id must be 2 to 32 lowercase letters, numbers or _.');
	}
	if (isset(cm_server_emblem_ids()[$id])) {
		throw new RuntimeException("\"$id\" is already an emblem on the server. Suggestions must be new emblems: give it a new id (Duplicate does this for you).");
	}

	$rules = cm_emblem_rules();
	$clean = [];
	$dropped = [];

	foreach ($rows as [$key, $value]) {
		$key = strtolower((string)$key);
		if (is_array($value) || !isset($rules[$key])) {
			$dropped[] = $key;
			continue;
		}
		$value = trim((string)$value);
		$rule = $rules[$key];
		$ok = false;

		switch ($rule[0]) {
			case 'text':
				$ok = $value !== '' && mb_strlen($value) <= $rule[1] && !preg_match('#["{}\\\\\\x00-\\x1f]|//#u', $value);
				break;
			case 'regex':
				$ok = (bool)preg_match($rule[1], $value) && strpos($value, '..') === false;
				break;
			case 'enum':
				$value = strtolower($value);
				$ok = in_array($value, $rule[1], true);
				break;
			case 'int':
				$ok = preg_match('/^-?\d+$/', $value) && (int)$value >= $rule[1] && (int)$value <= $rule[2];
				$value = (string)(int)$value;
				break;
			case 'num':
				$ok = is_numeric($value) && (float)$value >= $rule[1] && (float)$value <= $rule[2];
				$value = rtrim(rtrim(sprintf('%.4F', (float)$value), '0'), '.');
				if (strpos($value, '.') === false) {
					$value .= '.0';
				}
				break;
			case 'rgba':
				$parts = preg_split('/\s+/', $value);
				$ok = (count($parts) === 3 || count($parts) === 4);
				foreach ($parts as $p) {
					$ok = $ok && preg_match('/^\d{1,3}$/', $p) && (int)$p <= 255;
				}
				if ($ok && count($parts) === 3) {
					$parts[] = '255';
				}
				$value = implode(' ', array_map('intval', $parts));
				break;
			case 'vec':
				$parts = preg_split('/\s+/', $value);
				$ok = count($parts) === 3;
				foreach ($parts as $p) {
					$ok = $ok && is_numeric($p) && abs((float)$p) <= $rule[1];
				}
				$value = implode(' ', array_map(static fn ($p) => rtrim(rtrim(sprintf('%.2F', (float)$p), '0'), '.'), $parts));
				break;
		}

		if (!$ok) {
			throw new RuntimeException("\"$key\" has a value the server would not accept: " . mb_substr($value, 0, 40));
		}
		$clean[$key] = $value;
	}

	if (!isset($clean['name'])) {
		if (array_filter($rows, static fn ($e) => is_array($e[1]))) {
			throw new RuntimeException('Suggest one emblem at a time (one "id" { ... } block), not a whole section.');
		}
		throw new RuntimeException('Give your emblem a name.');
	}
	$clean += ['model' => 'models/celmod/phys8.mdl', 'skin' => '0', 'color' => '255 255 255 255', 'power' => 'none'];
	if (isset($clean['upgradesto']) && !isset(cm_server_emblem_ids()[$clean['upgradesto']])) {
		throw new RuntimeException('"upgradesto" must be an emblem that exists on the server.');
	}

	// Rebuild the block in the server's layout; nothing from the submitted text is copied verbatim.
	$out = "\"$id\"\n{\n";
	foreach (array_keys($rules) as $key) {
		if (isset($clean[$key])) {
			$out .= "\t\"$key\"" . str_repeat("\t", max(1, 4 - intdiv(strlen($key) + 2, 4))) . '"' . $clean[$key] . "\"\n";
		}
	}
	$out .= "}\n";

	return ['id' => $id, 'name' => $clean['name'], 'kv' => $out, 'dropped' => array_values(array_unique($dropped))];
}
