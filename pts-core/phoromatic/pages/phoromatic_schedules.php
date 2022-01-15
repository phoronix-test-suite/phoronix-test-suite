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

class phoromatic_schedules implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Test Schedules';
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
		echo phoromatic_webui_header_logged_in();

		if(!empty($PATH[0]) && is_numeric($PATH[0]))
		{
			$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_schedules WHERE AccountID = :account_id AND ScheduleID = :schedule_id');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$stmt->bindValue(':schedule_id', $PATH[0]);
			$result = $stmt->execute();
			$row = $result->fetchArray();

			if(empty($row))
			{
				$main = '<h1>Test Schedules</h1>';
				$main .= '<h3>No Resource Found</h3>';
			}
			else
			{
				if(!PHOROMATIC_USER_IS_VIEWER)
				{

					if(isset($_POST['add_to_schedule_select_test']) && verify_submission_token())
					{
						phoromatic_quit_if_invalid_input_found(array('add_to_schedule_select_test'));
						$name = $_POST['add_to_schedule_select_test'];
						$args = array();
						$args_name = array();

						foreach($_POST as $i => $v)
						{
							if(substr($i, 0, 12) == 'test_option_' && substr($i, -9) != '_selected')
							{
								phoromatic_quit_if_invalid_input_found(array($i, $i . '_selected'));
								array_push($args, $v);
								array_push($args_name, $_POST[$i . '_selected']);
							}
						}

						$args_name = implode(' - ', $args_name);
						$args = implode(' ', $args);

						if(!empty($name))
						{
							$stmt = phoromatic_server::$db->prepare('INSERT INTO phoromatic_schedules_tests (AccountID, ScheduleID, TestProfile, TestArguments, TestDescription) VALUES (:account_id, :schedule_id, :test_profile, :test_arguments, :test_description)');
							$stmt->bindValue(':account_id', $_SESSION['AccountID']);
							$stmt->bindValue(':schedule_id', $PATH[0]);
							$stmt->bindValue(':test_profile', $name);
							$stmt->bindValue(':test_arguments', $args);
							$stmt->bindValue(':test_description', $args_name);
							$result = $stmt->execute();
							phoromatic_add_activity_stream_event('tests_for_schedule', $PATH[0], 'added');
						}
					}
					else if(isset($_POST['suite_add']) && verify_submission_token())
					{
						$test_suite = phoromatic_server::find_suite_file($_SESSION['AccountID'], $_POST['suite_add']);
						if(is_file($test_suite))
						{
							$test_suite = new pts_test_suite($test_suite);
							foreach($test_suite->get_contained_test_result_objects() as $tro)
							{
								$stmt = phoromatic_server::$db->prepare('INSERT INTO phoromatic_schedules_tests (AccountID, ScheduleID, TestProfile, TestArguments, TestDescription) VALUES (:account_id, :schedule_id, :test_profile, :test_arguments, :test_description)');
								$stmt->bindValue(':account_id', $_SESSION['AccountID']);
								$stmt->bindValue(':schedule_id', $PATH[0]);
								$stmt->bindValue(':test_profile', $tro->test_profile->get_identifier());
								$stmt->bindValue(':test_arguments', $tro->get_arguments());
								$stmt->bindValue(':test_description', $tro->get_arguments_description());
								$result = $stmt->execute();
								phoromatic_add_activity_stream_event('tests_for_schedule', $PATH[0], 'added');
							}
						}
					}
					else if(isset($PATH[1]) && $PATH[1] == 'remove' && !empty($PATH[2]))
					{
						// REMOVE TEST
						$to_remove = explode(PHP_EOL, base64_decode($PATH[2]));
						$stmt = phoromatic_server::$db->prepare('DELETE FROM phoromatic_schedules_tests WHERE AccountID = :account_id AND ScheduleID = :schedule_id AND TestProfile = :test AND TestArguments = :test_args');
						$stmt->bindValue(':account_id', $_SESSION['AccountID']);
						$stmt->bindValue(':schedule_id', $PATH[0]);
						$stmt->bindValue(':test', $to_remove[0]);
						$stmt->bindValue(':test_args', $to_remove[1]);
						$result = $stmt->execute();
						phoromatic_add_activity_stream_event('tests_for_schedule', $to_remove[0] . ' - ' . $to_remove[1], 'removed');
					}
					else if(isset($PATH[1]) && $PATH[1] == 'delete-trigger' && !empty($PATH[2]))
					{
						// REMOVE TRIGGER
						$trigger = pts_strings::sanitize(base64_decode($PATH[2]));
						$stmt = phoromatic_server::$db->prepare('DELETE FROM phoromatic_schedules_triggers WHERE AccountID = :account_id AND Trigger = :trigger AND ScheduleID = :schedule_id');
						$stmt->bindValue(':account_id', $_SESSION['AccountID']);
						$stmt->bindValue(':schedule_id', $PATH[0]);
						$stmt->bindValue(':trigger', $trigger);
						$result = $stmt->execute();
						if($result)
							$main .= '<h2 style="color: red;">Trigger Removed: ' . $trigger . '</h2>';
					}
					else if(isset($PATH[1]) && in_array($PATH[1], array('activate', 'deactivate')) && verify_submission_token())
					{
						switch($PATH[1])
						{
							case 'deactivate':
								$new_state = 0;
								break;
							case 'activate':
							default:
								$new_state = 1;
								break;
						}

						// REMOVE TEST
						$stmt = phoromatic_server::$db->prepare('UPDATE phoromatic_schedules SET State = :new_state WHERE AccountID = :account_id AND ScheduleID = :schedule_id');
						$stmt->bindValue(':account_id', $_SESSION['AccountID']);
						$stmt->bindValue(':schedule_id', $PATH[0]);
						$stmt->bindValue(':new_state', $new_state);
						$result = $stmt->execute();
						$row['State'] = $new_state;
						phoromatic_add_activity_stream_event('schedule', $PATH[0], $PATH[1]);
					}
					else if(isset($_POST['do_manual_test_run']) && verify_submission_token())
					{
						$stmt = phoromatic_server::$db->prepare('INSERT INTO phoromatic_schedules_triggers (AccountID, ScheduleID, Trigger, TriggeredOn) VALUES (:account_id, :schedule_id, :trigger, :triggered_on)');
						$stmt->bindValue(':account_id',	$_SESSION['AccountID']);
						$stmt->bindValue(':schedule_id', $PATH[0]);
						$stmt->bindValue(':trigger', $_SESSION['UserName'] . ' - Manual Test Run - ' . date('H:i j M Y'));
						$stmt->bindValue(':triggered_on', phoromatic_server::current_time());
						$stmt->execute();
						$main .= '<h2 style="color: red;">Manual Test Run Triggered</h2>';
					}
					else if(isset($_POST['skip_current_ticket']) && verify_submission_token())
					{
						$stmt = phoromatic_server::$db->prepare('INSERT INTO phoromatic_schedules_trigger_skips (AccountID, ScheduleID, Trigger) VALUES (:account_id, :schedule_id, :trigger)');
						$stmt->bindValue(':account_id',	$_SESSION['AccountID']);
						$stmt->bindValue(':schedule_id', $PATH[0]);
						$stmt->bindValue(':trigger', date('Y-m-d'));
						$stmt->execute();
						$main .= '<h2 style="color: red;">Current Trigger To Be Ignored</h2>';
					}
				}

				$main .= '<h1>' . $row['Title'] . '</h1>';
				$main .= '<h3>' . $row['Description'] . '</h3>';
				switch($row['RunPriority'])
				{
					case 1:
						$prio = 'Low Priority';
						break;
					case 100:
						$prio = 'Default Priority';
						break;
					case 200:
						$prio = 'High Priority';
						break;
					default:
						$prio = $row['RunPriority'] . ' Priority';
						break;
				}

				$main .= '<p>Priority: ' . $prio . '</p><p>This schedule was last modified on <strong>' . date('j F Y \a\t H:i', strtotime($row['LastModifiedOn'])) . '</strong> by <strong>' . $row['LastModifiedBy'] . '</strong>.';

				if(!PHOROMATIC_USER_IS_VIEWER)
				{
					$main .= '<p><a href="?sched/' . $PATH[0] . '">Edit Schedule</a> | ';

					if($row['State'] == 1)
					{
						$main .= '<a href="?schedules/' . $PATH[0] . '/deactivate' . append_token_to_url()  . '">Deactivate Schedule</a>';
					}
					else
					{
						$main .= '<a href="?schedules/' . $PATH[0] . '/activate' . append_token_to_url()  . '">Activate Schedule</a>';
					}

					$main .= '</p>';
				}

				$main .= '<hr />';
				$main .= '<h2>Schedule</h2>';
				if(!empty($row['ActiveOn']))
				{
					$active_days = explode(',', $row['ActiveOn']);
					$week = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
					foreach($active_days as $i => &$day)
					{
						if(!isset($week[$day]))
						{
							unset($active_days[$i]);
						}
						else
						{
							$day = $week[$day];
						}
					}

					switch(count($active_days))
					{
						case 2:
							$day_show = implode(' and ', $active_days);
							break;
						default:
							$day_show = implode(', ', $active_days);
							break;
					}

					$main .= '<p>This test is scheduled to run every <strong>' . $day_show . '</strong> at <strong>' . str_replace('.', ':', $row['RunAt']) . '</strong>.</p>';
				}
				else
				{
					$main .= '<p>This test schedule is not currently set to run a pre-defined time-based schedule.</p>';
				}
				if(!PHOROMATIC_USER_IS_VIEWER)
				{
					$trigger_url = 'http://' . phoromatic_web_socket_server_ip() . '/event.php?type=trigger&user=' . $_SESSION['UserName'] . '&public_key=' . $row['PublicKey'] . '&trigger=XXX';
					$main .= '<p>This test schedule can be manually triggered to run at any time by calling <strong>' . $trigger_url . '</strong> where <em>XXX</em> is the trigger value to be used (if relevant, such as a time-stamp, Git/SVN commit number or hash, etc). There\'s also the option of sub-targeting system(s) part of this schedule. One option is appending <em>&sub_target_this_ip</em> if this URL is being called from one of the client test systems to only sub-target the triggered testing on that client, among other options.</p>';
					$main .= '<p>If you wish to run this test schedule now, click the following button and the schedule will be run on all intended systems at their next earliest possible convenience.</p>';
					$main .= '<p><form action="?schedules/' . $PATH[0] . '" name="manual_run" method="post">';
					$main .= write_token_in_form() . '<input type="hidden" name="do_manual_test_run" value="1" /><input type="submit" value="Run Test Schedule Now" onclick="return confirm(\'Run this test schedule now?\');" />';
					$main .= '</form></p>';
					$main .= '<p><form action="?schedules/' . $PATH[0] . '" name="skip_run" method="post">';
					$main .= write_token_in_form() . '<input type="hidden" name="skip_current_ticket" value="1" /><input type="submit" value="Skip Current Test Ticket" onclick="return confirm(\'Skip any currently active test ticket on all systems?\');" />';
					$main .= '</form></p>';
				}

				$main .= '<hr />';

				$contexts = array('SetContextPreInstall' => 'Pre-Install', 'SetContextPostInstall' => 'Post-Install', 'SetContextPreRun' => 'Pre-Test-Run', 'SetContextPostRun' => 'Post-Test-Run');
				$scripts = 0;
				foreach($contexts as $context => $v)
				{
					if(isset($row[$context]) && !empty($row[$context]) && is_file(phoromatic_server::phoromatic_account_path($_SESSION['AccountID']) . 'context_' . $row[$context]))
					{
						$scripts++;
						$main .= '<h2>' . $v . ' Context Script</h2>';
						$main .= '<blockquote>' . str_replace(PHP_EOL, '<br />', htmlentities(file_get_contents(phoromatic_server::phoromatic_account_path($_SESSION['AccountID']) . 'context_' . $row[$context]))) . '</blockquote>';
					}
				}

				if(!empty($row['EnvironmentVariables']))
				{
					$main .= '<hr /><h1>Environment Variables</h1><ol>';

					foreach(explode(';', $row['EnvironmentVariables']) as $env)
					{
						$main .= '<li><strong>' . $env . '</strong></li>';
					}
					$main .= '</ol>';
				}

				if($scripts > 0)
					$main .= '<hr />';

				$main .= '<h2>Tests To Run</h2>';

				$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_schedules_tests WHERE AccountID = :account_id AND ScheduleID = :schedule_id ORDER BY TestProfile ASC');
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$stmt->bindValue(':schedule_id', $PATH[0]);
				$result = $stmt->execute();

				$test_count = 0;
				$main .= '<p>';
				while($row = $result->fetchArray())
				{
					$test_count++;
					$main .= $row['TestProfile'] . ($row['TestDescription'] != null ? ' - <em>' . $row['TestDescription'] . '</em>' : '') . (!PHOROMATIC_USER_IS_VIEWER ? ' <a href="?schedules/' . $PATH[0] . '/remove/' . base64_encode(implode(PHP_EOL, array($row['TestProfile'], $row['TestArguments']))) . '">Remove Test</a>' : null) . '<br />';

					/*
					if(!PHOROMATIC_USER_IS_VIEWER && isset($_REQUEST['make_version_lock_tests']))
					{
						if(strpos($row['TestProfile'], '.') == false)
						{
							$test_profile = new pts_test_profile($row['TestProfile']);
							$full_identifier = $test_profile->get_identifier(true);

							$stmt = phoromatic_server::$db->prepare('UPDATE phoromatic_schedules_tests SET TestProfile = :version_locked_tp WHERE AccountID = :account_id AND ScheduleID = :schedule_id AND TestProfile = :test');
							$stmt->bindValue(':account_id', $_SESSION['AccountID']);
							$stmt->bindValue(':schedule_id', $PATH[0]);
							$stmt->bindValue(':test', $row['TestProfile']);
							$stmt->bindValue(':version_locked_tp', $full_identifier);
							$result2 = $stmt->execute();
						}
					}
					*/
				}
				$main .= '</p>';

				if($test_count == 0)
				{
					$main .= '<h3 style="text-transform: uppercase;">No tests have been added yet for this test schedule.</h3>';
				}

				if(!PHOROMATIC_USER_IS_VIEWER)
				{
					$main .= '<hr /><h2>Add A Test</h2>';
					$main .= '<form action="?schedules/' . $PATH[0] . '" name="add_test" id="add_test" method="post">';
					$main .= write_token_in_form() . '<select name="add_to_schedule_select_test" id="add_to_schedule_select_test" onchange="phoromatic_schedule_test_details(\'\');">';
					$dc = pts_client::download_cache_path();
					$dc_exists = is_file($dc . 'pts-download-cache.json');
					if($dc_exists)
					{
						$cache_json = file_get_contents($dc . 'pts-download-cache.json');
						$cache_json = json_decode($cache_json, true);
					}
					foreach(array_merge(pts_tests::local_tests(), pts_openbenchmarking::available_tests(false, isset($_COOKIE['list_show_all_test_versions']) && $_COOKIE['list_show_all_test_versions'])) as $test)
					{
						if(phoromatic_server::read_setting('show_local_tests_only'))
						{
							$cache_checked = false;
							if($dc_exists)
							{
								if($cache_json && isset($cache_json['phoronix-test-suite']['cached-tests']))
								{
									if(in_array($test, $cache_json['phoronix-test-suite']['cached-tests']))
									{
										$cache_checked = true;
									}
								}
							}
							if(!$cache_checked && pts_test_install_request::test_files_available_on_local_system($test) == false)
							{
								continue;
							}
						}

						$main .= '<option value="' . $test . '">' . $test . '</option>';
					}
					$main .= '</select>';
					$main .= pts_web_embed::cookie_checkbox_option_helper('list_show_all_test_versions', 'Show all available test profile versions.');
					$main .= '<p><div id="test_details"></div></p>';
					$main .= '</form>';

					$local_suites = array();
					foreach(pts_file_io::glob(phoromatic_server::phoromatic_account_suite_path($_SESSION['AccountID']) . '*/suite-definition.xml') as $xml_path)
					{
						$id = basename(dirname($xml_path));
						$test_suite = new pts_test_suite($xml_path);
						$local_suites[$test_suite->get_title() . ' - ' . $id] = $id;
					}
					$official_suites = pts_test_suites::suites_on_disk(false, true);

					$main .= '<hr /><h2>Add A Suite:</h2>';
					$main .= '<form action="?schedules/' . $PATH[0] . '" name="add_suite" id="add_suite" method="post">';
					$main .= write_token_in_form() . '<p><select name="suite_to_run" id="suite_to_run_identifier" onchange="phoromatic_show_basic_suite_details(\'\');">';
					foreach(array_merge($local_suites, $official_suites) as $title => $id)
					{
						$main .= '<option value="' . $id . '">' . $title . '</option>';
					}
					$main .= '</select></p>';
					$main .= '<p><div id="suite_details"></div></p>';
					$main .= '</form>';
				}

				$systems_in_schedule = phoromatic_server::systems_associated_with_schedule($_SESSION['AccountID'], $PATH[0]);
				if(!empty($systems_in_schedule))
				{
					$main .= '<hr /><h2>Systems In Schedule</h2>';
					if(!PHOROMATIC_USER_IS_VIEWER)
					{
						$main .= '<p>To run this schedule on more systems, <a href="?sched/' . $PATH[0] . '">edit the schedule</a>.</p>';
					}
					$main .= '<div class="pts_phoromatic_info_box_area" style="margin: 0 10%;"><ul><li><h1>Systems</h1></li>';

					foreach($systems_in_schedule as $system_id)
					{
						$row = phoromatic_server::get_system_details($_SESSION['AccountID'], $system_id);
						$main .= '<a href="?systems/' . $row['SystemID'] . '"><li>' . $row['Title'] . '<br /><table><tr><td>' . $row['LocalIP'] . '</td><td><strong>' . $row['CurrentTask'] . '</strong></td><td><strong>Last Communication:</strong> ' . date('j F Y H:i', strtotime($row['LastCommunication'])) . '</td></tr></table></li></a>';
					}
					$main .= '</ul></div><hr />';
				}

				$stmt = phoromatic_server::$db->prepare('SELECT Trigger, TriggeredOn FROM phoromatic_schedules_triggers WHERE AccountID = :account_id AND ScheduleID = :schedule_id ORDER BY TriggeredOn DESC LIMIT 10');
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$stmt->bindValue(':schedule_id', $PATH[0]);
				$test_result_result = $stmt->execute();
				$test_result_row = $test_result_result->fetchArray();

				if($test_result_row)
				{
					$main .= '<div class="pts_phoromatic_info_box_area" style="margin: 0 10%;">';
					$main .= '<ul><li><h1>Recent Triggers For This Schedule</h1></li>';

					do
					{
						$main .= '<a onclick=""><li>' . $test_result_row['Trigger'] . '<br /><table><tr><td>' . phoromatic_server::user_friendly_timedate($test_result_row['TriggeredOn']) . '</td><td><a href="?schedules/' . $PATH[0] . '/delete-trigger/' . base64_encode($test_result_row['Trigger']) . '">Remove Trigger</a></td></tr></table></li></a>';

					}
					while($test_result_row = $test_result_result->fetchArray());
					$main .= '</ul>';
					$main .= '</div>';
				}

				$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, ScheduleID, PPRID, UploadTime FROM phoromatic_results WHERE AccountID = :account_id AND ScheduleID = :schedule_id ORDER BY UploadTime DESC');
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$stmt->bindValue(':schedule_id', $PATH[0]);
				$test_result_result = $stmt->execute();
				$test_result_row = $test_result_result->fetchArray();
				$oldest_upload_time = 0;

				if($test_result_row)
				{
					$main .= '<div class="pts_phoromatic_info_box_area" style="margin: 0 10%;">';
					$main .= '<ul><li><h1>Recent Test Results For This Schedule</h1></li>';
					$results = 0;
					do
					{
						$oldest_upload_time = $test_result_row['UploadTime'];
						if($results > 100)
						{
							continue;
						}
						$main .= '<a href="?result/' . $test_result_row['PPRID'] . '"><li>' . $test_result_row['Title'] . '<br /><table><tr><td>' . phoromatic_server::system_id_to_name($test_result_row['SystemID']) . '</td><td>' . phoromatic_server::user_friendly_timedate($test_result_row['UploadTime']) .  '</td></tr></table></li></a>';
						$results++;

					}
					while($test_result_row = $test_result_result->fetchArray());
					$main .= '</ul>';
					$main .= '</div>';
				}
				$num_results = phoromatic_results_for_schedule($PATH[0]);

				if($num_results > 1)
				{
					$main .= '<p>Jump to the latest results from the past: ';
					$main .= '<select name="view_results_from_past" id="view_results_from_past" onchange="phoromatic_jump_to_results_from(\'' . $PATH[0] . '\', \'view_results_from_past\');">';
					$oldest_upload_time = !empty($oldest_upload_time) ? strtotime($oldest_upload_time) : 0;
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
						$main .= '<option value="' . $time_offset . '">' . $str_name . '</option>';
					}
					$main .= '<option value="all">All Results</option>';
					$main .= '</select>';
					$main .= '</p><hr />';
				}
				$main .= '<p><strong>' . $num_results . ' Test Results Available For This Schedule.</strong></p>';
			}

			echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in());
			echo phoromatic_webui_footer();
			return;
		}


		$main = '<h1>Test Schedules</h1>
		<p>Test schedules are used for tests that are intended to be run on a recurring basis -- either daily or other defined time period -- or whenever a trigger/event occurs, like a new Git commit to a software repository being tracked. Test schedules can be run on any given system(s)/group(s) and can be later edited.</p>';

		if(!PHOROMATIC_USER_IS_VIEWER)
		{
			$main .= '
			<hr />
			<h2>Create A Schedule</h2>
			<p><a href="?sched">Create a schedule</a> followed by adding tests/suites to run for that schedule on the selected systems.</p>';
		}

		$main .= '<hr /><h2>Current Schedules</h2>';
		$main .= '<div class="pts_phoromatic_info_box_area">
			<ul>
			<li><h1>Active Test Schedules</h1></li>';

		$stmt = phoromatic_server::$db->prepare('SELECT Title, ScheduleID, Description, RunTargetSystems, RunTargetGroups, RunAt, ActiveOn FROM phoromatic_schedules WHERE AccountID = :account_id AND State >= 1 ORDER BY Title ASC');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$result = $stmt->execute();
		$row = $result->fetchArray();

		if($row == false)
		{
			$main .= '<li class="light" style="text-align: center;">No Schedules Found</li>';
		}
		else
		{
			do
			{
				$stmt_tests = phoromatic_server::$db->prepare('SELECT COUNT(*) AS TestCount FROM phoromatic_schedules_tests WHERE AccountID = :account_id AND ScheduleID = :schedule_id ORDER BY TestProfile ASC');
				$stmt_tests->bindValue(':account_id', $_SESSION['AccountID']);
				$stmt_tests->bindValue(':schedule_id', $row['ScheduleID']);
				$result_tests = $stmt_tests->execute();
				$row_tests = $result_tests->fetchArray();
				$test_count = !empty($row_tests) ? $row_tests['TestCount'] : 0;

				$group_count = empty($row['RunTargetGroups']) ? 0 : count(explode(',', $row['RunTargetGroups']));
				$main .= '<a href="?schedules/' . $row['ScheduleID'] . '"><li>' . $row['Title'] . '<br /><table><tr><td>' . pts_strings::plural_handler(count(phoromatic_server::systems_associated_with_schedule($_SESSION['AccountID'], $row['ScheduleID'])), 'System') . '</td><td>' . pts_strings::plural_handler($group_count, 'Group') . '</td><td>' . pts_strings::plural_handler($test_count, 'Test') . '</td><td>' . pts_strings::plural_handler(phoromatic_results_for_schedule($row['ScheduleID']), 'Result') . ' Total</td><td>' . pts_strings::plural_handler(phoromatic_results_for_schedule($row['ScheduleID'], 'TODAY'), 'Result') . ' Today</td><td><strong>' . phoromatic_schedule_activeon_string($row['ActiveOn'], $row['RunAt']) . '</strong></td></tr></table></li></a>';
			}
			while($row = $result->fetchArray());
		}

		$main .= '</ul>
		</div>';

		$main .= '<hr /><h2>Schedule Overview</h2>';
		$week = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');

		foreach($week as $i => $day)
		{
			$stmt = phoromatic_server::$db->prepare('SELECT Title, ScheduleID, RunAt, RunTargetGroups, RunTargetSystems FROM phoromatic_schedules WHERE AccountID = :account_id AND State >= 1 AND ActiveOn LIKE :active_on ORDER BY RunAt,ActiveOn,Title ASC');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$stmt->bindValue(':active_on', '%' . $i . '%');
			$result = $stmt->execute();
			$has_matched = false;
			while($row = $result->fetchArray())
			{
				if(!$has_matched)
				{
					$main .= '<h3>' . $day . '</h3>' . PHP_EOL . '<p>';
					$has_matched = true;
				}
				$main .= '<em>' . $row['RunAt'] . '</em> <a href="?schedules/' . $row['ScheduleID'] . '">' . $row['Title'] . '</a>';
				//$main .= $row['RunTargetSystems'] . ' ' . $row['RunTargetGroups'];
				$main .= '<br />';
			}

			if($has_matched)
				$main .= '</p>' . PHP_EOL;
		}

		$main .= '<div class="pts_phoromatic_info_box_area">
				<ul>
				<li><h1>Deactivated Test Schedules</h1></li>';

		$stmt = phoromatic_server::$db->prepare('SELECT Title, ScheduleID, Description, RunTargetSystems, RunTargetGroups, RunAt, ActiveOn FROM phoromatic_schedules WHERE AccountID = :account_id AND State < 1 ORDER BY Title ASC');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$result = $stmt->execute();
		$row = $result->fetchArray();

		if($row == false)
		{
			$main .= '<li class="light" style="text-align: center;">No Schedules Found</li>';
		}
		else
		{
			do
			{
				$stmt_tests = phoromatic_server::$db->prepare('SELECT COUNT(*) AS TestCount FROM phoromatic_schedules_tests WHERE AccountID = :account_id AND ScheduleID = :schedule_id ORDER BY TestProfile ASC');
				$stmt_tests->bindValue(':account_id', $_SESSION['AccountID']);
				$stmt_tests->bindValue(':schedule_id', $row['ScheduleID']);
				$result_tests = $stmt_tests->execute();
				$row_tests = $result_tests->fetchArray();
				$test_count = !empty($row_tests) ? $row_tests['TestCount'] : 0;
				$group_count = empty($row['RunTargetGroups']) ? 0 : count(explode(',', $row['RunTargetGroups']));
				$main .= '<a href="?schedules/' . $row['ScheduleID'] . '"><li>' . $row['Title'] . '<br /><table><tr><td>' . pts_strings::plural_handler(count(phoromatic_server::systems_associated_with_schedule($_SESSION['AccountID'], $row['ScheduleID'])), 'System') . '</td><td>' . pts_strings::plural_handler($group_count, 'Group') . '</td><td>' . pts_strings::plural_handler($test_count, 'Test') . '</td><td>' . pts_strings::plural_handler(phoromatic_results_for_schedule($row['ScheduleID']), 'Result') . ' Total</td><td>' . pts_strings::plural_handler(phoromatic_results_for_schedule($row['ScheduleID'], 'TODAY'), 'Result') . ' Today</td><td><strong>' . phoromatic_schedule_activeon_string($row['ActiveOn'], $row['RunAt']) . '</strong></td></tr></table></li></a>';
			}
			while($row = $result->fetchArray());
		}

		$main .= '</ul>
		</div>';

		echo '<div id="pts_phoromatic_main_area">' . $main . '</div>';
		echo phoromatic_webui_footer();
	}
}

?>
