<?php
/*
 * Emblem drops, run from the admin panel (Emblem Drops tab).
 *
 * cm_drop_pool    an admin's choice of whether an emblem can drop, overriding collectibles.txt.
 *                 Only emblems an admin changed have a row; the rest keep the server's own setting.
 * cm_drop_schedule drops to start at a set time, once or every few minutes.
 *
 * celmod-database on the game server reads both about every 30 seconds and applies them.
 */

require_once __DIR__ . '/emblems.php';

const CM_RARITIES = ['Common', 'Uncommon', 'Rare', 'Epic', 'Legendary'];

/**
 * Every emblem on the server, as id => [name, weight, rarity (0-4), exclusive, default (bool), color].
 * The rarity and default are worked out exactly as celmod-collectibles does.
 */
function cm_drop_emblems(): array
{
	$text = @file_get_contents(cm_collectibles_file());
	if ($text === false) {
		return [];
	}
	try {
		$tree = cm_kv_parse($text);
	} catch (RuntimeException $e) {
		error_log('|CelMod| could not read the emblem list: ' . $e->getMessage());
		return [];
	}

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

	$out = [];
	foreach ($find($tree) ?? [] as [$id, $block]) {
		if (!is_array($block)) {
			continue;
		}
		$kv = [];
		foreach ($block as [$k, $v]) {
			if (!is_array($v)) {
				$kv[strtolower($k)] = $v;
			}
		}
		$weight = (int)($kv['dropweight'] ?? 0);
		$rarity = array_search(ucfirst(strtolower(trim($kv['rarity'] ?? ''))), CM_RARITIES, true);
		if ($rarity === false) {
			$rarity = $weight >= 10 ? 0 : ($weight >= 6 ? 1 : ($weight >= 3 ? 2 : ($weight === 2 ? 3 : 4)));
		}
		$exclusive = trim($kv['exclusive'] ?? '');
		$out[strtolower($id)] = [
			'name'      => $kv['name'] ?? $id,
			'weight'    => $weight,
			'rarity'    => (int)$rarity,
			'exclusive' => $exclusive,
			'default'   => $weight > 0 && $exclusive === '',
			'color'     => $kv['color'] ?? '',
		];
	}
	return $out;
}

/** "255 0 0 255" -> "#ff0000", or '' when there is no usable color. */
function cm_drop_swatch(string $color): string
{
	$p = preg_split('/\s+/', trim($color));
	if (count($p) < 3) {
		return '';
	}
	return sprintf('#%02x%02x%02x', ...array_map(static fn ($n) => max(0, min(255, (int)$n)), array_slice($p, 0, 3)));
}
