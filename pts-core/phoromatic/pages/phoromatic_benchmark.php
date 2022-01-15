<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2015 - 2022, Phoronix Media
	Copyright (C) 2015 - 2022, Michael Larabel

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

class phoromatic_benchmark implements pts_webui_interface
{
	public static function page_title()
	{
		return 'One-Time Benchmark Run';
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
		if(PHOROMATIC_USER_IS_VIEWER)
			return;

		$is_new = true;
		$e_schedule = false;
		if(!empty($PATH[0]) && $PATH[0] == 'all')
		{
			$main = '<h1>Past Benchmark Tickets</h1>';
			$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_benchmark_tickets WHERE AccountID = :account_id AND State >= 0 ORDER BY TicketIssueTime DESC');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$result = $stmt->execute();
			$main .= '<ol>';

			if($result)
			{
				$row = $result->fetchArray();

				if(!empty($row))
				{
					do
					{
						$main .= '<li><a href="?benchmark/' . $row['TicketID'] . '">' . $row['Title'] . '</a></li>';
					}
					while($row = $result->fetchArray());
				}
			}
			else
			{
				$main .= '<li>No Benchmark Tickets Found</li>';
			}

			$main .= '</ol>';
		}
		else if(!empty($PATH[0]) && is_numeric($PATH[0]))
		{
			$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_benchmark_tickets WHERE AccountID = :account_id AND TicketID = :ticket_id');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$stmt->bindValue(':ticket_id', $PATH[0]);
			$result = $stmt->execute();
			$row = $result->fetchArray();

			if(!empty($row))
			{
				if(isset($_GET['remove']) && verify_submission_token())
				{
					$stmt = phoromatic_server::$db->prepare('DELETE FROM phoromatic_benchmark_tickets WHERE AccountID = :account_id AND TicketID = :ticket_id');
					$stmt->bindValue(':account_id', $_SESSION['AccountID']);
					$stmt->bindValue(':ticket_id', $PATH[0]);
					$result = $stmt->execute();
					header('Location: /?benchmark');
				}
				else if(isset($_GET['repeat']) && verify_submission_token())
				{
					$stmt = phoromatic_server::$db->prepare('UPDATE phoromatic_benchmark_tickets SET TicketIssueTime = :new_ticket_time, State = 1 WHERE AccountID = :account_id AND TicketID = :ticket_id');
					$stmt->bindValue(':account_id', $_SESSION['AccountID']);
					$stmt->bindValue(':ticket_id', $PATH[0]);
					$stmt->bindValue(':new_ticket_time', time());
					$result = $stmt->execute();
				}
				else if(isset($_GET['disable']) && verify_submission_token())
				{
					$stmt = phoromatic_server::$db->prepare('UPDATE phoromatic_benchmark_tickets SET State = 0 WHERE AccountID = :account_id AND TicketID = :ticket_id');
					$stmt->bindValue(':account_id', $_SESSION['AccountID']);
					$stmt->bindValue(':ticket_id', $PATH[0]);
					$result = $stmt->execute();
				}

				$main = null;
				$main .= '<h1>' . $row['Title'] . '</h1>';
				$main .= '<h3>' . $row['Description'] . '</h3>';
				$main .= '<p>This benchmark ticket was created on <strong>' . date('j F Y \a\t H:i', strtotime($row['LastModifiedOn'])) . '</strong> by <strong>' . $row['LastModifiedBy'] . '. The ticket was last issued for testing at ' . date('j F Y \a\t H:i', $row['TicketIssueTime']) . '</strong>.';
				$main .= '<p> <a href="/?benchmark/' . $PATH[0] . '/&repeat' . append_token_to_url('') . '">Repeat Ticket</a> &nbsp; &nbsp; &nbsp; <a href="/?benchmark/' . $PATH[0] . '/&remove' . append_token_to_url('') . '">Remove Ticket</a>' . (!isset($_GET['disable']) && $row['State'] > 0 ? ' &nbsp; &nbsp; &nbsp; <a href="/?benchmark/' . $PATH[0] . '/&disable' . append_token_to_url('') . '">End Ticket</a>' : null) . '</p>';

				if(!empty($row['RunTargetSystems']))
				{
					$main .= '<hr /><h1>System Targets</h1><ol>';
					foreach(explode(',', $row['RunTargetSystems']) as $system_id)
					{
						$main .= '<li><a href="?systems/' . $system_id . '">' . phoromatic_server::system_id_to_name($system_id) . '</a></li>';
					}
				}
				if(!empty($row['RunTargetGroups']))
				{
					$main .= '<hr /><h1>Group Targets</h1><ol>';
					foreach(explode(',', $row['RunTargetGroups']) as $group)
					{
						if(empty($group))
							continue;

						$main .= '<li><strong style="font-weight: 800;">' . $group . '</strong></li>';

						$stmt = phoromatic_server::$db->prepare('SELECT SystemID FROM phoromatic_systems WHERE AccountID = :account_id AND Groups LIKE :sgroup AND State > 0 ORDER BY Title ASC');
						$stmt->bindValue(':account_id', $_SESSION['AccountID']);
						$stmt->bindValue(':sgroup', '%#' . $group . '#%');
						$result = $stmt->execute();

						while($result && $row = $result->fetchArray())
						{
							$main .= '<li><a href="?systems/' . $row['SystemID'] . '">' . phoromatic_server::system_id_to_name($row['SystemID']) . '</a></li>';
						}
					}
				}

				$main .= '</ol>';
				if(!empty($row['EnvironmentVariables']))
				{
					$main .= '<hr /><h1>Environment</h1><ol>';

					foreach(explode(';', $row['EnvironmentVariables']) as $env)
					{
						$main .= '<li><strong>' . $env . '</strong></li>';
					}
					$main .= '</ol>';
				}
				$main .= '<hr /><h1>Ticket Payload</h1>';
				$main .= '<p>This ticket runs the <strong>' . $row['SuiteToRun'] . '</strong> test suite:</p>';
				$main .= '<div style="max-height: 400px; overflow-y: scroll;">';
				$xml_path = phoromatic_server::find_suite_file($_SESSION['AccountID'], $row['SuiteToRun']);
				if(is_file($xml_path))
				{
					$test_suite = new pts_test_suite($xml_path);

				//	$main .= '<h2>' . $test_suite->get_title() . '</h2>';
				//	$main .= '<p><strong>' . $test_suite->get_maintainer() . '</strong></p>';
				//	$main .= '<p><em>' . $test_suite->get_description() . '</em></p>';

					foreach($test_suite->get_contained_test_result_objects() as $tro)
					{
						$main .= '<h3>' . $tro->test_profile->get_title() . ' [' . $tro->test_profile->get_identifier() . ']</h3>';
						$main .= '<p>' . $tro->get_arguments_description() . '</p>';
					}

					//$main .= '<hr />';
				}

				$main .= '</div><hr />';
				$main .= '<div class="pts_phoromatic_info_box_area">';
				if(strpos($row['EnvironmentVariables'], 'PTS_CONCURRENT_TEST_RUNS') !== false)
				{
					if(isset($_REQUEST['view_log']) && is_file(phoromatic_server::phoromatic_account_stress_log_path($_SESSION['AccountID'], $PATH[0]) . $_REQUEST['view_log'] . '.log'))
					{
						$main .= '<hr /><h1>Stress Log For: ' . phoromatic_server::system_id_to_name($_REQUEST['view_log']) . '</h1>';
						$log_text = PHP_EOL . file_get_contents(phoromatic_server::phoromatic_account_stress_log_path($_SESSION['AccountID'], $PATH[0]) . $_REQUEST['view_log'] . '.log');

						$x = 0;
						while(($x = strpos($log_text, "\n##", $x)) !== false)
						{
							$log_text = substr($log_text, 0, $x) . "\n<strong style=\"font-weight: 800;\">" . substr($log_text, $x + 1);

							if(($y = strpos($log_text, "\n", $x + 2)) !== false)
							{
								$log_text = substr($log_text, 0, $y) . '</strong>' . substr($log_text, $y);
							}
							$x = $y;
						}

						$x = 0;
						while(($x = strpos($log_text, "\n[", $x)) !== false)
						{
							$log_text = substr($log_text, 0, $x) . "\n<strong style=\"font-weight: 800;\">" . substr($log_text, $x + 1);

							if(($y = strpos($log_text, "]", $x + 2)) !== false)
							{
								$log_text = substr($log_text, 0, $y) . '</strong>' . substr($log_text, $y);
							}
							$x = $y;
						}
						$main .= '<blockquote>' . str_replace("\n", '<br />', $log_text) . '</blockquote>';
						$main .= '<p><a href="?benchmark/' . $PATH[0] . '#stress_logs">View Other System Logs</a></p>';
					}
					else
					{
						$main .= '<a name="stress_logs"></a><hr /><h1>Stress Run Logs</h1><ol>';
						$count = 0;
						foreach(pts_file_io::glob(phoromatic_server::phoromatic_account_stress_log_path($_SESSION['AccountID'], $PATH[0]) . '*.log') as $log_file)
						{
							$sys_id = basename($log_file, '.log');
							$main .= '<li><a href="?benchmark/' . $PATH[0] . '/&view_log=' . $sys_id . '">' . phoromatic_server::system_id_to_name($sys_id) . '</a></li>';
							$count++;
						}
						if($count == 0)
						{
							$main .= '<li><em>No Logs Currently Available</em></li>';
						}
						$main .= '</ol>';
					}
				}
				else
				{
					$main .= '<div style="margin: 0 5%;"><ul style="max-height: 100%;"><li><h1>Test Results</h1></li>';
					$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, ScheduleID, PPRID, UploadTime, TimesViewed FROM phoromatic_results WHERE AccountID = :account_id AND BenchmarkTicketID = :ticket_id ORDER BY UploadTime DESC');
					$stmt->bindValue(':account_id', $_SESSION['AccountID']);
					$stmt->bindValue(':ticket_id', $PATH[0]);
					$test_result_result = $stmt->execute();
					$results = 0;
					while($test_result_row = $test_result_result->fetchArray())
					{
						$main .= '<a onclick=""><li id="result_select_' . $test_result_row['PPRID'] . '"><input type="checkbox" id="result_compare_checkbox_' . $test_result_row['PPRID'] . '" onclick="javascript:phoromatic_checkbox_toggle_result_comparison(\'' . $test_result_row['PPRID'] . '\');" onchange="return false;"></input> <span onclick="javascript:phoromatic_window_redirect(\'?result/' . $test_result_row['PPRID'] . '\');">' . $test_result_row['Title'] . '</span><br /><table><tr><td>' . phoromatic_server::system_id_to_name($test_result_row['SystemID']) . '</td><td>' . phoromatic_server::user_friendly_timedate($test_result_row['UploadTime']) .  '</td><td>' . $test_result_row['TimesViewed'] . ' Times Viewed</td></table></li></a>';
						$results++;

					}
					if($results == 0)
					{
						$main .= '<li class="light" style="text-align: center;">No Results Found</li>';
					}
					else if($results > 3)
					{
						$main .= '<a onclick=""><li id="global_bottom_totals"><input type="checkbox" id="global_checkbox" onclick="javascript:phoromatic_toggle_checkboxes_on_page(this);" onchange="return false;"></input> <strong>' . $results . ' Results</strong></li></a>';
					}
					$main .= '</ul></div>';
					$main .= '</div>';
				}
			}
		}
		else
		{
			if(isset($_POST['benchmark_title']) && !empty($_POST['benchmark_title']))
			{
				$title = phoromatic_get_posted_var('benchmark_title');
				$description = phoromatic_get_posted_var('benchmark_description');
				$result_identifier = phoromatic_get_posted_var('benchmark_identifier');
				$suite_to_run = phoromatic_get_posted_var('suite_to_run');

				if(strlen($title) < 3)
				{
					echo '<h2>Title must be at least three characters.</h2>';
					exit;
				}
				if(strlen($result_identifier) < 3)
				{
					echo '<h2>Identifier must be at least three characters.</h2>';
					exit;
				}
				if(strlen($suite_to_run) < 3)
				{
					echo '<h2>You must specify a suite to run.</h2>';
					exit;
				}

				$run_target_systems = phoromatic_get_posted_var('run_on_systems', array());
				$run_target_groups = phoromatic_get_posted_var('run_on_groups', array());
				if(!is_array($run_target_systems)) $run_target_systems = array();
				if(!is_array($run_target_groups)) $run_target_groups = array();
				$run_target_systems = implode(',', $run_target_systems);
				$run_target_groups = implode(',', $run_target_groups);

				if($is_new)
				{
					do
					{
						$ticket_id = rand(10, 999999);
						$matching_tickets = phoromatic_server::$db->querySingle('SELECT TicketID FROM phoromatic_benchmark_tickets WHERE TicketID = \'' . $ticket_id . '\'');
					}
					while(!empty($matching_tickets));
				}

				$env_vars = array();

				if(is_numeric($_POST['PTS_CONCURRENT_TEST_RUNS']) && $_POST['PTS_CONCURRENT_TEST_RUNS'] > 0)
				{
					array_push($env_vars, 'PTS_CONCURRENT_TEST_RUNS=' . $_POST['PTS_CONCURRENT_TEST_RUNS']);
				}
				if(is_numeric($_POST['TOTAL_LOOP_TIME']) && $_POST['TOTAL_LOOP_TIME'] > 0)
				{
					array_push($env_vars, 'TOTAL_LOOP_TIME=' . $_POST['TOTAL_LOOP_TIME']);
				}

				foreach(pts_env::get_posted_options('phoromatic') as $ei => $ev)
				{
					array_push($env_vars, $ei . '=' . $ev);
				}

				$env_vars = implode(';', $env_vars);

				// Add benchmark
				$stmt = phoromatic_server::$db->prepare('INSERT OR REPLACE INTO phoromatic_benchmark_tickets (AccountID, TicketID, TicketIssueTime, Title, ResultIdentifier, SuiteToRun, Description, State, LastModifiedBy, LastModifiedOn, RunTargetGroups, RunTargetSystems, EnvironmentVariables) VALUES (:account_id, :ticket_id, :ticket_time, :title, :result_identifier, :suite_to_run, :description, :state, :modified_by, :modified_on, :run_target_groups, :run_target_systems, :environment_variables)');
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$stmt->bindValue(':ticket_id', $ticket_id);
				$stmt->bindValue(':ticket_time', time());
				$stmt->bindValue(':title', $title);
				$stmt->bindValue(':result_identifier', $result_identifier);
				$stmt->bindValue(':suite_to_run', $suite_to_run);
				$stmt->bindValue(':description', $description);
				$stmt->bindValue(':state', 1);
				$stmt->bindValue(':modified_by', $_SESSION['UserName']);
				$stmt->bindValue(':modified_on', phoromatic_server::current_time());
				$stmt->bindValue(':public_key', null); //  isset($public_key) ? $public_key :
				$stmt->bindValue(':run_target_groups', $run_target_groups);
				$stmt->bindValue(':run_target_systems', $run_target_systems);
				$stmt->bindValue(':environment_variables', $env_vars);
				$result = $stmt->execute();
				phoromatic_add_activity_stream_event('benchmark', $ticket_id, ($is_new ? 'added' : 'modified'));

				if($result)
				{
					header('Location: ?benchmark/' . $ticket_id);
				}
			}

			$main = '<h2>' . ($is_new ? 'Create' : 'Edit') . ' A Benchmark</h2>
			<p>This page allows you to run a test suite -- consisting of a single or multiple test suites -- on a given set/group of systems right away at their next earliest possibility. This benchmark mode is an alternative to the <a href="?schedules">benchmark schedules</a> for reptitive/routine testing.</p>';
			$local_suites = array();
			foreach(pts_file_io::glob(phoromatic_server::phoromatic_account_suite_path($_SESSION['AccountID']) . '*/suite-definition.xml') as $xml_path)
			{
					$id = basename(dirname($xml_path));
					$test_suite = new pts_test_suite($xml_path);
					$local_suites[$test_suite->get_title() . ' - ' . $id] = $id;
			}
			$official_suites = pts_test_suites::suites_on_disk(false, true);

			if(empty($local_suites))
			{
				$main .= '<p><strong>Before you can create a benchmark ticket you must first <a href="?build_suite">create a test suite</a> with the tests you wish to run.</strong></p>';
			}
			else
			{
				$main .= '<form action="' . $_SERVER['REQUEST_URI'] . '" name="run_benchmark" id="run_benchmark" method="post" enctype="multipart/form-data" onsubmit="return validate_run_benchmark();">
				<h3>Title:</h3>
				<p>The title is the name of the result file for this test run.</p>
				<p><input type="text" name="benchmark_title" value="' . (!$is_new ? $e_schedule['Title'] : null) . '" /></p>
				<h3>Test Run Identifier:</h3>
				<p>The test run identifier is the per-system name for the system(s) being benchmarked. The following variables may be used: <strong>.SYSTEM</strong>, <strong>.GROUP</strong>. Any custom per-user system variables set via the individual system pages can also be used.</p>
				<p><input type="text" name="benchmark_identifier" value="' . (!$is_new ? $e_schedule['Identifier'] : null) . '" /></p>
				<h3>Test Suite To Run:</h3>
				<p><a href="?build_suite">Build a suite</a> to add/select more tests to run or <a href="?local_suites">view local suites</a> for more information on a particular suite. A test suite is a set of test profiles to run in a pre-defined manner.</p>';
				$main .= '<p><select name="suite_to_run" id="suite_to_run_identifier" onchange="phoromatic_show_basic_suite_details(\'\');">';
				foreach(array_merge($local_suites, $official_suites) as $title => $id)
				{
					$main .= '<option value="' . $id . '">' . $title . '</option>';
				}
				$main .= '</select></p>';
				$main .= '<p><div id="suite_details" style="background: #efefef;"></div></p>';
				$main .= '<h3>Description:</h3>
				<p>The description is an optional way to add more details about the intent or objective of this test run.</p>
				<p><textarea name="benchmark_description" id="benchmark_description" cols="50" rows="3">' . (!$is_new ? $e_schedule['Description'] : null) . '</textarea></p>
				<hr /><h3>System Targets:</h3>
				<p>Select the systems that should be benchmarked at their next earliest convenience.</p>
				<p style="white-space: nowrap;">';

				$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID FROM phoromatic_systems WHERE AccountID = :account_id AND State >= 0 ORDER BY Title ASC');
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$result = $stmt->execute();

				if(!$is_new)
				{
					$e_schedule['RunTargetSystems'] = explode(',', $e_schedule['RunTargetSystems']);
					$e_schedule['RunTargetGroups'] = explode(',', $e_schedule['RunTargetGroups']);
				}

				if($row = $result->fetchArray())
				{
					$main .= '<h4>Systems: ';
					do
					{
						$main .= '<input type="checkbox" name="run_on_systems[]" value="' . $row['SystemID'] . '" ' . (!$is_new && in_array($row['SystemID'], $e_schedule['RunTargetSystems']) ? 'checked="checked" ' : null) . '/> ' . $row['Title'] . ' ';
					}
					while($row = $result->fetchArray());
					$main .= '</h4>';
				}

				$stmt = phoromatic_server::$db->prepare('SELECT GroupName FROM phoromatic_groups WHERE AccountID = :account_id ORDER BY GroupName ASC');
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$result = $stmt->execute();

				if($row = $result->fetchArray())
				{
					$main .= '<h4>Groups: ';
					do
					{
						$main .= '<input type="checkbox" name="run_on_groups[]" value="' . $row['GroupName'] . '" ' . (!$is_new && in_array($row['GroupName'], $e_schedule['RunTargetGroups']) ? 'checked="checked" ' : null) . '/> ' . $row['GroupName'] . ' ';
					}
					while($row = $result->fetchArray());
					$main .= '</h4>';
				}

				$main .= '</p>
				<hr /><h3>Environment Options</h3>
				<h4>Stress Testing</h4>
				<p>If you wish to test systems for stability/reliability rather than performance, use this option and specify the number of tests to run concurrently (two or more) and (optionally) for the total period of time to continue looping the benchmarks. These options are intended to just stress the system and will not record any benchmark results. From the command-line this testing mode can be used via the <em>phoronix-test-suite stress-run</em> sub-command.</p>
				<p><strong>Concurrent Number Of Test Processes:</strong> <select name="PTS_CONCURRENT_TEST_RUNS"><option value="0">Disabled</option>';
				for($i = 2; $i <= 24; $i++)
				{
					$main .= '<option value="' . $i . '">' . $i . '</option>';
				}
				$main .= '</select></p>
				<p><strong>Force Loop Time:</strong> <select name="TOTAL_LOOP_TIME"><option value="0">Disabled</option>';
				$s = true;
				for($i = 5; $i < 60; $i += 5)
				{
					if($i > 15 && $i % 10 != 0)
					{
						continue;
					}
					$main .= '<option value="' . $i . '">' . pts_strings::format_time($i, 'MINUTES') . '</option>';
				}
				for($i = 60; $i <= (30 * 24 * 60); $i += 60)
				{
					if($i > 10080)
					{
						// 7 days
						if(($i % 1440) != 0)
							continue;
					}
					else if($i > 480)
					{
						$s = !$s;
						if(!$s)
							continue;
					}

					$main .= '<option value="' . $i . '">' . pts_strings::format_time($i, 'MINUTES') . '</option>';
				}
				$main .= '</select></p>';
				$main .= '<p><a id="env_var_options_show" onclick="javascript:document.getElementById(\'env_var_options\').style.display = \'block\'; javascript:document.getElementById(\'env_var_options_show\').style.display = \'none\'; ">Advanced Options</a></p> <div id="env_var_options" style="display: none;"><p>The advanced options require the Phoromatic clients be on the latest Phoronix Test Suite (10.8 or newer / Git). See the Phoronix Test Suite documentation for more information on these environment variables / advanced options.</p>' . pts_env::get_html_options('phoromatic') . '</div>';

				$main .= '<hr /><p align="left"><input name="submit" value="' . ($is_new ? 'Run' : 'Edit') . ' Benchmark" type="submit" onclick="return pts_rmm_validate_schedule();" /></p>
					</form>';
			}
		}

		$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_benchmark_tickets WHERE AccountID = :account_id AND State >= 0 AND TicketIssueTime > :time_cutoff ORDER BY TicketIssueTime DESC LIMIT 30');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$stmt->bindValue(':time_cutoff', (time() - (60 * 60 * 24 * 14)));
		$result = $stmt->execute();
		$right = '<ul><li>Benchmark Tickets</li>';

		if($result)
		{
			$row = $result->fetchArray();

			if(!empty($row))
			{
				do
				{
					$right .= '<li><a href="?benchmark/' . $row['TicketID'] . '">' . $row['Title'] . '</a></li>';
				}
				while($row = $result->fetchArray());
			}
		}
		$right .= '<li><em><a href="?benchmark/all">View All Past Tickets</a></em></li>';
		$right .= '</ul>';

		echo phoromatic_webui_header_logged_in();
		echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in($right));
		echo phoromatic_webui_footer();
	}
}

?>
