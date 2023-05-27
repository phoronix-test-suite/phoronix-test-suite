<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2022, Phoronix Media
	Copyright (C) 2008 - 2022, Michael Larabel

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

			foreach($upload_ids as $i => &$upload_id)
			{
				if(($x = strpos($upload_id, '&')) !== false)
				{
					$upload_id = substr($upload_id, 0, $x);
				}
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
			$benchmark_tickets = array();
			$tickets = array();
			$showed_progress_msg = false;

			foreach($upload_ids as $id)
			{
				$result_share_opt = phoromatic_server::read_setting('force_result_sharing') ? '1 = 1' : 'AccountID = (SELECT AccountID FROM phoromatic_account_settings WHERE LetOtherGroupsViewResults = "1" AND AccountID = phoromatic_results.AccountID)';
				$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_results WHERE PPRID = :pprid AND (AccountID = :account_id OR ' . $result_share_opt . ') LIMIT 1');
				$stmt->bindValue(':pprid', $id);
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$result = $stmt->execute();
				$row = $result->fetchArray();

				if(empty($row))
					continue;

				$composite_xml = phoromatic_server::phoromatic_account_result_path($row['AccountID'], $row['UploadID']) . 'composite.xml';
				if(!is_file($composite_xml))
				{
					echo 'File Not Found: ' . $composite_xml;
					return false;
				}
				$has_system_logs = is_file(phoromatic_server::phoromatic_account_result_path($_SESSION['AccountID'], $row['UploadID']) . 'system-logs.zip') ? $row['UploadID'] : false;
				$display_rows[$composite_xml] = $row;
				pts_arrays::unique_push($benchmark_tickets, $row['BenchmarkTicketID']);
				pts_arrays::unique_push($system_types, $row['SystemID']);
				pts_arrays::unique_push($schedule_types, $row['ScheduleID']);
				pts_arrays::unique_push($trigger_types, $row['Trigger']);
				pts_arrays::unique_push($tickets, $row['BenchmarkTicketID']);

				if($row['InProgress'] > 0 && !$showed_progress_msg)
				{
					$showed_progress_msg = true;
					$main .= '<p align="center"><strong style="color: red;">The result file being shown is still undergoing testing, results being shown for completed results.</strong></p>';
				}

				// Update view counter
				$stmt_view = phoromatic_server::$db->prepare('UPDATE phoromatic_results SET TimesViewed = (TimesViewed + 1) WHERE AccountID = :account_id AND UploadID = :upload_id');
				$stmt_view->bindValue(':account_id', $_SESSION['AccountID']);
				$stmt_view->bindValue(':upload_id', $row['UploadID']);
				$stmt_view->execute();
			}

			$result_file_title = null;
			if(count($system_types) == 1)
			{
				$result_file_title = phoromatic_server::system_id_to_name($system_types[0]) . ' Tests';
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
				$result_file_title = phoromatic_server::schedule_id_to_name($schedule_types[0]);
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

			if(count($display_rows) == 1)
			{
				// Rather than going through the merge logic and all that, when just one result file, present as is
				$result_file = new pts_result_file(array_pop(array_keys($display_rows)), true);
				$identifiers = $result_file->get_system_identifiers();
				if(count($identifiers) == 1)
				{
					$system_name = $identifiers[0];

					if(strpos($system_name, '.') !== false)
					{
						$osn = $system_name;
						if(($replacement = phoromatic_server::system_id_to_name($row['SystemID'])) != null)
						{
							$system_name = str_replace('.SYSTEM', $replacement, $system_name);
						}
						if(($replacement = phoromatic_server::account_id_to_group_name($row['AccountID'])) != null)
						{
							$system_name = str_replace('.GROUP', $replacement, $system_name);
						}
						foreach(explode(';', phoromatic_server::system_id_variables($row['SystemID'], $row['AccountID'])) as $var)
						{
							$var = explode('=', $var);
							if(count($var) == 2)
							{
								$system_name = str_replace('.' . $var[0], $var[1], $system_name);
							}
						}
						if($osn != $system_name)
						{
							$result_file->rename_run(null, $system_name);
						}
					}
				}
			}
			else
			{
				foreach($display_rows as $composite_xml => $row)
				{
					switch($system_name_format)
					{
						case 'ORIGINAL_DATA':
							$system_name = null;
							break;
						case 'SYSTEM_NAME':
							$system_name = phoromatic_server::system_id_to_name($row['SystemID']);
							break;
						case 'TRIGGER':
							$system_name = $row['Trigger'];
							break;
						case 'TRIGGER_AND_SYSTEM':
							$system_name = phoromatic_server::system_id_to_name($row['SystemID']) . ': ' . $row['Trigger'];
							break;
						case 'SYSTEM_AND_SCHEDULE':
							$system_name = phoromatic_server::schedule_id_to_name($row['ScheduleID']) . ': ' . $row['Trigger'];
							break;
						default:
							$system_name = phoromatic_server::system_id_to_name($row['SystemID']) . ' - ' . phoromatic_server::schedule_id_to_name($row['ScheduleID']) . ' - ' . $row['Trigger'];
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

					if(($replacement = phoromatic_server::system_id_to_name($row['SystemID'])) != null)
					{
						$system_name = str_replace('.SYSTEM', $replacement, $system_name);
					}
					if(($replacement = phoromatic_server::account_id_to_group_name($row['AccountID'])) != null)
					{
						$system_name = str_replace('.GROUP', $replacement, $system_name);
					}
					$system_vars = phoromatic_server::system_id_variables($row['SystemID'], $row['AccountID']);
					if(!empty($system_vars))
					{
						foreach(explode(';', $system_vars) as $var)
						{
							$var = explode('=', $var);
							if(count($var) == 2)
							{
								$system_name = str_replace('.' . $var[0], $var[1], $system_name);
							}
						}
					}

					$rf = new pts_result_file($composite_xml);
					$rf->rename_run(null, $system_name);
					$result_files[] = $rf;
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
			}

			$embed = new pts_result_viewer_embed($result_file);
			$embed->allow_modifying_results(!PHOROMATIC_USER_IS_VIEWER);
			$embed->allow_deleting_results(!PHOROMATIC_USER_IS_VIEWER);
			$embed->show_html_result_table(false);
			$embed->show_test_metadata_helper(false);
			$embed->include_page_print_only_helpers(false);
			$main .= $embed->get_html();
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
					$right .= '<p><input type="checkbox" value="' . $row['PPRID'] . '" name="compare_results" /> ' . $row['Title'] . '<br /><em>' . phoromatic_server::system_id_to_name($row['SystemID'], $row['AccountID']) . '</em></p>';
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
		$right .= '<p><a href="/public.php?t=result&ut='  . implode(',', $upload_ids) . '">Public Viewer</a></p>';

		if($has_system_logs)
		{
		//		$right .= '<hr /><p><a href="?logs/system/' . $has_system_logs . '">View System Logs</a></p>';
		}

		echo phoromatic_webui_header_logged_in();
		echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in($right));
		echo phoromatic_webui_footer();
	}
}

?>
