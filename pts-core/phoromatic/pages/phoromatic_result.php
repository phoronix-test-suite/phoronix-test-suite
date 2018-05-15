<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2016, Phoronix Media
	Copyright (C) 2008 - 2016, Michael Larabel

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
	protected static $schedule_id = false;

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

			foreach($upload_ids as $i => $upload_id)
			{
				if(isset($upload_id[5]) && substr($upload_id, 0, 2) == 'S:')
				{
					$t = explode(':', $upload_id);
					$stmt = phoromatic_server::$db->prepare('SELECT UploadID, UploadTime FROM phoromatic_results WHERE AccountID = :account_id AND ScheduleID = :schedule_id ORDER BY UploadTime DESC');
					$stmt->bindValue(':account_id', $_SESSION['AccountID']);
					$stmt->bindValue(':schedule_id', $t[1]);
					$test_result_result = $stmt->execute();
					$cutoff_time = is_numeric($t[2]) ? strtotime('today -' . $t[2] . ' days') : false;
					while($test_result_row = $test_result_result->fetchArray())
					{
						if($cutoff_time !== false && strtotime($test_result_row['UploadTime']) < $cutoff_time)
							break;

						$upload_ids[] = $test_result_row['UploadID'];
					}

					unset($upload_ids[$i]);
				}
			}
			$upload_ids = array_unique($upload_ids);

			$result_files = array();

			$display_rows = array();
			$system_types = array();
			$schedule_types = array();
			$trigger_types = array();
			$upload_times = array();
			$benchmark_tickets = array();
			$xml_result_hash = array();
			$tickets = array();

			foreach($upload_ids as $id)
			{
				$result_share_opt = phoromatic_server::read_setting('force_result_sharing') ? '1 = 1' : 'AccountID = (SELECT AccountID FROM phoromatic_account_settings WHERE LetOtherGroupsViewResults = "1" AND AccountID = phoromatic_results.AccountID)';
				$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_results WHERE PPRID = :pprid AND (AccountID = :account_id OR ' . $result_share_opt . ') LIMIT 1');
				$stmt->bindValue(':pprid', $id);
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$result = $stmt->execute();
				$row = $result->fetchArray();

				if(false && empty($row))
				{
					// TODO XXX
					// XXX this code is ultimately dead
					$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_results WHERE AccountID = :account_id AND UploadID = :upload_id LIMIT 1');
					$stmt->bindValue(':account_id', $_SESSION['AccountID']);
					$stmt->bindValue(':upload_id', $id);
					$result = $stmt->execute();
					$row = $result->fetchArray();
				}

				if(empty($row))
					continue;

				$composite_xml = phoromatic_server::phoromatic_account_result_path($row['AccountID'], $row['UploadID']) . 'composite.xml';
				if(!is_file($composite_xml))
				{
					echo 'File Not Found: ' . $composite_xml;
					return false;
				}
				$display_rows[$composite_xml] = $row;
				pts_arrays::unique_push($benchmark_tickets, $row['BenchmarkTicketID']);
				pts_arrays::unique_push($upload_times, $row['UploadTime']);
				pts_arrays::unique_push($xml_result_hash, $row['XmlUploadHash']);
				pts_arrays::unique_push($system_types, $row['SystemID']);
				pts_arrays::unique_push($schedule_types, $row['ScheduleID']);
				pts_arrays::unique_push($trigger_types, $row['Trigger']);
				pts_arrays::unique_push($tickets, $row['BenchmarkTicketID']);

				// Update view counter
				$stmt_view = phoromatic_server::$db->prepare('UPDATE phoromatic_results SET TimesViewed = (TimesViewed + 1) WHERE AccountID = :account_id AND UploadID = :upload_id');
				$stmt_view->bindValue(':account_id', $_SESSION['AccountID']);
				$stmt_view->bindValue(':upload_id', $row['UploadID']);
				$stmt_view->execute();
			}

			$result_file_title = null;
			if(count($system_types) == 1)
			{
				$result_file_title = phoromatic_system_id_to_name($system_types[0]) . ' Tests';
			}

			if(!empty($tickets) && $tickets[0] != null)
			{
				$system_name_format = 'ORIGINAL_DATA';
			}
			else if(count($trigger_types) == 1 && $trigger_types[0] != null && $benchmark_tickets[0] != null && count($display_rows) > 1)
			{
				$system_name_format = 'TRIGGER_AND_SYSTEM';
			}
			else if(empty($schedule_types[0]))
			{
				$system_name_format = 'ORIGINAL_DATA';
			}
			else if(count($display_rows) == 1)
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
			else if(false && count($trigger_types) == 1)
			{
				// TODO XXX: this approach yields garbage strings generally without refining the selector
				// i.e. first make sure all the schedules match or are comparable
				$system_name_format = 'SYSTEM_AND_SCHEDULE';
			}
			else
			{
				$system_name_format = null;
			}

			if(count($schedule_types) == 1 && $schedule_types[0] != 0)
			{
				self::$schedule_id = $schedule_types[0];
			}

			foreach($display_rows as $composite_xml => $row)
			{
				//  $row['SystemID'] . ' ' . $row['ScheduleID'] . ' ' . $row['Trigger']

				switch($system_name_format)
				{
					case 'ORIGINAL_DATA':
						$system_name = null;
						break;
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

				if($system_name == null)
				{
					$rf = new pts_result_file($composite_xml);
					$identifiers = $rf->get_system_identifiers();
					if(count($identifiers) == 1)
					{
						$system_name = $identifiers[0];
					}
				}

				$system_name = str_replace('.SYSTEM', phoromatic_system_id_to_name($row['SystemID']), $system_name);
				$system_name = str_replace('.GROUP', phoromatic_account_id_to_group_name($row['AccountID']), $system_name);
				$system_variables = explode(';', phoromatic_server::system_id_variables($row['SystemID'], $row['AccountID']));
				foreach($system_variables as $var)
				{
					$var = explode('=', $var);
					if(count($var) == 2)
					{
						$system_name = str_replace('.' . $var[0], $var[1], $system_name);
					}
				}


				$result_files[] = new pts_result_merge_select($composite_xml, null, $system_name);
			}

			$result_file = new pts_result_file(null, true);
			if(!empty($result_files))
			{
				$attributes = array('new_result_file_title' => $result_file_title);
				if(!empty($result_files))
				{
					$result_file->merge($result_files, $attributes);
				}
			}

			$extra_attributes = array();

			if(isset($_GET['upload_to_openbenchmarking']))
			{
				$ob_url = pts_openbenchmarking_client::upload_test_result($result_file, false);
				if($ob_url)
				{
					header('Location: ' . $ob_url);
				}
			}

			$attribute_options = array(
				'normalize_results' => 'normalize_result_buffer',
				'sort_by_performance' => 'sort_result_buffer_values',
				'sort_by_reverse' => 'reverse_result_buffer',
				'sort_by_name' => 'sort_result_buffer',
				'condense_comparison' => 'condense_multi_way',
				'force_line_graph' => 'force_tracking_line_graph',
				);
			$url_append = null;
			foreach($attribute_options as $web_var => $attr_var)
			{
				if(isset($_REQUEST[$web_var]))
				{
					$extra_attributes[$attr_var] = true;
					$url_append .= '&' . $web_var . '=1';
				}
			}

			if(isset($_POST['transpose_comparison']))
			{
				$result_file->invert_multi_way_invert();
			}

			$intent = null;

			if(isset($_GET['download']) && $_GET['download'] == 'csv')
			{
				$result_csv = pts_result_file_output::result_file_to_csv($result_file);
				header('Content-Description: File Transfer');
				header('Content-Type: application/csv');
				header('Content-Disposition: attachment; filename=phoromatic-result.csv');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . strlen($result_csv));
				ob_clean();
				flush();
				echo $result_csv;
				return;
			}
			else if(isset($_GET['download']) && $_GET['download'] == 'txt')
			{
				$result_txt = pts_result_file_output::result_file_to_text($result_file);
				header('Content-Description: File Transfer');
				header('Content-Type: application/txt');
				header('Content-Disposition: attachment; filename=phoromatic-result.txt');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . strlen($result_txt));
				ob_clean();
				flush();
				echo $result_txt;
				return;
			}
			else if(isset($_GET['download']) && $_GET['download'] == 'pdf')
			{
				// TODO XXX: make use of pts_result_file_output::result_file_to_pdf()
				$pdf_output = pts_result_file_output::result_file_to_pdf($result_file, 'phoromatic.pdf', 'I', $extra_attributes);
				return;
			}
			else if(isset($_GET['download']) && $_GET['download'] == 'xml')
			{
				echo $result_file->get_xml();
				return;
			}

			if(count($result_files) > 1)
			{
				$main .= '<h1>Phoromatic Comparison</h1>';
			}
			else
			{
				$main .= '<h1>' . ($result_file->get_title() != null ? $result_file->get_title() : 'Phoromatic Results') . '</h1>';
				$main .= '<p>' . $result_file->get_description() . '</p>';

				$main .= '<p><strong>Uploaded On:</strong> ' . $upload_times[0] . '</p>';
			}
			$main .= phoromatic_annotate_entry('RESULT', implode(',', $upload_ids), 'TOP');

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

			$main .= '<div id="pts_results_area">';
			foreach($result_file->get_result_objects((isset($_POST['show_only_changed_results']) ? 'ONLY_CHANGED_RESULTS' : -1)) as $i => $result_object)
			{
				$main .= '<h2><a name="r-' . $i . '"></a><a name="' . $result_object->get_comparison_hash(true, false) . '"></a>' . $result_object->test_profile->get_title() . '</h2>';
				$main .= phoromatic_annotate_entry('RESULT', implode(',', $upload_ids), $result_object->get_comparison_hash(true, false));
				$main .= '<p class="result_object">';
				$main .= pts_render::render_graph_inline_embed($result_object, $result_file, $extra_attributes);
				$main .= '</p>';
			}
			$main .= '</div>';
		}
		else
		{
			// No result
		}

		$right = null;
		if(self::$schedule_id && !empty(self::$schedule_id))
		{
			$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_schedules WHERE AccountID = :account_id AND ScheduleID = :schedule_id LIMIT 1');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$stmt->bindValue(':schedule_id', self::$schedule_id);
			$result = $stmt->execute();
			$row = $result->fetchArray();

			if(!empty($row))
			{
				$right .= '<h3><a href="?schedules/' . $row['ScheduleID'] . '">' . $row['Title'] . '</a></h3>';

				if(!empty($row['ActiveOn']))
				{

					$right .= '<p align="center"><strong>' . phoromatic_schedule_activeon_string($row['ActiveOn'], $row['RunAt']) . '</strong></p>';
				}

				$right .= '<p>Compare this result file to the latest results from the past: ';
				$right .= '<select name="view_results_from_past" id="view_results_from_past" onchange="phoromatic_jump_to_results_from(\'' . $row['ScheduleID'] . '\', \'view_results_from_past\', \'' . $PATH[0] . ',\');">';
				$oldest_upload_time = strtotime(phoromatic_oldest_result_for_schedule(self::$schedule_id));
				$opts = array(
					'Week' => 7,
					'Three Weeks' => 21,
					'Month' => 30,
					'Quarter' => 90,
					'Six Months' => 180,
					'Year' => 365,
					);
				foreach($opts as $str_name => $time_offset)
				{
					if($oldest_upload_time > (time() - (86400 * $time_offset)))
						break;
					$right .= '<option value="' . $time_offset . '">' . $str_name . '</option>';
				}
				$right .= '<option value="all">All Results</option>';
				$right .= '</select>';
				$right .= '</p>';
			}
		}
		if(true)
		{
			$compare_results = array();
			$hash_matches = 0;
			$ticket_matches = 0;

			$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_results WHERE AccountID = :account_id AND ComparisonHash = :comparison_hash AND PPRID NOT IN (:pprid) ORDER BY UploadTime DESC LIMIT 12');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$stmt->bindValue(':comparison_hash', $result_file->get_contained_tests_hash(false));
			$stmt->bindValue(':pprid', implode(',', $upload_ids));
			$result = $stmt->execute();
			while($row = $result->fetchArray())
			{
				$compare_results[$row['PPRID']] = $row;
				$hash_matches++;
			}

			foreach($benchmark_tickets as $ticket_id)
			{
				$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_results WHERE AccountID = :account_id AND BenchmarkTicketID = :ticket_id AND PPRID NOT IN (:pprid) ORDER BY UploadTime DESC LIMIT 12');
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$stmt->bindValue(':ticket_id', $ticket_id);
				$stmt->bindValue(':pprid', implode(',', $upload_ids));
				$result = $stmt->execute();

				while($row = $result->fetchArray())
				{
					$compare_results[$row['PPRID']] = $row;
					$ticket_matches++;
				}
			}

			if(!empty($compare_results))
			{
				$right .= '<hr /><h3>Compare Results</h3><form name="compare_similar_results" onsubmit="return false;">
						<input type="hidden" value="' . implode(',', $upload_ids) . '" id="compare_similar_results_this" />';

				foreach($compare_results as &$row)
				{
					$right .= '<p><input type="checkbox" value="' . $row['PPRID'] . '" name="compare_results" /> ' . $row['Title'] . '<br /><em>' . phoromatic_system_id_to_name($row['SystemID'], $row['AccountID']) . '</em></p>';
				}

				$right .= '<p><input type="submit" value="Compare Results" id="compare_results_submit" onclick="javascript:phoromatic_do_custom_compare_results(this); return false;" /></p></form>';

				if($ticket_matches > 3)
				{
					$right .= '<p><a href="/results/ticket/' . $ticket_id . '">Find All Matching Results</a>';
				}
				else if($hash_matches > 3)
				{
					$right .= '<p><a href="/results/hash/' . $result_file->get_contained_tests_hash(false) . '">Find All Matching Results</a>';
				}
			}
		}

		if(count($upload_ids) > 1)
		{
			$checkbox_options = array(
				'normalize_results' => 'Normalize Results',
				'sort_by_performance' => 'Sort Results By Performance',
				'sort_by_name' => 'Reverse Result By Identifier',
				'sort_by_reverse' => 'Reverse Result Order',
				'show_only_changed_results' => 'Show Only Results With Result Variation',
				'force_line_graph' => 'Force Line Graph',
				);

			if($result_file->is_multi_way_comparison())
			{
				$checkbox_options['condense_comparison'] = 'Condense Comparison';
				$checkbox_options['transpose_comparison'] = 'Transpose Comparison';
			}

			$right .= '<form action="' . $_SERVER['REQUEST_URI'] . '" name="update_result_view" method="post"><hr /><h3>Result Analysis Options</h3><p align="left">' . PHP_EOL;
			foreach($checkbox_options as $val => $name)
			{
				$right .= '<input type="checkbox" name="' . $val . '" value="1" ' . (isset($_POST[$val]) ? 'checked="checked" ' : null) . '/> ' . $name . '<br />';
			}
			$right .= '<br /><input type="submit" value="Refresh Results"></p></form>';
		}

		if(self::$schedule_id && !empty(self::$schedule_id) && $system_types[0] && $trigger_types[0])
		{
			$stmt = phoromatic_server::$db->prepare('SELECT UserContextStep FROM phoromatic_system_context_logs WHERE AccountID = :account_id AND ScheduleID = :schedule_id AND SystemID = :system_id AND TriggerID = :trigger_id');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$stmt->bindValue(':system_id', $system_types[0]);
			$stmt->bindValue(':schedule_id', self::$schedule_id);
			$stmt->bindValue(':trigger_id', $trigger_types[0]);
			$result = $stmt->execute();
			if($row = $result->fetchArray())
			{
				$right .= '<hr /><h3>User Context Logs</h3>';
				do
				{
					$right .= '<p><a href="?logs/context/' . $system_types[0] . ',' . self::$schedule_id . ',' . base64_encode($trigger_types[0]) . '">' . $row['UserContextStep'] . '</a></p>';
				}
				while($row = $result->fetchArray());
			}
		}

		$right .= '<hr /><h3>Result Export</h3>';
		$right .= '<p><a href="/public.php?t=result&ut='  . implode(',', $upload_ids) . $url_append . '">Public Viewer</a></p>';
		$right .= '<p><a href="?' . $_SERVER['QUERY_STRING'] . '/&download=pdf' . $url_append . '">Download As PDF</a></p>';
		$right .= '<p><a href="?' . $_SERVER['QUERY_STRING'] . '/&download=csv">Download As CSV</a></p>';
		$right .= '<p><a href="?' . $_SERVER['QUERY_STRING'] . '/&download=xml">Download As XML</a></p>';
		$right .= '<p><a href="?' . $_SERVER['QUERY_STRING'] . '/&download=txt">Download As TEXT</a></p>';
		$right .= '<p><a href="?' . $_SERVER['QUERY_STRING'] . '/&upload_to_openbenchmarking">Upload To OpenBenchmarking.org</a></p>';

		if(is_file(phoromatic_server::phoromatic_account_result_path($_SESSION['AccountID'], $row['UploadID']) . 'system-logs.zip'))
		{
				$right .= '<hr /><p><a href="?logs/system/' . $row['UploadID'] . '">View System Logs</a></p>';
		}

		echo phoromatic_webui_header_logged_in();
		echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in($right));
		echo phoromatic_webui_footer();
	}
}

?>
