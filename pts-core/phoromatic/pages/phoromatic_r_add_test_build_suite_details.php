<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2014 - 2022, Phoronix Media
	Copyright (C) 2014 - 2022, Michael Larabel

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

class phoromatic_r_add_test_build_suite_details implements pts_webui_interface
{
	public static function page_title()
	{
		return '';
	}
	public static function page_header()
	{
		return null;
	}
	public static function preload($PAGE)
	{
		return true;
	}
	public static function render_page_process($PATH)
	{
		phoromatic_quit_if_invalid_input_found(array('tp'));
		$test_profile = new pts_test_profile($_GET['tp']);
		$name = $test_profile->get_title();
		$description = $test_profile->get_description();

		$tid = 't_' . rand(1, 20000);
		echo '<div id="' . $tid . '">';
		echo '<p align="right"><a onclick="javascript:phoromatic_remove_from_suite_list(\'' . $tid  . '\');">Remove Test</a></p>';
		echo '<h2>' . $name . ' [' . $test_profile->get_identifier() . '] </h2>';
		echo '<p><em>' . $description . '</em></p>';
		if(!empty($supported_os = $test_profile->get_supported_platforms()))
		{
			echo '<p>This test is supported on <strong>' . implode(', ', $supported_os) . '</strong>.</p>';
		}
		echo '<p>More information on this test can be found via <a href="?tests/' . $test_profile->get_identifier() . '">the test profile page</a> or <a target="_blank" href="http://openbenchmarking.org/test/' . $test_profile->get_identifier() . '">OpenBenchmarking.org</a>.</p>';

		$test_options = $test_profile->get_test_option_objects();

		echo '<input type="hidden" name="test_add[]" value="' . $test_profile->get_identifier() . '" />';
		$test_prefix = "test_option_" . str_replace('.', '-', microtime(true)) . "_";
		echo '<input type="hidden" name="test_prefix[]" value="' . $test_prefix . '" />';

		if(count($test_options) == 0)
		{
			echo '<p><strong>No configurable user options for this test.</strong></p>';
		}
		else
		{
			for($i = 0; $i < count($test_options); $i++)
			{
				$o = $test_options[$i];
				$option_count = $o->option_count();

				echo '<p id="' . $test_prefix . $o->get_identifier() . '_name">' . $o->get_name() . '</p>';

				if($option_count == 0)
				{
					echo '<p><input type="text" name="' . $test_prefix . $o->get_identifier() . '" id="' . $test_prefix . $o->get_identifier() . '" /></p>';
				}
				else
				{
					echo '<p><select name="' . $test_prefix . $o->get_identifier() . '" id="' . $test_prefix . $o->get_identifier() . '" onChange="phoromatic_test_select_update_selected_name(this);" onload="phoromatic_test_select_update_selected_name(this);">';

					$opts = array();
					$selected_index = 0;
					for($j = 0; $j < $option_count; $j++)
					{
						$v = $o->format_option_value_from_input($o->get_option_value($j));
						$selected = isset($_GET['tpa']) && strpos($_GET['tpa'], $o->get_option_name($j)) !== false;
						if($selected)
						{
							$selected_index = $j;
						}
						echo '<option value="' . $v . '" ' . ($selected ? 'selected="selected"' : null) . '>' . $o->get_option_name($j) . '</option>';
						$opts[] = $o->get_name() . ': ' . $o->get_option_name($j) . '::' . $v;
					}
					if($j > 1)
					{
						echo '<option value="' . implode('||', $opts) . '">Test All Options</option>';
					}

					echo '</select></p>';
					echo '<input name="' . $test_prefix . $o->get_identifier() . '_selected" id="' . $test_prefix . $o->get_identifier() . '_selected" type="hidden" value="' . $o->get_name() . ': ' . $o->get_option_name($selected_index) . '" />';
				}
			}
		}
		echo '<hr />';
		echo '</div>';
	}
}

?>
