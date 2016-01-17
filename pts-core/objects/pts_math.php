<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2016, Phoronix Media
	Copyright (C) 2009 - 2016, Michael Larabel

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

class pts_math
{
	public static function geometric_mean($values)
	{
		return pow(array_product($values), (1 / count($values)));
	}
	public static function harmonic_mean($values)
	{
		$b = 0;
		$c = 0;

		foreach($values as $value)
		{
			if($value != 0)
			{
				$b += 1 / $value;
				$c++;
			}
		}

		return $b != 0 ? $c / $b : 0;
	}
	public static function standard_error($values)
	{
		self::clean_numeric_array($values);

		return empty($values) ? 0 : (self::standard_deviation($values) / sqrt(count($values)));
	}
	public static function remove_outliers($values, $mag = 2)
	{
		$ret = array();
		$mean = array_sum($values) / count($values);
		$std_dev = self::standard_deviation($values);
		$outlier = $mag * $std_dev;
		foreach($values as $i)
		{
			if(abs($i - $mean) < $outlier)
			{
				$ret[] = $i;
			}
		}

		return $ret;
	}
	public static function standard_deviation($values)
	{
		self::clean_numeric_array($values);
		$count = count($values);

		if($count < 2)
		{
			return 0;
		}

		$total = array_sum($values);
		$mean = $total / $count;
		$standard_sum = 0;

		foreach($values as $value)
		{
			$standard_sum += pow(($value - $mean), 2);
		}

		return sqrt($standard_sum / ($count - 1));
	}
	public static function percent_standard_deviation($values)
	{
		if(count($values) == 0)
		{
			// No values
			return 0;
		}

		$standard_deviation = pts_math::standard_deviation($values);
		$average_value = array_sum($values) / count($values);

		return $average_value != 0 ? ($standard_deviation / $average_value * 100) : 0;
	}
	public static function set_precision($number, $precision = 2)
	{
		// This is better than using round() with precision because of the $precision is > than the current value, 0s will not be appended
		return number_format($number, $precision, '.', '');
	}
	public static function find_percentile($values, $quartile)
	{
		sort($values, SORT_NUMERIC);
		$qr_index = count($values) * $quartile;
		$qr = $values[floor($qr_index)];

		return $qr;
	}
	public static function first_quartile($values)
	{
		return self::find_percentile($values, 0.25);
	}
	public static function third_quartile($values)
	{
		return self::find_percentile($values, 0.75);
	}
	public static function inter_quartile_range($values)
	{
		return self::third_quartile($values) - self::first_quartile($values);
	}
	protected static function clean_numeric_array(&$values)
	{
		foreach($values as $i => $value)
		{
			if(!is_numeric($value))
			{
				unset($values[$i]);
			}
		}
	}
}

?>
