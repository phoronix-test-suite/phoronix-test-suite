<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel
	pts-functions_stats.php: Functions needed for statistics

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

function pts_standard_deviation($values)
{
	$total = array_sum($values);
	$count = count($values);
	$mean = $total / $count;
	$standard_sum = 0;

	if($count < 2)
	{
		return 0;
	}

	foreach($values as $value)
	{
		$standard_sum += pow(($value - $mean), 2);
	}

	return sqrt($standard_sum / ($count - 1));
}
function pts_percent_standard_deviation($values)
{
	$standard_deviation = pts_standard_deviation($values);
	$average_value = array_sum($values) / count($values);

	return $standard_deviation / $average_value * 100;
}

?>
