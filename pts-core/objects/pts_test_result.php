<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2013, Phoronix Media
	Copyright (C) 2008 - 2013, Michael Larabel

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
	private $result;
	private $used_arguments;
	private $used_arguments_description;

	public $test_profile;
	public $test_result_buffer;

	public $active_result = null;

	public function __construct(&$test_profile)
	{
		$this->test_profile = $test_profile;
		$this->result = 0;
	}
	public function __clone()
	{
		$this->test_profile = clone $this->test_profile;
	}
	public function set_test_result_buffer($test_result_buffer)
	{
		$this->test_result_buffer = $test_result_buffer;
	}
	public function set_used_arguments_description($arguments_description)
	{
		$this->used_arguments_description = $arguments_description;
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
	public function set_result($result)
	{
		$this->result = $result;
	}
	public function get_result()
	{
		return $this->result;
	}
	public function get_comparison_hash($show_version_and_attributes = true)
	{
		if($show_version_and_attributes)
		{
			$tp = $this->test_profile->get_identifier(true);
			// remove the last segment of the test profile version that should be in xx.yy.zz format
			// this removal is done since the zz segment should be maintainable between comparisons
			$tp = substr($tp, 0, strrpos($tp, '.'));

			return pts_test_profile::generate_comparison_hash($tp, $this->get_arguments(), $this->get_arguments_description(), $this->test_profile->get_app_version());
		}
		else
		{
			return pts_test_profile::generate_comparison_hash($this->test_profile->get_identifier(false), $this->get_arguments());
		}
	}
	public function __toString()
	{
		return $this->test_profile->get_identifier(false) . ' ' . $this->get_arguments() . ' ' . $this->get_arguments_description() . ' ' . $this->test_profile->get_override_values();
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

				array_push($key_sets[$identifier_r[0]], $k);
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
				foreach($keys as $k)
				{
					if($this->test_result_buffer->buffer_items[$k]->get_result_value() < $divide_value || $divide_value == -1)
					{
						$divide_value = $this->test_result_buffer->buffer_items[$k]->get_result_value();
					}
				}
			}

			foreach($keys as $k)
			{
				$normalized = pts_math::set_precision(($this->test_result_buffer->buffer_items[$k]->get_result_value() / $divide_value), 2);
				$this->test_result_buffer->buffer_items[$k]->reset_result_value($normalized);
				$this->test_result_buffer->buffer_items[$k]->reset_raw_value(0);
			}
		}

		$this->test_profile->set_result_proportion('HIB');
		$this->test_profile->set_result_scale('Relative Performance');
		return true;
	}
}

?>
