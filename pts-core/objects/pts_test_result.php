<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2017, Phoronix Media
	Copyright (C) 2008 - 2017, Michael Larabel

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

	public $test_profile;
	public $test_result_buffer;

	public $active = null;
	public $generated_result_buffers = null;

	// Added to make it easy to have PTS modules run a custom binary prior to running a program for the test
	public $exec_binary_prepend = null;
	public $test_result_standard_output = null;

	public function __construct($test_profile)
	{
		$this->test_profile = clone $test_profile;
		$this->result = 0;
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
	public function append_to_arguments_description($arguments_description)
	{
		if(strpos(' ' . $this->used_arguments_description . ' ', ' ' . $arguments_description . ' ') === false)
		{
			$this->used_arguments_description .= ($this->used_arguments_description != null ? ' ' : null) . $arguments_description;
		}
	}
	public function set_result_precision($precision = 2)
	{
		$this->result_precision = $precision;
	}
	public function get_result_precision()
	{
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

			return pts_test_profile::generate_comparison_hash($tp, $this->get_arguments(), null, null, $raw_output);
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
	public function get_result_first()
	{
		// a.k.a. the result winner
		$winner = null;

		if($this->test_profile->get_result_proportion() == 'LIB')
		{
			$winner = $this->test_result_buffer->get_min_value(true);
		}
		else if($this->test_profile->get_result_proportion() == 'HIB')
		{
			$winner = $this->test_result_buffer->get_max_value(true);
		}

		return $winner;
	}
	public function get_result_last()
	{
		// a.k.a. the result loser
		$winner = null;

		if($this->test_profile->get_result_proportion() == 'HIB')
		{
			$winner = $this->test_result_buffer->get_min_value(true);
		}
		else if($this->test_profile->get_result_proportion() == 'LIB')
		{
			$winner = $this->test_result_buffer->get_max_value(true);
		}

		return $winner;
	}
	public function normalize_buffer_values($normalize_against = false)
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
			if($this->test_profile->get_result_proportion() == 'LIB')
			{
				// Invert values for LIB
				foreach($keys as $k)
				{
					$this->test_result_buffer->buffer_items[$k]->reset_result_value((1 / $this->test_result_buffer->buffer_items[$k]->get_result_value()));
				}
			}

			$divide_value = -1;
			if($normalize_against != false)
			{
				foreach($keys as $k)
				{
					if($is_multi_way && strpos($this->test_result_buffer->buffer_items[$k]->get_result_identifier(), ': ' . $normalize_against) !== false)
					{
						// This allows it to just normalize against part of the string
						$divide_value = $this->test_result_buffer->buffer_items[$k]->get_result_value();
						break;
					}
					else if($this->test_result_buffer->buffer_items[$k]->get_result_identifier() == $normalize_against)
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
						if($this->test_result_buffer->buffer_items[$k]->get_result_value() > $divide_value)
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

			if($divide_value != 0)
			{
				foreach($keys as $k)
				{
					$normalized = pts_math::set_precision(($this->test_result_buffer->buffer_items[$k]->get_result_value() / $divide_value), max(3, $this->result_precision));
					$this->test_result_buffer->buffer_items[$k]->reset_result_value($normalized);
					$this->test_result_buffer->buffer_items[$k]->reset_raw_value(0);
				}
			}
		}

		$this->test_profile->set_result_proportion('HIB');
		$this->test_profile->set_result_scale('Relative Performance');
		return true;
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
		return true;
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
				$raw = $this->test_result_buffer->buffer_items[$k]->get_result_raw();
				if(!empty($raw))
				{
					$raw = pts_math::standard_error(pts_strings::colon_explode($raw));
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
		return true;
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
				if($prev_value != -1 && $prev_id != -1)
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
}

?>
