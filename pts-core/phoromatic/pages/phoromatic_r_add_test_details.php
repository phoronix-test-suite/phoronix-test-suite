<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2014 - 2021, Phoronix Media
	Copyright (C) 2014 - 2021, Michael Larabel

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


class phoromatic_r_add_test_details implements pts_webui_interface
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

		echo '<h2>' . $name . ' [' . $test_profile->get_identifier() . '] </h2>';
		echo '<p><em>' . $description . '</em></p>';
		if(!empty($supported_os = $test_profile->get_supported_platforms()))
		{
			echo '<p>This test is supported on <strong>' . implode(', ', $supported_os) . '</strong>.</p>';
		}
		echo '<p>More information on this test can be found via <a href="?tests/' . $test_profile->get_identifier() . '">the test profile page</a> or <a target="_blank" href="http://openbenchmarking.org/test/' . $test_profile->get_identifier() . '">OpenBenchmarking.org</a>.</p>';

		$test_options = $test_profile->get_test_option_objects();

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

				echo '<input type="hidden" name="test_add[]" value="' . $test_profile->get_identifier() . '" />';

				if(isset($_GET['build_suite']))
				{
					$test_prefix = "test_option_" . microtime() . "_";
					echo '<input type="hidden" name="test_prefix[]" value="' . $test_prefix . '" />';
				}
				else
					$test_prefix = "test_option_" . $_GET['tp'] . "_";

				echo '<p id="' . $test_prefix . $o->get_identifier() . '_name">' . $o->get_name() . '</p>';

				if($option_count == 0)
				{
					echo '<input name="' . $test_prefix . $o->get_identifier() . '_selected" id="' . $test_prefix . $o->get_identifier() . '_selected" type="hidden" value="" />';
					echo '<p><input type="text" name="' . $test_prefix . $o->get_identifier() . '" id="' . $test_prefix . $o->get_identifier() . '"  onChange="phoromatic_test_select_update_selected_name_custom_input(this);" /></p>';
				}
				else
				{
					echo '<input name="' . $test_prefix . $o->get_identifier() . '_selected" id="' . $test_prefix . $o->get_identifier() . '_selected" type="hidden" value="' . $o->get_name() . ': ' . $o->get_option_name(0) . '" />';
					echo '<p><select name="' . $test_prefix . $o->get_identifier() . '" id="' . $test_prefix . $o->get_identifier() . '" onChange="phoromatic_test_select_update_selected_name(this);">';

					for($j = 0; $j < $option_count; $j++)
					{
						echo '<option value="' . $o->format_option_value_from_input($o->get_option_value($j)) . '">' . $o->get_option_name($j) . '</option>';
					}

					echo '</select></p>';
				}
			}
		}
		echo '<br /><br /><p><input name="submit" value="Add" type="submit" onclick="" /></p>';
	}
}

?>
