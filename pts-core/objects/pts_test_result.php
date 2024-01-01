<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2021, Phoronix Media
	Copyright (C) 2008 - 2021, Michael Larabel

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

class pts_test_result
{
	// Note in most pts-core code the initialized var is called $result_object
	// Note in pts-core code the initialized var is also called $test_run_request
	private $used_arguments;
	private $used_arguments_description;
	private $result_precision = 2;
	private $overrode_default_precision = false;
	private $annotation = null;
	private $parent_hash = null;

	public $test_profile;
	public $test_result_buffer;

	public $active = null;
	public $generated_result_buffers = null;
	public $test_run_times = null;

	// Added to make it easy to have PTS modules run a custom binary prior to running a program for the test
	public $exec_binary_prepend = null;

	protected $already_normalized = false;
	public $dynamically_generated = false;
	public $belongs_to_suite = false;
	public $pre_run_message = null;

	public function __construct($test_profile)
	{
		$this->test_profile = clone $test_profile;
		$this->test_run_times = array();
	}
	public function get_estimated_run_time()
	{
		// More accurate time tracking than just test_profile->get_estimated_run_time() ....
		return $this->get_estimated_per_run_time() * $this->test_profile->get_times_to_run();
	}
	public function get_estimated_per_run_time(&$accuracy = 0)
	{
		$per_run_time = 0;
		if(($t = $this->test_profile->test_installation->get_average_time_per_run($this->get_comparison_hash(true, false))) > 0)
		{
			$accuracy = 1;
			$per_run_time = $t;
		}
		else if(($t = $this->test_profile->test_installation->get_average_time_per_run('avg')) > 0)
		{
			$accuracy = 0;
			$per_run_time = $t;
		}
		else
		{
			$accuracy = 0;
			$per_run_time = $this->test_profile->get_estimated_run_time() / $this->test_profile->get_default_times_to_run();
		}

		return round($per_run_time);
	}
	public function __clone()
	{
		$this->test_profile = clone $this->test_profile;

		if(!empty($this->test_result_buffer) && is_object($this->test_result_buffer))
		{
			$this->test_result_buffer = clone $this->test_result_buffer;
		}
	}
	public function set_test_result_buffer(&$test_result_buffer)
	{
		$this->test_result_buffer = $test_result_buffer;
	}
	public function set_used_arguments_description($arguments_description)
	{
		$this->used_arguments_description = $arguments_description;
	}
	public function remove_from_used_arguments_description($remove_string)
	{
		$this->used_arguments_description = str_replace($remove_string, '', $this->used_arguments_description);
	}
	public function append_to_arguments_description($arguments_description)
	{
		if(strpos(' ' . $this->used_arguments_description . ' ', ' ' . $arguments_description . ' ') === false)
		{
			if(($x = strpos($arguments_description, ': ')) !== false)
			{
				$prefix_being_added = substr($arguments_description, 0, $x);

				// Remove the old prefix to avoid when re-running tests that it could see multiple things appended from older version
				// Encountered when introducing the append to test arguments for Mad Max test profile
				if(($x = strpos($this->used_arguments_description, ' ' . $prefix_being_added)) !== false)
				{
					$this->used_arguments_description = substr($this->used_arguments_description, 0, $x);
				}
			}

			$this->used_arguments_description .= ($this->used_arguments_description != null && $arguments_description[0] != ' ' ? ' ' : null) . $arguments_description;
		}
	}
	public function set_suite_parent($suite)
	{
		$this->belongs_to_suite = $suite;
	}
	public function belongs_to_suite()
	{
		return $this->belongs_to_suite ? $this->belongs_to_suite : false;
	}
	public function set_result_precision($precision)
	{
		if(!is_numeric($precision) || $precision < 0)
		{
			return false;
		}

		$this->result_precision = $precision;
		$this->overrode_default_precision = true;
	}
	public function get_result_precision()
	{
		if(!$this->overrode_default_precision && isset($this->active->results) && !empty($this->active->results))
		{
			// default precision
			$p = pts_math::get_precision($this->active->results);
			if($p > 0 && $p < 10)
			{
				return $p;
			}
		}

		return $this->result_precision;
	}
	public function set_used_arguments($used_arguments)
	{
		$this->used_arguments = $used_arguments;
	}
	public function get_arguments()
	{
		return $this->used_arguments;
	}
	public function get_arguments_description()
	{
		return $this->used_arguments_description;
	}
	public function set_annotation($annotation)
	{
		$this->annotation = $annotation;
	}
	public function append_annotation($annotation)
	{
		if(strpos($this->annotation, $annotation) === false)
		{
			$this->annotation .= ' ' . PHP_EOL . $annotation;
		}
	}
	public function get_annotation()
	{
		return $this->annotation;
	}
	public function set_parent_hash_from_result(&$result_object)
	{
		$this->parent_hash = $result_object->get_comparison_hash(true, false);
	}
	public function set_parent_hash($parent)
	{
		$this->parent_hash = $parent;
	}
	public function get_parent_hash()
	{
		return $this->parent_hash;
	}
	public function get_arguments_description_shortened($compact_words = true)
	{
		$shortened = explode(' - ', $this->used_arguments_description);
		foreach($shortened as &$part)
		{
			if(($x = strpos($part, ': ')) !== false)
			{
				$part = substr($part, $x + 2);
			}
			if($compact_words && isset($part[18]) && strpos($part, ' ') != false && function_exists('preg_replace'))
			{
				$part = implode('.', str_split(preg_replace('/\b(\w)|./', '$1', $part)));
			}
		}

		$shortened = implode(' - ', $shortened) . ' ';

		$shorten_words = array(
			'Random' => 'Rand',
			'Sequential' => 'Seq',
			'Frequency' => 'Freq',
			'Temperature' => 'Temp',
			'localhost -' => '',
			'- D.T.D' => '',
			);

		foreach($shorten_words as $word => $to)
		{
			$shortened = str_ireplace($word . ' ', $to . ' ', $shortened);
		}

		return trim($shortened);
	}
	public function get_comparison_hash($show_version_and_attributes = true, $raw_output = true)
	{
		if($show_version_and_attributes)
		{
			$tp = $this->test_profile->get_identifier(true);
			if($tp == null)
			{
				$tp = $this->test_profile->get_title();
			}
			else if(($x = strrpos($tp, '.')) !== false)
			{
				// remove the last segment of the test profile version that should be in xx.yy.zz format
				// this removal is done since the zz segment should be maintainable between comparisons
				$tp = substr($tp, 0, $x);
			}
			return pts_test_profile::generate_comparison_hash($tp, $this->get_arguments(), $this->get_arguments_description(), $this->test_profile->get_app_version(), $this->test_profile->get_result_scale(), $raw_output);
		}
		else
		{
			$tp = $this->test_profile->get_identifier(false);
			if($tp == null)
			{
				$tp = $this->test_profile->get_title();
			}

			return pts_test_profile::generate_comparison_hash($tp, $this->get_arguments(), '', '', $raw_output);
		}
	}
	public function __toString()
	{
		return $this->test_profile->get_identifier(false) . ' ' . $this->get_arguments() . ' ' . $this->get_arguments_description() . ' ' . $this->test_profile->get_override_values();
	}
	public function largest_result_variation($break_if_greater_than = false)
	{
		if($this->test_profile->get_display_format() != 'BAR_GRAPH') // BAR_ANALYZE_GRAPH is currently unsupported
		{
			return false;
		}

		$is_multi_way = pts_render::multi_way_identifier_check($this->test_result_buffer->get_identifiers());
		$keys = array_keys($this->test_result_buffer->buffer_items);

		if($is_multi_way)
		{
			$key_sets = array();
			foreach($keys as $k)
			{
				$identifier_r = pts_strings::trim_explode(': ', $this->test_result_buffer->buffer_items[$k]->get_result_identifier());

				if(!isset($key_sets[$identifier_r[0]]))
				{
					$key_sets[$identifier_r[0]] = array();
				}

				$key_sets[$identifier_r[0]][] = $k;
			}
		}
		else
		{
			$key_sets = array($keys);
		}

		$largest_variation = 0;
		foreach($key_sets as $keys)
		{
			$divide_value = 0;
			foreach($keys as $k)
			{
				if(!is_numeric($this->test_result_buffer->buffer_items[$k]->get_result_value()))
				{
					continue;
				}
				if($divide_value == 0)
				{
					$divide_value = $this->test_result_buffer->buffer_items[$k]->get_result_value();
					continue;
				}
				$variation = ($this->test_result_buffer->buffer_items[$k]->get_result_value() / $divide_value) - 1;

				if(abs($variation) > $largest_variation)
				{
					$largest_variation = $variation;

					if($this->test_profile->get_result_proportion() == 'LIB')
						$largest_variation = 0 - $largest_variation;

					if($break_if_greater_than !== false && abs($largest_variation) > $break_if_greater_than)
					{
						return $largest_variation;
					}
				}
			}
		}

		return $largest_variation;
	}
	public function get_result_first($return_identifier = true)
	{
		// a.k.a. the result winner
		$winner = null;

		if($this->test_profile->get_result_proportion() == 'LIB')
		{
			$winner = $this->test_result_buffer->get_min_value($return_identifier);
		}
		else if($this->test_profile->get_result_proportion() == 'HIB')
		{
			$winner = $this->test_result_buffer->get_max_value($return_identifier);
		}

		return $winner;
	}
	public function get_result_last($return_identifier = true)
	{
		// a.k.a. the result loser
		$winner = null;

		if($this->test_profile->get_result_proportion() == 'HIB')
		{
			$winner = $this->test_result_buffer->get_min_value($return_identifier);
		}
		else if($this->test_profile->get_result_proportion() == 'LIB')
		{
			$winner = $this->test_result_buffer->get_max_value($return_identifier);
		}

		return $winner;
	}
	public function result_flat($distance = 0.03)
	{
		if($this->get_spread(false) < (1 + $distance))
		{
			return true;
		}

		$values = $this->test_result_buffer->get_values();
		if(($value_count = count($values)) > 3)
		{
			$values_no_o = pts_math::remove_outliers($values, 1.5);
			if(empty($values_no_o))
			{
				return true;
			}
			$avg = array_sum($values_no_o) / count($values_no_o);
			$upper_threshold = $avg * (1 + $distance);
			$lower_threshold = $avg * (1 - $distance);
			$c = 0;
//echo $this->test_profile->get_title() . ' - ' . $avg . ' ' . $upper_threshold . ' ' . $lower_threshold . '<br />';
			foreach($values as $v)
			{
				if($v > $lower_threshold && $v < $upper_threshold)
				{
					$c++;

					if($c > ($value_count * 0.3))
					{
						return true;
					}
				}
			}
		}

		return false;
	}
	public function get_spread($noisy_check = true)
	{
		if($noisy_check && $this->has_noisy_result())
		{
			return -1;
		}
		if($this->get_parent_hash() != null)
		{
			return -1;
		}

		$best = $this->get_result_first(false);
		$worst = $this->get_result_last(false);

		if(!is_numeric($best) || !is_numeric($worst) || $worst == 0)
		{
			return -1;
		}

		$spread = $best / $worst;
		if($this->test_profile->get_result_proportion() == 'LIB' && $spread != 0)
		{
			$spread = 1 / $spread;
		}

		return $spread;
	}
	public function normalize_buffer_values($normalize_against = false, $extra_attributes = null)
	{
		if($this->test_profile->get_display_format() != 'BAR_GRAPH') // BAR_ANALYZE_GRAPH is currently unsupported
		{
			return false;
		}
		if($this->already_normalized)
		{
			return false;
		}
		$this->already_normalized = true;

		$is_multi_way = pts_render::multi_way_identifier_check($this->test_result_buffer->get_identifiers());
		$keys = array_keys($this->test_result_buffer->buffer_items);

		if($is_multi_way)
		{
			$key_sets = array();
			foreach($keys as $k)
			{
				$identifier_r = pts_strings::trim_explode(': ', $this->test_result_buffer->buffer_items[$k]->get_result_identifier());
				$position = !isset($extra_attributes['multi_way_comparison_invert_default']) ? 0 : 1;

				if(!isset($key_sets[$identifier_r[$position]]))
				{
					$key_sets[$identifier_r[$position]] = array();
				}

				$key_sets[$identifier_r[$position]][] = $k;
			}
		}
		else
		{
			$key_sets = array($keys);
		}

		foreach($key_sets as $keys)
		{
			foreach($keys as $i => $k)
			{
				if(!is_numeric($this->test_result_buffer->buffer_items[$k]->get_result_value()))
				{
					unset($keys[$i]);
				}
			}
			if($this->test_profile->get_result_proportion() == 'LIB')
			{
				// Invert values for LIB
				foreach($keys as $k)
				{
					$this->test_result_buffer->buffer_items[$k]->reset_result_value((1 / $this->test_result_buffer->buffer_items[$k]->get_result_value()), false);
				}
			}

			$divide_value = -1;
			if($normalize_against != false)
			{
				foreach($keys as $k)
				{
					if(strpos($this->test_result_buffer->buffer_items[$k]->get_result_identifier(), strval($normalize_against)) !== false)
					{
						$divide_value = $this->test_result_buffer->buffer_items[$k]->get_result_value();
						break;
					}
				}
			}

			if($divide_value == -1)
			{
				if($is_multi_way) // find the largest value to use as divide value
				{
					foreach($keys as $k)
					{
						if($this->test_result_buffer->buffer_items[$k]->get_result_value() > $divide_value || $divide_value == -1)
						{
							$divide_value = $this->test_result_buffer->buffer_items[$k]->get_result_value();
						}
					}
				}
				else // find the lowest value to use as divide value
				{
					foreach($keys as $k)
					{
						if($this->test_result_buffer->buffer_items[$k]->get_result_value() < $divide_value || $divide_value == -1)
						{
							$divide_value = $this->test_result_buffer->buffer_items[$k]->get_result_value();
						}
					}
				}
			}

			if($divide_value != 0 && is_numeric($divide_value))
			{
				foreach($keys as $k)
				{
					$normalized = ($this->test_result_buffer->buffer_items[$k]->get_result_value() / $divide_value);
					$normalized_attempt = pts_math::set_precision($normalized, max(3, $this->result_precision));
					$normalized = !empty($normalized_attempt) ? $normalized_attempt : $normalized;
					$this->test_result_buffer->buffer_items[$k]->reset_result_value($normalized, false);
					$this->test_result_buffer->buffer_items[$k]->reset_raw_value(0);
				}
			}
		}

		$this->test_profile->set_result_proportion('HIB');
		$this->test_profile->set_result_scale('Relative Performance');
		$this->test_result_buffer->recalculate_buffer_items_min_max();
		return true;
	}
	public function sort_results_by_performance()
	{
		$this->test_result_buffer->buffer_values_sort();

		if($this->test_profile->get_result_proportion() == 'HIB')
		{
			$this->test_result_buffer->buffer_values_reverse();
		}
	}
	public function remove_unchanged_results($change_threshold = 0.03)
	{
		if($this->test_profile->get_display_format() != 'BAR_GRAPH') // BAR_ANALYZE_GRAPH is currently unsupported
		{
			return false;
		}

		$is_multi_way = pts_render::multi_way_identifier_check($this->test_result_buffer->get_identifiers());
		$keys = array_keys($this->test_result_buffer->buffer_items);

		if($is_multi_way)
		{
			$key_sets = array();
			foreach($keys as $k)
			{
				$identifier_r = pts_strings::trim_explode(': ', $this->test_result_buffer->buffer_items[$k]->get_result_identifier());
				if(!isset($key_sets[$identifier_r[0]]))
				{
					$key_sets[$identifier_r[0]] = array();
				}

				$key_sets[$identifier_r[0]][] = $k;
			}
		}
		else
		{
			$key_sets = array($keys);
		}

		foreach($key_sets as $keys)
		{
			$base_value = -1;
			$remove_set = true;
			foreach($keys as $k)
			{
				if(!is_numeric($this->test_result_buffer->buffer_items[$k]->get_result_value()))
				{
					continue;
				}

				if($base_value == -1)
				{
					$base_value = $this->test_result_buffer->buffer_items[$k]->get_result_value();
				}
				else if(abs($base_value - $this->test_result_buffer->buffer_items[$k]->get_result_value()) > ($base_value * $change_threshold))
				{
					$remove_set = false;
					break;
				}
			}

			if($remove_set)
			{
				foreach($keys as $k)
				{
					unset($this->test_result_buffer->buffer_items[$k]);
				}
			}
		}
		$this->test_result_buffer->recalculate_buffer_items_min_max();
		return true;
	}
	public function get_result_value_from_name($name)
	{
		$val = null;
		foreach(array_keys($this->test_result_buffer->buffer_items) as $k)
		{
			if($this->test_result_buffer->buffer_items[$k]->get_result_identifier() == $name)
			{
				$val = $this->test_result_buffer->buffer_items[$k]->get_result_value();
				break;
			}
		}
		return $val;
	}
	public function has_noisy_result($noise_level_percent = 6)
	{
		$val = null;
		$noisy_count = 0;
		$c = $this->test_result_buffer->get_count();
		foreach(array_keys($this->test_result_buffer->buffer_items) as $k)
		{
			if($this->test_profile->get_display_format() != 'BAR_GRAPH')
			{
				break;
			}
			$raw = $this->test_result_buffer->buffer_items[$k]->get_result_raw_array();
			if(!empty($raw) && count($raw) > 2)
			{
				if(($p = pts_math::percent_standard_deviation($raw)) > $noise_level_percent)
				{
					if($c > 5)
					{
						// if large result file, check to see multiple results end up being noisy
						$noisy_count++;

						if($noisy_count > ceil($c * 0.2))
						{
							return $p;
						}
					}
					else
						return $p;
				}
			}
		}
		return false;
	}
	public function remove_noisy_results($threshold = 0.6)
	{
		if($this->test_profile->get_display_format() != 'BAR_GRAPH') // BAR_ANALYZE_GRAPH is currently unsupported
		{
			return false;
		}

		$is_multi_way = pts_render::multi_way_identifier_check($this->test_result_buffer->get_identifiers());
		$keys = array_keys($this->test_result_buffer->buffer_items);

		if($is_multi_way)
		{
			$key_sets = array();
			foreach($keys as $k)
			{
				$identifier_r = pts_strings::trim_explode(': ', $this->test_result_buffer->buffer_items[$k]->get_result_identifier());
				if(!isset($key_sets[$identifier_r[0]]))
				{
					$key_sets[$identifier_r[0]] = array();
				}

				$key_sets[$identifier_r[0]][] = $k;
			}
		}
		else
		{
			$key_sets = array($keys);
		}

		foreach($key_sets as $keys)
		{
			$jiggy_results = 0;
			foreach($keys as $k)
			{
				$raw = $this->test_result_buffer->buffer_items[$k]->get_result_raw_array();
				if(!empty($raw))
				{
					$raw = pts_math::standard_error($raw);
					if($raw > 10)
					{
						$jiggy_results++;
					}
				}
			}

			if(($jiggy_results / count($keys)) > $threshold)
			{
				foreach($keys as $k)
				{
					unset($this->test_result_buffer->buffer_items[$k]);
				}
			}
		}
		$this->test_result_buffer->recalculate_buffer_items_min_max();
		return true;
	}
	public function recalculate_averages_without_outliers($mag = 2)
	{
		if($this->test_profile->get_display_format() != 'BAR_GRAPH') // BAR_ANALYZE_GRAPH is currently unsupported
		{
			return false;
		}

		foreach(array_keys($this->test_result_buffer->buffer_items) as $i => $k)
		{
			$raw = $this->test_result_buffer->buffer_items[$k]->get_result_raw_array();
			if(!empty($raw))
			{
				$raw = pts_math::remove_outliers($raw, $mag);
				if(count($raw) > 0)
				{
					$this->test_result_buffer->buffer_items[$k]->reset_result_value(pts_math::arithmetic_mean($raw));
					$this->test_result_buffer->buffer_items[$k]->reset_raw_value(implode(':', $raw));
				}
			}
		}
		$this->test_result_buffer->recalculate_buffer_items_min_max();
	}
	public function get_run_time_avg()
	{
		$total_times = array();

		foreach($this->test_result_buffer->get_buffer_items() as $item)
		{
			$total_time = $item->get_run_time_total();

			if($total_time > 0)
			{
				$total_times[] = $total_time;
			}
		}

		return !empty($total_times) ? array_sum($total_times) / count($total_times) : -1;
	}
	public function get_run_times()
	{
		$total_times = array();

		foreach($this->test_result_buffer->get_buffer_items() as $item)
		{
			$total_time = $item->get_run_time_total();

			if($total_time > 0)
			{
				$total_times[$item->get_result_identifier()] = $total_time;
			}
		}

		return $total_times;
	}
	public function points_of_possible_interest($threshold_level = 0.05, $adaptive = true)
	{
		$points_of_interest = array();
		if($this->test_profile->get_display_format() != 'BAR_GRAPH') // BAR_ANALYZE_GRAPH is currently unsupported
		{
			return $points_of_interest;
		}

		$is_multi_way = pts_render::multi_way_identifier_check($this->test_result_buffer->get_identifiers());
		$keys = array_keys($this->test_result_buffer->buffer_items);

		if($is_multi_way)
		{
			$key_sets = array();
			foreach($keys as $k)
			{
				$identifier_r = pts_strings::trim_explode(': ', $this->test_result_buffer->buffer_items[$k]->get_result_identifier());
				if(!isset($key_sets[$identifier_r[0]]))
				{
					$key_sets[$identifier_r[0]] = array();
				}

				$key_sets[$identifier_r[0]][] = $k;
			}
		}
		else
		{
			$key_sets = array($keys);
		}

		foreach($key_sets as $keys)
		{
			$prev_value = -1;
			$prev_id = -1;
			foreach($keys as $k)
			{
				$this_value = $this->test_result_buffer->buffer_items[$k]->get_result_value();
				$this_id = $this->test_result_buffer->buffer_items[$k]->get_result_identifier();
				if($prev_value != -1 && $prev_id != -1 && is_numeric($this_value))
				{
					$d = abs(($prev_value / $this_value) - 1);
					if($d > $threshold_level)
					{
						$points_of_interest[] = $this_id . ' - ' . $prev_id . ': ' . round(($d * 100), 2) . '%';
					}
				}
				$prev_value = $this_value;
				$prev_id = $this_id;
			}
		}

		if($adaptive && count($points_of_interest) > (count($key_sets) * (count($keys)) * 0.15))
		{
			// If too many results are being flagged, increase the threshold and run again
			if($threshold_level < 0.5)
			{
				return $this->points_of_possible_interest($threshold_level * 2, true);
			}
		}

		return $points_of_interest;
	}
	public function is_last_result_worse_than_prior($threshold_level = 0.05)
	{
		if($this->test_profile->get_display_format() != 'BAR_GRAPH') // BAR_ANALYZE_GRAPH is currently unsupported
		{
			return false;
		}

		$is_multi_way = pts_render::multi_way_identifier_check($this->test_result_buffer->get_identifiers());
		$keys = array_keys($this->test_result_buffer->buffer_items);

		if($is_multi_way)
		{
			$key_sets = array();
			foreach($keys as $k)
			{
				$identifier_r = pts_strings::trim_explode(': ', $this->test_result_buffer->buffer_items[$k]->get_result_identifier());
				if(!isset($key_sets[$identifier_r[0]]))
				{
					$key_sets[$identifier_r[0]] = array();
				}
				$key_sets[$identifier_r[0]][] = $k;
			}
		}
		else
		{
			$key_sets = array($keys);
		}

		foreach($key_sets as $keys)
		{
			$prev_value = -1;
			$prev_id = -1;
			foreach($keys as $k)
			{
				$first_value = pts_arrays::first_element($this->test_result_buffer->buffer_items)->get_result_value();
				$last_value = pts_arrays::last_element($this->test_result_buffer->buffer_items)->get_result_value();
				//echo 'first: ' . $first_value . ' last: ' . $last_value . '<br />';
				if(!is_numeric($first_value) || !is_numeric($last_value))
				{
					continue;
				}
				if($this->test_profile->get_result_proportion() == 'HIB')
				{
					if($last_value < $first_value * (1 - $threshold_level))
						return true;
				}
				else if($this->test_profile->get_result_proportion() == 'LIB')
				{
					if($last_value > $first_value * (1 + $threshold_level))
						return true;
				}
			}
		}

		return false;
	}
}

?>
