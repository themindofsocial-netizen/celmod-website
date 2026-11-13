<?php
/*
 * Tiny server-rendered SVG bar chart in the site's own colours.
 * One series per chart, so no legend: the heading above names it.
 * Each bar has a hover tooltip (<title>) and a hit area taller than the bar.
 */
function cm_bar_chart(array $points, string $unit = '', int $width = 540, int $height = 150): string
{
	if (!$points) {
		return '<p class="muted">No data yet.</p>';
	}

	$padL = 34; $padR = 6; $padT = 10; $padB = 22;
	$plotW = $width - $padL - $padR;
	$plotH = $height - $padT - $padB;
	$max = max(1, max(array_map('intval', $points)));
	// round the axis top up to a "nice" number
	$mag = 10 ** floor(log10($max));
	$top = ceil($max / $mag) * $mag;
	$n = count($points);
	$slot = $plotW / $n;
	$barW = max(2, $slot - 2); // 2px gap between bars

	$svg = '<svg class="chart" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '" role="img">';

	// recessive gridlines + y labels
	foreach ([0, 0.5, 1] as $f) {
		$y = $padT + $plotH - $plotH * $f;
		$svg .= '<line x1="' . $padL . '" x2="' . ($width - $padR) . '" y1="' . $y . '" y2="' . $y . '" stroke="#555" stroke-width="1"/>';
		$svg .= '<text x="' . ($padL - 4) . '" y="' . ($y + 4) . '" fill="#AA8" font-size="10" text-anchor="end">' . h(number_format($top * $f)) . '</text>';
	}

	$i = 0;
	$labelEvery = (int)ceil($n / 8);
	foreach ($points as $label => $value) {
		$value = (int)$value;
		$x = $padL + $i * $slot + ($slot - $barW) / 2;
		$bh = $value > 0 ? max(2, $plotH * $value / $top) : 0;
		$y = $padT + $plotH - $bh;
		$tip = h($label . ': ' . number_format($value) . ($unit ? ' ' . $unit : ''));
		$svg .= '<g><title>' . $tip . '</title>';
		$svg .= '<rect x="' . ($padL + $i * $slot) . '" y="' . $padT . '" width="' . $slot . '" height="' . $plotH . '" fill="transparent"/>';
		if ($bh > 0) {
			$r = min(4, $barW / 2, $bh / 2);
			// rounded top, square baseline
			$svg .= '<path class="bar" fill="#FFB200" d="M' . $x . ',' . ($y + $bh) . ' V' . ($y + $r) . ' Q' . $x . ',' . $y . ' ' . ($x + $r) . ',' . $y . ' H' . ($x + $barW - $r) . ' Q' . ($x + $barW) . ',' . $y . ' ' . ($x + $barW) . ',' . ($y + $r) . ' V' . ($y + $bh) . ' Z"/>';
		}
		$svg .= '</g>';
		if ($i % $labelEvery === 0) {
			$svg .= '<text x="' . ($padL + $i * $slot + $slot / 2) . '" y="' . ($height - 6) . '" fill="#AA8" font-size="10" text-anchor="middle">' . h((string)$label) . '</text>';
		}
		$i++;
	}

	return $svg . '</svg>';
}
