<?php
/*
 * The Updates page's entries: the ones posted from the admin panel (cm_updates) together with
 * the older hand-written ones (updates_static.php), newest first.
 */

/**
 * Turns an update written in the admin panel into the page's HTML. Plain text, with a little markup:
 *   !command or /command  -> green, like the rest of the page
 *   **bold**  *italics*  ~~struck out~~
 *   Each new line starts an indented line under the date, like the older entries.
 */
function cm_update_format(string $body): string
{
	$html = h(trim(str_replace("\r", '', $body)));
	$html = preg_replace('/\*\*(.+?)\*\*/s', '<b>$1</b>', $html);
	$html = preg_replace('/(?<![\w*])\*(?!\s)(.+?)(?<!\s)\*(?![\w*])/s', '<i>$1</i>', $html);
	$html = preg_replace('/~~(.+?)~~/s', '<span class="strikeout">$1</span>', $html);
	$html = preg_replace('~(^|[\s(>])([!/][a-z][a-z0-9_]*)~i', '$1<span class="highlight">$2</span>', $html);
	$lines = array_values(array_filter(array_map('trim', explode("\n", $html)), 'strlen'));
	return implode('<br>' . "\n" . '<span class="indent"></span>', $lines);
}

/** Every update, newest first: [['date' => 'Y-m-d', 'html' => ..., 'id' => int|null], ...]. */
function cm_all_updates(bool $includeHidden = false): array
{
	$list = [];
	foreach (cm_db() ? cm_rows('SELECT id, posted_on, body, published FROM cm_updates' . ($includeHidden ? '' : ' WHERE published = 1')) : [] as $r) {
		$list[] = ['date' => $r['posted_on'], 'html' => cm_update_format($r['body']), 'id' => (int)$r['id'], 'published' => (int)$r['published']];
	}
	foreach (require __DIR__ . '/updates_static.php' as [$date, $html]) {
		$list[] = ['date' => $date, 'html' => $html, 'id' => null, 'published' => 1];
	}
	// Newest first; posted ones before hand-written ones on the same day.
	usort($list, static fn ($a, $b) => [$b['date'], $b['id'] ?? 0] <=> [$a['date'], $a['id'] ?? 0]);
	return $list;
}
