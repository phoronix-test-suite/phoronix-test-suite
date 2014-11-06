<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2014, Phoronix Media
	Copyright (C) 2008 - 2014, Michael Larabel

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


class phoromatic_result implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Result';
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
		$main = null;
		if(isset($PATH[0]))
		{
			$upload_ids = explode(',', $PATH[0]);
			$result_file = array();

			$display_rows = array();
			$system_types = array();
			$schedule_types = array();
			$trigger_types = array();

			foreach($upload_ids as $upload_id)
			{
				$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_results WHERE AccountID = :account_id AND UploadID = :upload_id LIMIT 1');
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$stmt->bindValue(':upload_id', $upload_id);
				$result = $stmt->execute();
				$row = $result->fetchArray();

				$composite_xml = phoromatic_server::phoromatic_account_result_path($_SESSION['AccountID'], $upload_id) . 'composite.xml';
				if(!is_file($composite_xml))
				{
					echo 'File Not Found: ' . $composite_xml;
					return false;
				}
				$display_rows[$composite_xml] = $row;
				pts_arrays::unique_push($system_types, $row['SystemID']);
				pts_arrays::unique_push($schedule_types, $row['ScheduleID']);
				pts_arrays::unique_push($trigger_types, $row['Trigger']);
			}

			$result_file_title = null;
			if(count($system_types) == 1)
			{
				$result_file_title = phoromatic_system_id_to_name($system_types[0]) . ' Tests';
			}

			if(count($display_rows) == 1)
			{
				$system_name_format = 'SYSTEM_NAME';
			}
			else if(count($schedule_types) == 1 && count($system_types) == 1)
			{
				$system_name_format = 'TRIGGER';
				$result_file_title = phoromatic_schedule_id_to_name($schedule_types[0]);
			}
			else if(count($schedule_types) == 1)
			{
				$system_name_format = 'TRIGGER_AND_SYSTEM';
			}
			else if(count($trigger_types) == 1)
			{
				$system_name_format = 'SYSTEM_AND_SCHEDULE';
			}
			else
			{
				$system_name_format = null;
			}

			foreach($display_rows as $composite_xml => $row)
			{
				//  $row['SystemID'] . ' ' . $row['ScheduleID'] . ' ' . $row['Trigger']

				switch($system_name_format)
				{
					case 'SYSTEM_NAME':
						$system_name = phoromatic_system_id_to_name($row['SystemID']);
						break;
					case 'TRIGGER':
						$system_name = $row['Trigger'];
						break;
					case 'TRIGGER_AND_SYSTEM':
						$system_name = phoromatic_system_id_to_name($row['SystemID']) . ': ' . $row['Trigger'];
						break;
					case 'SYSTEM_AND_SCHEDULE':
						$system_name = phoromatic_schedule_id_to_name($row['ScheduleID']) . ': ' . $row['Trigger'];
						break;
					default:
						$system_name = phoromatic_system_id_to_name($row['SystemID']) . ' - ' . phoromatic_schedule_id_to_name($row['ScheduleID']) . ' - ' . $row['Trigger'];
				}


				array_push($result_file, new pts_result_merge_select($composite_xml, null, $system_name));
			}

			$writer = new pts_result_file_writer(null);
			$attributes = array('new_result_file_title' => $result_file_title);
			pts_merge::merge_test_results_process($writer, $result_file, $attributes);
			$result_file = new pts_result_file($writer->get_xml());
			$extra_attributes = array();

			$attribute_options = array(
				'normalize_results' => 'normalize_result_buffer',
				'sort_by_performance' => 'sort_result_buffer_values',
				'sort_by_reverse' => 'reverse_result_buffer',
				'sort_by_name' => 'sort_result_buffer',
				'condense_comparison' => 'condense_multi_way',
				);
			foreach($attribute_options as $web_var => $attr_var)
			{
				if(isset($_POST[$web_var]))
				{
					$extra_attributes[$attr_var] = true;
				}
			}

			if(isset($_POST['transpose_comparison']))
			{
				$result_file->invert_multi_way_invert();
			}

			$intent = null;

			$main .= '<h1>' . $result_file->get_title() . '</h1>';

			if($result_file->get_system_count() == 1 || ($intent = pts_result_file_analyzer::analyze_result_file_intent($result_file, $intent, true)))
			{
				$table = new pts_ResultFileCompactSystemsTable($result_file, $intent);
			}
			else
			{
				$table = new pts_ResultFileSystemsTable($result_file);
			}

			$main .= '<p style="text-align: center; overflow: auto;" class="result_object">' . pts_render::render_graph_inline_embed($table, $result_file, $extra_attributes) . '</p>';

			$table = new pts_ResultFileTable($result_file, $intent);
			$main .= '<p style="text-align: center; overflow: auto;" class="result_object">' . pts_render::render_graph_inline_embed($table, $result_file, $extra_attributes) . '</p>';

			foreach($result_file->get_result_objects() as $i => $result_object)
			{
				$main .= '<h2><a name="r-' . $i . '"></a>' . $result_object->test_profile->get_title() . '</h2>';
				$main .= '<p class="result_object">';
				$main .= pts_render::render_graph_inline_embed($result_object, $result_file, $extra_attributes);
				$main .= '</p>';
			}
		}
		else
		{
			// No result
		}

		$checkbox_options = array(
			'normalize_results' => 'Normalize Results',
			'sort_by_performance' => 'Sort Results By Performance',
			'sort_by_name' => 'Reverse Result By Identifier',
			'sort_by_reverse' => 'Reverse Result Order',
			);

		if($result_file->is_multi_way_comparison())
		{
			$checkbox_options['condense_comparison'] = 'Condense Comparison';
			$checkbox_options['transpose_comparison'] = 'Transpose Comparison';
		}

		$right = '<form action="' . $_SERVER['REQUEST_URI'] . '" name="update_result_view" method="post"><ul><li><h3>Result Analysis Options</h3></li>' . PHP_EOL;
		foreach($checkbox_options as $val => $name)
		{
			$right .= '<li><input type="checkbox" name="' . $val . '" value="1" ' . (isset($_POST[$val]) ? 'checked="checked" ' : null) . '/> ' . $name . '</li>';
		}
		$right .= '<li><input type="submit" name="Submit" /></li></ul></form>';

		echo phoromatic_webui_header_logged_in();
		echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in($right));
		echo phoromatic_webui_footer();
	}
}

?>
