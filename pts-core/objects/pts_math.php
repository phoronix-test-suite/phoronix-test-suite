<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2021, Phoronix Media
	Copyright (C) 2009 - 2021, Michael Larabel

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
	public static function values_outside_three_sigma_limits($values)
	{
		$p = pts_math::get_precision($values);
		$tsl = pts_math::three_sigma_limits($values, $p);
		$outside_limits = array();
		foreach($values as $num)
		{
			if($num < $tsl[0] || $num > $tsl[1])
			{
				$outside_limits[] = $num;
			}
		}

		return empty($outside_limits) ? false : $outside_limits;
	}
	public static function three_sigma_limits($values, $p = 2)
	{
		$avg = pts_math::arithmetic_mean($values);
		$variance = pts_math::variance($values, $avg);
		$std_dev = sqrt($variance);
		$std_dev_3x = $std_dev * 3;

		return array(round($avg - $std_dev_3x, $p), round($avg + $std_dev_3x, $p));
	}
	public static function variance($values, $avg)
	{
		return array_sum(array_map(function($v) use ($avg) { return pow($v - $avg, 2); }, $values)) / count($values);
	}
	public static function arithmetic_mean($values)
	{
		return array_sum($values) / count($values);
	}
	public static function geometric_mean($values)
	{
		// simple code hits INF issue on large arrays
		//return pow(array_product($values), (1 / count($values)));
		$power = 1 / count($values);
		$chunk_r = array();

		foreach(array_chunk($values, 8) as $chunk)
		{
			$chunk_r[] = pow(array_product($chunk), $power);
		}

		return array_product($chunk_r);
	}
	public static function harmonic_mean($values)
	{
		// useful for rates / all same result types
		$sum = 0;
		foreach($values as $v)
		{
			$sum += 1 / $v;
		}
		return (1 / $sum) * count($values);
	}
	public static function max_number($a, $b, $fallback_on_no_number = 1)
	{
		if(($an = is_numeric($a)) && ($bn = is_numeric($b)))
		{
			return max($a, $b);
		}
		else if($an)
		{
			return $a;
		}
		else if(isset($bn) && $bn)
		{
			return $b;
		}
		else
		{
			return $fallback_on_no_number;
		}
	}
	public static function standard_error($values)
	{
		self::clean_numeric_array($values);

		return empty($values) ? 0 : (self::standard_deviation($values) / sqrt(count($values)));
	}
	public static function remove_outliers($values, $mag = 2)
	{
		$ret = array();
		$mean = pts_math::arithmetic_mean($values);
		$std_dev = self::standard_deviation($values);
		$outlier = $mag * $std_dev;
		foreach($values as $i)
		{
			if(is_numeric($i) && abs($i - $mean) <= $outlier)
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
		$average_value = pts_math::arithmetic_mean($values);

		return $average_value != 0 ? ($standard_deviation / $average_value * 100) : 0;
	}
	public static function get_precision($number)
	{
		// number of decimal digits
		if($number === null)
		{
			return 0;
		}
		else if(is_array($number))
		{
			$max_precision = 0;
			foreach($number as $n)
			{
				$max_precision = max($max_precision, pts_math::get_precision($n));
			}

			return $max_precision;
		}
		else
		{
			return strlen(substr(strrchr($number, '.'), 1));
		}
	}
	public static function set_precision($number, $precision = 2)
	{
		// This is better than using round() with precision because of the $precision is > than the current value, 0s will not be appended
		return is_numeric($number) ? number_format($number, $precision, '.', '') : $number;
	}
	public static function find_percentile($values, $quartile, $values_sorted = false)
	{
		if($values_sorted == false)
		{
			sort($values, SORT_NUMERIC);
		}
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
	public static function median($values)
	{
		if(count($values) < 3)
		{
			return;
		}

		sort($values);
		$count = count($values);
		$index = floor($count / 2);
		if($count & 1)
		{
			return $values[$index];
		}
		else if(is_numeric($values[($index - 1)]) && is_numeric($values[($index + 1)]))
		{
			return ($values[($index - 1)] + $values[($index + 1)]) / 2;
		}
	}
	public static function number_with_unit_to_mb($value)
	{
		$value = trim($value);
		$num = null;
		$unit = null;
		if(($x = strpos($value, ' ')) !== false)
		{
			$num = substr($value, 0, $x);
			$unit = substr($value, ($x + 1));
		}
		else
		{
			switch(substr($value, -1))
			{
				case 'K':
					$num = substr($value, 0, -1);
					$unit = 'K';
					break;
			}
		}

		return $num != null && $unit != null ? self::unit_to_mb($num, $unit) : null;
	}
	public static function unit_to_mb($value, $current_unit)
	{
		$v = null;
		switch($current_unit)
		{
			case 'kb':
				$v = $value / 1000;
				break;
			case 'K':
				$v = $value / 1024;
				break;
			case 'MiB':
				$v = $value;
				break;
		}

		return $v;
	}
}

?>
