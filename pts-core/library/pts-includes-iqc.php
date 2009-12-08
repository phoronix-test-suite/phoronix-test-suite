<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel
	pts-includes-comparisons.php: Functions needed for performing reference comparisons

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

function pts_rgb_gd_color_at(&$img, $x, $y)
{
	$rgb = imagecolorat($img, $x, $y);

	// R, G, B
	return array(($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF);
}
function pts_rgb_int_diff($rgb1, $rgb2)
{
	return abs(array_sum($rgb1) - array_sum($rgb2));
}
function pts_color_pixel_delta(&$base_img, &$compare_img, &$x, &$y)
{
	$color = false; // for now we set to true when it should change, but ultimately should return a color

	$check_points = array(
	array($x + 1, $y),
	array($x - 1, $y),
	array($x, $y + 1),
	array($x, $y - 1)
	);

	foreach($check_points as $point_r)
	{
		if(pts_rgb_int_diff(pts_rgb_gd_color_at($base_img, $point_r[0], $point_r[1]), pts_rgb_gd_color_at($compare_img, $point_r[0], $point_r[1])) > 12)
		{
			$color = array($point_r[0], $point_r[1]);
			break;
		}
	}

	return $color;
}

?>
