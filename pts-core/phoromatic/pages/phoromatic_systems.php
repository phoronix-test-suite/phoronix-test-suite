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


class phoromatic_systems implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Systems';
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
		echo phoromatic_webui_header_logged_in();
		$main = null;

		if(!PHOROMATIC_USER_IS_VIEWER && !empty($PATH[0]) && isset($_POST['system_title']) && !empty($_POST['system_title']) && isset($_POST['system_description']) && isset($_POST['system_state']))
		{
			phoromatic_quit_if_invalid_input_found(array('system_title', 'system_description', 'system_state'));
			$stmt = phoromatic_server::$db->prepare('UPDATE phoromatic_systems SET Title = :title, Description = :description, State = :state, CurrentTask = \'Awaiting Task\', BlockPowerOffs = :block_power_offs WHERE AccountID = :account_id AND SystemID = :system_id');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$stmt->bindValue(':system_id', $PATH[0]);
			$stmt->bindValue(':title', pts_strings::simple($_POST['system_title']));
			$stmt->bindValue(':description', pts_strings::sanitize($_POST['system_description']));
			$stmt->bindValue(':state', pts_strings::simple($_POST['system_state']));
			$stmt->bindValue(':block_power_offs', $_POST['block_power_offs']);
			$stmt->execute();
		}
		if(!PHOROMATIC_USER_IS_VIEWER && !empty($PATH[0]) && isset($_POST['maintenance_mode']) && verify_submission_token())
		{
			$stmt = phoromatic_server::$db->prepare('UPDATE phoromatic_systems SET MaintenanceMode = :maintenance_mode WHERE AccountID = :account_id AND SystemID = :system_id');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$stmt->bindValue(':system_id', $PATH[0]);
			$stmt->bindValue(':maintenance_mode', pts_strings::simple($_POST['maintenance_mode']));
			$stmt->execute();
		}
		if(!PHOROMATIC_USER_IS_VIEWER && !empty($PATH[0]) && isset($_GET['clear_system_warnings']))
		{
			$stmt = phoromatic_server::$db->prepare('DELETE FROM phoromatic_system_client_errors WHERE AccountID = :account_id AND SystemID = :system_id');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$stmt->bindValue(':system_id', $PATH[0]);
			$stmt->execute();
		}
		if(!PHOROMATIC_USER_IS_VIEWER && !empty($PATH[0]) && isset($_POST['tick_thread_reboot']) && verify_submission_token())
		{
			$stmt = phoromatic_server::$db->prepare('UPDATE phoromatic_systems SET TickThreadEvent = :event WHERE AccountID = :account_id AND SystemID = :system_id');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$stmt->bindValue(':system_id', $PATH[0]);
			$stmt->bindValue(':event', time() . ':reboot');
			$stmt->execute();
		}
		if(!PHOROMATIC_USER_IS_VIEWER && !empty($PATH[0]) && isset($_POST['tick_thread_halt']) && verify_submission_token())
		{
			$stmt = phoromatic_server::$db->prepare('UPDATE phoromatic_systems SET TickThreadEvent = :event WHERE AccountID = :account_id AND SystemID = :system_id');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$stmt->bindValue(':system_id', $PATH[0]);
			$stmt->bindValue(':event', time() . ':halt-testing');
			$stmt->execute();
		}
		if(!PHOROMATIC_USER_IS_VIEWER && !empty($PATH[0]) && isset($_GET['really_delete_system']))
		{
			$stmt = phoromatic_server::$db->prepare('DELETE FROM phoromatic_systems WHERE AccountID = :account_id AND SystemID = :system_id LIMIT 1');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$stmt->bindValue(':system_id', $PATH[0]);
			$stmt->execute();
		}
		if(!PHOROMATIC_USER_IS_VIEWER && !empty($PATH[0]) && isset($_POST['system_var_names'])&& isset($_POST['system_var_values']))
		{
			phoromatic_quit_if_invalid_input_found(array('system_var_names', 'system_var_values'));
			$vars = array();
			foreach($_POST['system_var_names'] as $i => $name)
			{
				if(isset($_POST['system_var_values'][$i]))
				{
					$name = pts_strings::keep_in_string(strtoupper($name), pts_strings::CHAR_LETTER | pts_strings::CHAR_NUMERIC | pts_strings::CHAR_UNDERSCORE);
					$val = pts_strings::keep_in_string($_POST['system_var_values'][$i], pts_strings::CHAR_LETTER | pts_strings::CHAR_NUMERIC | pts_strings::CHAR_DASH | pts_strings::CHAR_UNDERSCORE | pts_strings::CHAR_COMMA | pts_strings::CHAR_SLASH | pts_strings::CHAR_SPACE | pts_strings::CHAR_DECIMAL | pts_strings::CHAR_PLUS | pts_strings::CHAR_EQUAL);

					if($name != null)
					{
						$vars[$name] = $val;
					}
				}
			}

			$var_string = null;
			foreach($vars as $name => $val)
			{
				$var_string .= $name . '=' . $val . ';';
			}
			$stmt = phoromatic_server::$db->prepare('UPDATE phoromatic_systems SET SystemVariables = :system_variables WHERE AccountID = :account_id AND SystemID = :system_id');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$stmt->bindValue(':system_id', $PATH[0]);
			$stmt->bindValue(':system_variables', $var_string);
			$stmt->execute();
		}

		if(!empty($PATH[0]))
		{
			$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_systems WHERE AccountID = :account_id AND SystemID = :system_id ORDER BY LastCommunication DESC');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$stmt->bindValue(':system_id', $PATH[0]);
			$result = $stmt->execute();

			if(!empty($result))
			{
				$row = $result->fetchArray();

				if(!PHOROMATIC_USER_IS_VIEWER && isset($PATH[1]) && $PATH[1] == 'edit')
				{
					$main = '<h1>' . $row['Title'] . '</h1>';
					$main .= '<form name="system_form" id="system_form" action="?systems/' . $PATH[0] . '" method="post" onsubmit="return phoromatic_system_edit(this);">
			<p><div style="width: 200px; font-weight: bold; float: left;">System Title:</div> <input type="text" style="width: 400px;" name="system_title" value="' . $row['Title'] . '" /></p>
			<p><div style="width: 200px; font-weight: bold; float: left;">System Description:</div> <textarea style="width: 400px;" name="system_description">' . $row['Description'] . '</textarea></p>
			<p><div style="width: 200px; font-weight: bold; float: left;">System State:</div><select name="system_state" style="width: 200px;"><option value="-1">Disabled</option><option value="1" selected="selected">Enabled</option></select></p>
			<p><div style="width: 200px; font-weight: bold; float: left;">Allow Phoromatic To Power Off System When Testing Complete:</div><select name="block_power_offs" style="width: 200px;"><option value="0">Permitted</option><option value="1">Block Power-Off Signaling For This System</option></select> <sup>Assuming the power-off setting is enabled from the account settings page.</sup></p>
			<p><div style="width: 200px; font-weight: bold; float: left;">&nbsp;</div> <input type="submit" value="Submit" /></p></form>';
				}
				else
				{
					$main = '<h1>' . $row['Title'] . '</h1><p><em>' . ($row['Description'] != null ? $row['Description'] : 'No system description.') . '</em></p>';

					if(phoromatic_server::system_check_if_down($_SESSION['AccountID'], $row['SystemID'], $row['LastCommunication'], $row['CurrentTask']))
					{
						$main .= '<h3 style="text-align: center; color: red;">This system appears to be offline or inactive and there are pending tests scheduled to be run on this system that have yet to be completed. This system has not communicated with the Phoromatic Server in ' . pts_strings::format_time((time() - strtotime($row['LastCommunication'])), 'SECONDS', true, 60) . '.</h3>';
					}

					if(!PHOROMATIC_USER_IS_VIEWER)
					{
						$main .= '<p><a href="?systems/' . $PATH[0] . '/edit">Edit Task & Enable/Disable System</a></p>';
					}
				}

				switch($row['State'])
				{
					case -1:
						$state = 'Disabled';
						break;
					case 0:
						$state = 'Connected; Awaiting Approval';
						break;
					case 1:
						$state = 'Active';
						break;
					default:
						$state = 'Unknown';
						break;
				}

				$main .= '<hr />';
				$status_extra = null;
				if(!empty($row['CurrentProcessSchedule']))
				{
					$status_extra = ' - <a href="/?schedules/' . $row['CurrentProcessSchedule'] . '">' . phoromatic_server::schedule_id_to_name($row['CurrentProcessSchedule']) . '</a>';
				}
				else if(!empty($row['CurrentProcessTicket']))
				{
					$status_extra = ' - <a href="/?benchmark/' . $row['CurrentProcessTicket'] . '">' . phoromatic_server::ticket_id_to_name($row['CurrentProcessTicket']) . '</a>';
				}
				$info_table = array('Status:' => $row['CurrentTask'] . $status_extra, 'Last Communication:' => phoromatic_server::user_friendly_timedate($row['LastCommunication']), 'Estimated Time Left For Task: ' => phoromatic_server::estimated_time_remaining_string($row['EstimatedTimeForTask'], $row['LastCommunication']), 'State:' => $state, 'Phoronix Test Suite Client:' => $row['ClientVersion'], 'Initial Creation:' => phoromatic_server::user_friendly_timedate($row['CreatedOn']), 'System ID:' => $row['SystemID'], 'Last IP:' => $row['LastIP'], 'MAC Address:' => $row['NetworkMAC'], 'Wake-On-LAN Information:' => (empty($row['NetworkWakeOnLAN']) ? 'N/A' : $row['NetworkWakeOnLAN']), 'Power-Off Sequence Permitted: ' => ($row['BlockPowerOffs'] == 1 ? 'Blocked' : 'Permitted'));
				$main .= '<h2>System State</h2>' . pts_webui::r2d_array_to_table($info_table, 'auto');

				if(!PHOROMATIC_USER_IS_VIEWER)
				{
					if($row['MaintenanceMode'] == 1)
					{
						$mm_str = 'Disable Maintenance Mode';
						$mm_val = 0;
						$mm_onclick = 'return true;';
					}
					else
					{
						$mm_str = 'Enter Maintenance Mode';
						$mm_val = 1;
						$mm_onclick = 'return confirm(\'Enter maintenance mode now?\');';
					}

					$main .= '<p><form action="' . $_SERVER['REQUEST_URI'] . '" name="update_groups" method="post">' . write_token_in_form() . '<input type="hidden" name="maintenance_mode" value="' . $mm_val . '" /><input type="submit" value="' . $mm_str . '" onclick="' . $mm_onclick . '" style="float: left; margin: 0 20px 5px 0;" /></form> Putting the system into maintenance mode will power up the system (if supported and applicable) and cause the Phoronix Test Suite Phoromatic client to idle and block all testing until the mode has been disabled. If a test is already running on the system, the maintenance mode will not be entered until after the testing has completed. The maintenance mode can be used if wishing to update the system software or carry out other tasks without interfering with the Phoromatic client process. Once disabled, the Phoronix Test Suite will continue to function as normal.</p>';

					if($row['CoreVersion'] >= 5730)
					{
						$main .= '<p><form action="' . $_SERVER['REQUEST_URI'] . '" name="update_groups" method="post">' . write_token_in_form() . '<input type="hidden" name="tick_thread_reboot" value="1" /><input type="submit" value="Reboot System" style="float: left; margin: 0 20px 5px 0;" /></form> If the system is currently powered up and connected to the Phoromatic Server, this will send a message to the system to issue a reboot -- in case the system is hung on a test or you wish to otherwise manually reboot the server.</p>';

						$main .= '<p><form action="' . $_SERVER['REQUEST_URI'] . '" name="update_groups" method="post">' . write_token_in_form() . '<input type="hidden" name="tick_thread_halt" value="1" /><input type="submit" value="Halt Testing" style="float: left; margin: 0 20px 5px 0;" /></form> If the system is currently powered up and running a test/benchmark via the Phoromatic Server, this will tell the system to halt the testing prematurely as soon as the currently-active test has finished. The results successfully ran will then be uploaded to the Phoromatic Server.</p>';
					}
				}

				$main .= '<hr /><h2>System Variables</h2><p>System variables allow for providing per-system information in an easy-to-use manner for other parts of the Phoromatic system. Initially these named variables can be used for the results identifier when <a href="/?benchmark">creating a benchmark ticket</a> and in the future the system variables may be used elsewhere. Examples of system variables could include providing a <em>.SERIAL</em> variable to acknowledge the system\'s serial number that may not be presented elsewhere by the Phoronix Test Suite, <em>.ADMIN</em> for the system\'s local administrator, etc. Variable names can only be alpha-numeric strings while their values are also alpha-numeric strings but with spaces allowed. System variables are always prefixed by a period. These system variables are also automatically transferred to the Phoromatic clients and set as environment variables prior to running any scheduled tests/process via Phoromatic.</p>';

				$system_variables = $row['SystemVariables'] != null ? explode(';', $row['SystemVariables']) : array();

				$main .= '<form action="' . $_SERVER['REQUEST_URI'] . '" name="update_system_variables" method="post">';
				$main .= '<table width="80%"><tr><th>Variable Name</th><th>Value</th></tr>';
				$i = 0;
				foreach($system_variables as $i => $v_string)
				{
					$var = explode('=', $v_string);
					if(count($var) == 2)
					{
						$main .= '<tr id="system_var_' . $i . '">';
						$main .= '<td><span style="font-weight: 800; font-size: 16px;">.</span><input name="system_var_names[]" value="' . $var[0]. '" readonly /></td>';
						$main .= '<td><input name="system_var_values[]" value="' . $var[1]. '" /></td>';
						$main .= '</tr>';
					}
				}
				$main .= '<tr id="system_var_' . ($i + 1) . '">';
				$main .= '<td><span style="font-weight: 800; font-size: 16px;">.</span><input name="system_var_names[]" /></td>';
				$main .= '<td><input name="system_var_values[]" /></td>';
				$main .= '</tr>';
				$main .= '</table>';
				$main .= '<p><input name="submit" value="Update System Variables" type="submit" /></p></form>';

				$main .= '<hr /><h2>System Components</h2><div style="float: left; width: 50%;">';
				$components = pts_result_file_analyzer::system_component_string_to_array($row['Hardware']);
				$main .= pts_webui::r2d_array_to_table($components) . '</div><div style="float: left; width: 50%;">';
				$components = pts_result_file_analyzer::system_component_string_to_array($row['Software']);
				$main .= pts_webui::r2d_array_to_table($components) . '</div>';

				if(!empty($row['SystemProperties']))
				{
					$properties = json_decode($row['SystemProperties'], true);
					$main .= '<blockquote style="max-height: 440px; overflow: scroll; clear: both;">';
					foreach($properties as $component => $component_properties)
					{
						$main .= '<strong>' . strtoupper($component) . '</strong><br />';
						foreach($component_properties as $property => $value)
						{
							$main .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $property . ' = ';

							if(is_array($value))
							{
								foreach($value as $si => $sv)
								{
									if(is_array($sv))
									{
										foreach($sv as $ssi => $ssv)
										{
											$main .= '<br />' . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $ssi . ' = ' . $ssv;
										}
										$main .= '<br />';
									}
									else
									{
										$main .= '<br />' . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $sv . ' = ' . $sv . PHP_EOL;
									}
									//echo PHP_EOL;
								}
							}
							else
							{
								$main .= $value . '<br />';
							}
						}
						$main .= '<br />';
					}
					$main .= '</blockquote>';
				}

				$system_path = phoromatic_server::phoromatic_account_system_path($_SESSION['AccountID'], $row['SystemID']);
				$main .= '<hr />';
				if(is_file($system_path . 'sensors-pool.json'))
				{
					$sensors = file_get_contents($system_path . 'sensors-pool.json');
					$sensors = json_decode($sensors, true);

					foreach($sensors as $title => $s)
					{
						if(!isset($s['values']) || count($s['values']) < 5 || max($s['values']) == min($s['values']))
						{
							continue;
						}

						$graph = new pts_sys_graph(array('title' => $title, 'x_scale' => 'm', 'y_scale' => $s['unit'], 'text_size' => 12, 'reverse_x_direction' => false, 'width' => 920, 'height' => 400));
						$graph->render_base();
						$svg_dom = $graph->render_graph_data($s['values']);
						if($svg_dom === false)
						{
							continue;
						}
						$output_type = 'SVG';
						$graph = $svg_dom->output(null, $output_type);
						$main .= '<p align="center">' . substr($graph, strpos($graph, '<svg')) . '</p>';
					}
				}
				else if(is_file($system_path . 'sensors.json'))
				{
					$sensor_file = file_get_contents($system_path . 'sensors.json');
					$sensor_file = json_decode($sensor_file, true);
					if($sensor_file && isset($sensor_file['sensors']) && !empty($sensor_file['sensors']))
					{
						$i = 0;
						$col = array(1 => array(), 2 => array(), 3 => array(), 0 => array());
						foreach($sensor_file['sensors'] as $name => $sensor)
						{
							array_push($col[($i % 4)], '<strong>' . $name . ':</strong> ' . $sensor['value'] . ' ' . $sensor['unit']);
							$i++;
						}

						$main .= '<h2>System Sensors</h2>';
						foreach($col as $sensors)
						{
							$main .= '<div style="float: left; width: 25%;">';
							foreach($sensors as $sensor)
								$main .= '<p>' . $sensor . '</p>';
							$main .= '</div>';
						}
						$main .= '<p><em><strong>Last Updated:</strong>' . date('d F H:i', filemtime(phoromatic_server::phoromatic_account_system_path($_SESSION['AccountID'], $row['SystemID']) . 'sensors.json')) . ' <strong>System Uptime:</strong> ' . $sensor_file['uptime'] . ' Minutes</em></p>';
					}
				}
				$log_file = phoromatic_server::phoromatic_account_system_path($_SESSION['AccountID'], $row['SystemID']) . 'phoronix-test-suite.log';
				if(is_file($log_file))
				{
					$main .= '<hr /><h2>Phoronix Test Suite Client Log</h2>';
					$main .= '<p><textarea style="width: 100%; height: 300px;">' . file_get_contents($log_file)  . '</textarea></p>';
					$main .= '<p><em><strong>Last Updated:</strong>' . date ('d F H:i', filemtime($log_file)) . '</em></p>';
				}

				$groups = $row['Groups'] != null ? explode('#', $row['Groups']) : array();
				foreach($groups as $i => $group)
				{
					if(empty($group))
						unset($groups[$i]);
				}
				$schedules = phoromatic_server::schedules_that_run_on_system($_SESSION['AccountID'], $row['SystemID']);
				if(!empty($groups) || !empty($schedules))
				{
					$main .= '<hr /><h2>Schedules</h2>';
					if(!empty($groups))
						$group_msg = 'This system belongs to the following groups: <strong>' . implode(', ', $groups) . '</strong>.';
					else
						$group_msg = 'This system does not currently belong to any groups.';

					$main .= '<p>' . $group_msg . ' Manage groups via the <a href="?systems">systems page</a>.</p>';

					if(!empty($schedules))
					{
						$main .= '<div class="pts_phoromatic_info_box_area" style="margin: 0 10%;"><ul><li><h1>Schedules Running On This System</h1></li>';
						foreach($schedules as &$row)
						{
							$group_count = empty($row['RunTargetGroups']) ? 0 : count(explode(',', $row['RunTargetGroups']));
							$main .= '<a href="?schedules/' . $row['ScheduleID'] . '"><li>' . $row['Title'] . '<br /><table><tr><td>' . pts_strings::plural_handler(count(phoromatic_server::systems_associated_with_schedule($_SESSION['AccountID'], $row['ScheduleID'])), 'System') . '</td><td>' . pts_strings::plural_handler($group_count, 'Group') . '</td><td>' . pts_strings::plural_handler(phoromatic_results_for_schedule($row['ScheduleID']), 'Result') . '</td><td><strong>' . phoromatic_schedule_activeon_string($row['ActiveOn'], $row['RunAt']) . '</strong></td></tr></table></li></a>';
						}
						$main .= '</ul></div>';
					}
				}

				$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, ScheduleID, PPRID, UploadTime FROM phoromatic_results WHERE AccountID = :account_id AND SystemID = :system_id ORDER BY UploadTime DESC');
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$stmt->bindValue(':system_id', $PATH[0]);
				$test_result_result = $stmt->execute();
				$test_result_row = $test_result_result->fetchArray();
				$results = 0;

				if($test_result_row != false)
				{
					$main .= '<hr /><h2>Test Results</h2>';
					$main .= '<div class="pts_phoromatic_info_box_area" style="margin: 0 10%;">';
					$main .= '<ul><li><h1>Recent Test Results</h1></li>';

					do
					{
						if($results > 20)
						{
							break;
						}

						$main .= '<a href="?result/' . $test_result_row['PPRID'] . '"><li>' . $test_result_row['Title'] . '<br /><table><tr><td>' . phoromatic_server::system_id_to_name($test_result_row['SystemID']) . '</td><td>' . phoromatic_server::user_friendly_timedate($test_result_row['UploadTime']) .  '</td></tr></table></li></a>';
						$results++;

					}
					while($test_result_row = $test_result_result->fetchArray());
				}

				if($results > 0)
				{
					$main .= '</ul></div>';
				}


				// Any System Errors?
				$stmt = phoromatic_server::$db->prepare('SELECT ErrorMessage, UploadTime, SystemID, TestIdentifier FROM phoromatic_system_client_errors WHERE AccountID = :account_id AND SystemID = :system_id AND UploadTime >= date("now", "-14 day") ORDER BY UploadTime DESC LIMIT 300');
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$stmt->bindValue(':system_id', $PATH[0]);
				$result = $stmt->execute();
				$row = $result->fetchArray();
				if($row != false)
				{
					$main .= '<hr /><h2>Recent System Warnings &amp; Errors</h2>';
					$main .= '<div style="overflow: auto; max-height: 500px;">';

					do
					{
						$main .= '[' . $row['UploadTime'] . '] <strong>' . $row['TestIdentifier'] . '</strong>: ' .$row['ErrorMessage'] . '<br />';
					}
					while($row = $result->fetchArray());
					$main .= '</div>';
					$main .= '<p align="center"><a href="?systems/' . $PATH[0] . '/&clear_system_warnings">Clear System Warnings/Errors</a></p>';
				}

				$test_install_json = phoromatic_server::phoromatic_account_system_path($_SESSION['AccountID'], $PATH[0]) . 'test-installations.json';
				if(is_file($test_install_json))
				{
					$test_install_json = json_decode(file_get_contents($test_install_json), true);
					if(!empty($test_install_json))
					{
						$main .= '<hr /><h2>Test Profile Installations</h2>';
						foreach($test_install_json as $test_profile => $ti_data)
						{
							$test_installation = new pts_installed_test($ti_data);
							$status = $test_installation->get_install_status();
							if($status == 'INSTALLED')
							{
								$status = '<span style="color: green;">' . $status . '</span> ' . ($test_installation->get_run_count() > 0 ? '<strong>Times Run:</strong> ' . $test_installation->get_run_count() : '');
							}
							else if($status == 'INSTALL_FAILED')
							{
								$status = '<span style="color: red; font-weight: bold;">INSTALL FAILED</span>';
							}
							$error_output = '';
							$runtime_errors = $test_installation->get_runtime_errors();
							$install_errors = $test_installation->get_install_errors();
							if(!empty($runtime_errors))
							{
								foreach($runtime_errors as $e)
								{
									$error_output .= '<br />' . trim((empty($e['description']) ? '' : '<em>' . $e['description'] . '</em> - ') . 'Last Attempted: ' . $e['date_time']);
									foreach($e['errors'] as $error)
									{
										$error_output .= '<br /> &nbsp; &nbsp; <span style="color: red; font-weight: bold;">    ' . $error . '</span>';
									}
								}
							}
							if(!empty($install_errors))
							{
								foreach($install_errors as $install_error)
								{
									$error_output .= '<br /><span style="color: red; font-weight: bold;">    ' . $install_error . '</span>';
								}
							}
							$main .= '<p><strong>' .  $test_profile . '</strong> ' .  $status . ' (Install Date: ' . $test_installation->get_install_date() . ')' . $error_output . '</p>';
						}
					}
				}
			}
		}

		if($main == null)
		{
			if(!PHOROMATIC_USER_IS_VIEWER && isset($_POST['new_group']) && !empty($_POST['new_group']) && verify_submission_token())
			{
				$group = trim($_POST['new_group']);

				if($group)
				{
					phoromatic_quit_if_invalid_input_found(array('new_group'));
					$group = pts_strings::simple($group);
					$stmt = phoromatic_server::$db->prepare('INSERT INTO phoromatic_groups (AccountID, GroupName) VALUES (:account_id, :group_name)');
					$stmt->bindValue(':account_id', $_SESSION['AccountID']);
					$stmt->bindValue(':group_name', $group);
					$result = $stmt->execute();
					phoromatic_add_activity_stream_event('groups', $group, 'added');

					if(!empty($_POST['systems_for_group']) && is_array($_POST['systems_for_group']))
					{
						foreach($_POST['systems_for_group'] as $sid)
						{
							// Find current groups
							$stmt = phoromatic_server::$db->prepare('SELECT Groups FROM phoromatic_systems WHERE AccountID = :account_id AND SystemID = :system_id ORDER BY LastCommunication DESC');
							$stmt->bindValue(':account_id', $_SESSION['AccountID']);
							$stmt->bindValue(':system_id', $sid);
							$result = $stmt->execute();
							$row = $result->fetchArray();
							$existing_groups = $row != false ? $row['Groups'] : null;

							// Append new Group
							$stmt = phoromatic_server::$db->prepare('UPDATE phoromatic_systems SET Groups = :new_group WHERE AccountID = :account_id AND SystemID = :system_id');
							$stmt->bindValue(':account_id', $_SESSION['AccountID']);
							$stmt->bindValue(':system_id', $sid);
							$stmt->bindValue(':new_group', $existing_groups . '#' . $group . '#');
							$stmt->execute();
						}
					}
				}
			}
			else if(!PHOROMATIC_USER_IS_VIEWER && isset($_POST['system_group_update']) && verify_submission_token())
			{
				$stmt = phoromatic_server::$db->prepare('SELECT SystemID FROM phoromatic_systems WHERE AccountID = :account_id');
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$result = $stmt->execute();
				phoromatic_add_activity_stream_event('groups', null, 'modified');

				while($row = $result->fetchArray())
				{
					if(isset($_POST['groups_' . $row['SystemID']]))
					{
						$group_string = null;
						foreach($_POST['groups_' . $row['SystemID']] as $group)
						{
							if($group != null)
							{
								$group_string .= '#' . $group . '#';
							}
						}

							$stmt1 = phoromatic_server::$db->prepare('UPDATE phoromatic_systems SET Groups = :new_groups WHERE AccountID = :account_id AND SystemID = :system_id');
							$stmt1->bindValue(':account_id', $_SESSION['AccountID']);
							$stmt1->bindValue(':system_id', $row['SystemID']);
							$stmt1->bindValue(':new_groups', $group_string);
							$stmt1->execute();
					}
				}
			}
			else if(!PHOROMATIC_USER_IS_VIEWER && isset($_POST['remove_group']) && verify_submission_token())
			{
				$remove_group = pts_strings::sanitize($_POST['remove_group']);
				$stmt = phoromatic_server::$db->prepare('DELETE FROM phoromatic_groups WHERE AccountID = :account_id AND GroupName = :group_name');
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$stmt->bindValue(':group_name', $remove_group);
				$stmt->execute();
				phoromatic_add_activity_stream_event('groups', $group, 'removed');

				$stmt = phoromatic_server::$db->prepare('SELECT SystemID, Groups FROM phoromatic_systems WHERE AccountID = :account_id AND Groups LIKE \'%#' . $remove_group . '#%\'');
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$result = $stmt->execute();
				while($row = $result->fetchArray())
				{
					$revised_groups = str_replace('#' . $remove_group . '#', '', $row['Groups']);

					$stmt1 = phoromatic_server::$db->prepare('UPDATE phoromatic_systems SET Groups = :new_groups WHERE AccountID = :account_id AND SystemID = :system_id');
					$stmt1->bindValue(':account_id', $_SESSION['AccountID']);
					$stmt1->bindValue(':system_id', $row['SystemID']);
					$stmt1->bindValue(':new_groups', $revised_groups);
					$stmt1->execute();
				}
			}
			else if(!PHOROMATIC_USER_IS_VIEWER && isset($_POST['remove_inactive_systems']) && is_numeric($_POST['remove_inactive_systems']) && $_POST['remove_inactive_systems'] > 1)
			{
				// $_POST['remove_inactive_systems'] is number of days system is without activity before removing
				$stmt = phoromatic_server::$db->prepare('UPDATE phoromatic_systems SET State = :state WHERE AccountID = :account_id AND (julianday() - julianday(LastCommunication)) > :inactive_days_before_removal');
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$stmt->bindValue(':state', -1);
				$stmt->bindValue(':inactive_days_before_removal', pts_strings::sanitize($_POST['remove_inactive_systems']));
				$stmt->execute();
			}

			$main = '<h1>Test Systems</h1>';
			if(!PHOROMATIC_USER_IS_VIEWER)
			{
				$main .= phoromatic_systems_needing_attention();
				$main .= '<h2>Add A System</h2>
				<p>To connect a <a href="http://www.phoronix-test-suite.com/">Phoronix Test Suite</a> test system to this account for remotely managing and/or carrying out routine automated benchmarking, follow these simple and quick steps:</p>
				<p>From a system with Phoronix Test Suite installed, run <strong>phoronix-test-suite phoromatic.connect ' . phoromatic_web_socket_server_addr() . '</strong>. (The test system must be able to access this server\'s correct IP address / domain name.)</p><p>When you have run the command from the test system, you will need to log into this page on Phoromatic server again where you can approve the system and configure the system settings so you can begin using it as part of this Phoromatic account.</p><p>Repeat the two steps for as many systems as you would like. When you are all done -- if you haven\'t done so already, you can start creating test schedules, groups, and other Phoromatic events.</p>
				<p>Those having to connect many Phoronix Test Suite Phoromatic clients can also attempt <a href="?system_claim">adding the server configuration</a> via SSH or an IP/MAC address claim.</p>
				<p>The Phoronix Test Suite ships with a <em>phoromatic-client</em> systemd example service file for automatically starting the Phoromatic client process after the initial configuration process is complete.</p>
				<p><button onclick="javascript:window.location.replace(\'?system_claim\');">Add Via SSH Or IP/MAC Claim</button></p>';

			}

			$main .= '<hr />
			<h2>Systems</h2>
			<div class="pts_phoromatic_info_box_area">

					<ul>
						<li><h1>Active Systems</h1></li>';

					$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, LocalIP, CurrentTask, LastCommunication, EstimatedTimeForTask, TaskPercentComplete FROM phoromatic_systems WHERE AccountID = :account_id AND State >= 0 ORDER BY LastCommunication DESC');
					$stmt->bindValue(':account_id', $_SESSION['AccountID']);
					$result = $stmt->execute();
					$row = $result->fetchArray();
					$active_system_count = 0;

					if($row == false)
					{
						$main .= '<li class="light" style="text-align: center;">No Systems Found</li>';
					}
					else
					{
						do
						{
							$acti = phoromatic_server::estimated_time_remaining_string($row['EstimatedTimeForTask'], $row['LastCommunication']) . ($row['TaskPercentComplete'] > 0 ? ' [' . $row['TaskPercentComplete'] . '% Complete]' : null);
							if(empty($acti))
							{
								$next_job_in = phoromatic_server::time_to_next_scheduled_job($_SESSION['AccountID'], $row['SystemID']);
								if($next_job_in > 0)
								{
									if($next_job_in > 600)
									{
										$next_job_in = round($next_job_in / 60);
										$next_unit = 'hours';
									}
									else
									{
										$next_unit = 'minutes';
									}

									$acti = 'Next job in ' . $next_job_in . ' ' . $next_unit;
								}
							}

							$main .= '<a href="?systems/' . $row['SystemID'] . '"><li>' . $row['Title'] . '<br /><table><tr><td>' . $row['LocalIP'] . '</td><td><strong>' . $row['CurrentTask'] . '</strong></td><td><strong>' . $acti . '</strong></td><td><strong>Last Communication:</strong> ' . date('j F Y H:i', strtotime($row['LastCommunication'])) . '</td></tr></table></li></a>';
							$active_system_count++;
						}
						while($row = $result->fetchArray());
					}


			$main .= '</ul>';

			$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, LocalIP, CurrentTask, LastCommunication, EstimatedTimeForTask, TaskPercentComplete FROM phoromatic_systems WHERE AccountID = :account_id AND State < 0 ORDER BY LastCommunication DESC');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$result = $stmt->execute();
			$row = $result->fetchArray();

			if($row != false)
			{
				$main .= '<ul>
				<li><h1>Inactive Systems</h1></li>';
				do
				{
					$main .= '<a href="?systems/' . $row['SystemID'] . '"><li>' . $row['Title'] . '<br /><table><tr><td>' . $row['LocalIP'] . '</td><td><strong>' . $row['CurrentTask'] . '</strong></td><td><strong>Deactivated</strong></td><td><strong>Last Communication:</strong> ' . date('j F Y H:i', strtotime($row['LastCommunication'])) . '</td></tr></table></li></a>';
				}
				while($row = $result->fetchArray());
				$main .= '</ul>';
			}

			$main .= '</div>';

			if(!PHOROMATIC_USER_IS_VIEWER)
			{
				$main .= '<hr />
				<h2>System Groups</h2>
				<p>System groups make it very easy to organize multiple test systems for targeting by test schedules. You can always add/remove systems to groups, create new groups, and add systems to multiple groups. After creating a group and adding systems to the group, you can begin targeting tests against a particular group of systems. Systems can always be added/removed from groups later and a system can belong to multiple groups.</p>';


				$main .= '<div style="float: left;"><form name="new_group_form" id="new_group_form" action="?systems" method="post" onsubmit="return phoromatic_new_group(this);">' . write_token_in_form() . '
				<p><div style="width: 200px; font-weight: bold; float: left;">New Group Name:</div> <input type="text" style="width: 300px;" name="new_group" value="" /></p>
				<p><div style="width: 200px; font-weight: bold; float: left;">Select System(s) To Add To Group:</div><select name="systems_for_group[]" multiple="multiple" style="width: 300px;">';

				$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID FROM phoromatic_systems WHERE AccountID = :account_id AND State >= 0 ORDER BY Title ASC');
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$result = $stmt->execute();
				$row = $result->fetchArray();

				if($row != false)
				{
					do
					{
						$main .= '<option value="' . $row['SystemID'] . '">' . $row['Title'] . '</option>';
					}
					while($row = $result->fetchArray());
				}


				$main .= '</select></p>
				<p><div style="width: 200px; font-weight: bold; float: left;">&nbsp;</div> <input type="submit" value="Create Group" /></p></form></div>';

				$stmt = phoromatic_server::$db->prepare('SELECT GroupName FROM phoromatic_groups WHERE AccountID = :account_id ORDER BY GroupName ASC');
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$result = $stmt->execute();
				$row = $result->fetchArray();

				if($row != false)
				{
					$main .= '<div style="float: left; margin-left: 90px;"><h3>Current System Groups</h3>';

					do
					{
						$stmt_count = phoromatic_server::$db->prepare('SELECT COUNT(SystemID) AS system_count FROM phoromatic_systems WHERE AccountID = :account_id AND State >= 0 AND Groups LIKE \'%#' . $row['GroupName'] . '#%\'');
						$stmt_count->bindValue(':account_id', $_SESSION['AccountID']);
						$result_count = $stmt_count->execute();
						$row_count = $result_count->fetchArray();
						$row_count['system_count'] = isset($row_count['system_count']) ? $row_count['system_count'] : 0;

						$main .= '<div style="clear: both;"><div style="width: 200px; float: left; font-weight: bold;">' . $row['GroupName'] . '</div> ' . $row_count['system_count'] . ' System' . ($row_count['system_count'] != 1 ? 's' : '') . '</div>';

					}
					while($row = $result->fetchArray());

					$main .= '</div>';

					$main .= '<hr /><a name="group_edit"></a><h2>System Group Editing</h2><div style="text-align: center;"><form action="' . $_SERVER['REQUEST_URI'] . '" name="update_groups" method="post">' . write_token_in_form() . '<input type="hidden" name="system_group_update"  value="1" />';
					$main .= '<table style="margin: 5px auto; overflow: auto;">';
					$main .= '<tr>';
					$main .= '<th></th>';

					$stmt = phoromatic_server::$db->prepare('SELECT GroupName FROM phoromatic_groups WHERE AccountID = :account_id ORDER BY GroupName ASC');
					$stmt->bindValue(':account_id', $_SESSION['AccountID']);
					$result = $stmt->execute();
					$all_groups = array();
					while($row = $result->fetchArray())
					{
						$main .= '<th>' . $row['GroupName'] . '</th>';
						array_push($all_groups, $row['GroupName']);
					}

					$main .= '</tr>';

					$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, Groups FROM phoromatic_systems WHERE AccountID = :account_id AND State >= 0 ORDER BY Title ASC');
					$stmt->bindValue(':account_id', $_SESSION['AccountID']);
					$result = $stmt->execute();
					while($row = $result->fetchArray())
					{
						$main .= '<tr>';
						$main .= '<th>' . $row['Title'] . '</th>';
						$main .= '<input type="hidden" name="groups_' . $row['SystemID'] . '[]" value="" />';

						foreach($all_groups as $group)
						{
							$checked = stripos($row['Groups'], '#' . $group . '#') !== false ? 'checked="checked" ' : null;
							$main .= '<td><input type="checkbox" name="groups_' . $row['SystemID'] . '[]" value="' . $group . '" ' . $checked . '/></td>';
						}
						$main .= '</tr>';
					}

					$main .= '</table><p><input name="submit" value="Update Groups" type="submit" /></p></form></div>';
					$main .= '<hr /><h2>Remove A Group</h2><p>Removing a group is a permanent action that cannot be undone.</p>';
					$main .= '<p><form action="' . $_SERVER['REQUEST_URI'] . '" name="remove_group" method="post">' . write_token_in_form() . '<select name="remove_group" id="remove_group">';

					foreach($all_groups as $group)
					{
						$main .= '<option value="' . $group . '">' . $group . '</option>';
					}
					$main .= '</select> <input name="submit" value="Remove Group" type="submit" /></form></p>';
					$main .= '<hr /><h2>Retire Inactive Systems</h2><p>This option will soft-delete systems that have not communicated with this Phoromatic Server in more than one week (7 days).</p>';
					$main .= '<p><form action="' . $_SERVER['REQUEST_URI'] . '" name="remove_inactive" method="post"><input type="hidden" name="remove_inactive_systems" value="7" /><input name="submit" value="Remove Inactive Systems" type="submit" /></form></p>';
				}
			}
		}

		$right = '<ul><li>Active Systems</li>';

		$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID FROM phoromatic_systems WHERE AccountID = :account_id AND State > 0 ORDER BY Title ASC');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$result = $stmt->execute();
		$row = $result->fetchArray();

		if($row == false)
		{
			$right .= '</ul><p style="text-align: left; margin: 6px 10px;">No Systems Found</p>';
		}
		else
		{
			do
			{
				$right .= '<li><a href="?systems/' . $row['SystemID'] . '">' . $row['Title'] . '</a></li>';
			}
			while($row = $result->fetchArray());
			$right .= '</ul>';
		}
		echo '<div id="pts_phoromatic_main_area">' . $main . '</div>';
		echo phoromatic_webui_footer();
	}
}

?>
