<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2015, Phoronix Media
	Copyright (C) 2008 - 2015, Michael Larabel

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


class phoromatic_main implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Main';
	}
	public static function page_header()
	{
		return null;
	}
	public static function preload($PAGE)
	{
		return true;
	}
	protected static function result_match($schedule_id, $system_id, $date)
	{
		$stmt = phoromatic_server::$db->prepare('SELECT PPRID FROM phoromatic_results WHERE AccountID = :account_id AND ScheduleID = :schedule_id AND SystemID = :system_id AND Trigger = :trigger LIMIT 1');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$stmt->bindValue(':schedule_id', $schedule_id);
		$stmt->bindValue(':system_id', $system_id);
		$stmt->bindValue(':trigger', $date);
		$result = $stmt->execute();
		return $result && ($row = $result->fetchArray()) ? $row['PPRID'] : false;
	}
	protected static function system_info($system_id, $info = '*')
	{
		$stmt = phoromatic_server::$db->prepare('SELECT ' . $info . ' FROM phoromatic_systems WHERE AccountID = :account_id AND SystemID = :system_id LIMIT 1');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$stmt->bindValue(':system_id', $system_id);
		$result = $stmt->execute();
		return $result && ($row = $result->fetchArray()) ? $row : false;
	}
	public static function render_page_process($PATH)
	{
		echo phoromatic_webui_header_logged_in();
		$main = null;
		if(pts_network::internet_support_available())
		{
			// Check For pts-core updates
			$latest_reported_version = pts_network::http_get_contents('http://www.phoronix-test-suite.com/LATEST_CORE');
			if(is_numeric($latest_reported_version) && $latest_reported_version > PTS_CORE_VERSION)
			{
					// New version of PTS is available
				$main .= '<p style="font-weight: 600; color: #ccc;">An outdated version of the Phoronix Test Suite / Phoromatic is currently installed.' . PHP_EOL . 'The version in use is v' . PTS_VERSION . ' (v' . PTS_CORE_VERSION . '), but the latest is pts-core v' . $latest_reported_version . '. Visit <a href="http://www.phoronix-test-suite.com/">Phoronix-Test-Suite.com</a> to update this software.</strong>';
			}
		}
		$main .= '<h1>Phoromatic</h1>';

		$main .= phoromatic_systems_needing_attention();

		$main_page_message = phoromatic_server::read_setting('main_page_message');
		if(!PHOROMATIC_USER_IS_VIEWER)
		{
			$main .= '<p>To get started with your new account, the basic steps to get started include:</p>
				<ol>
					<li>Connect/sync the Phoronix Test Suite client systems (the systems to be benchmarked) to this account. In the simplest form, you just need to run the following command on the test systems: <strong style="font-weight: 800;">phoronix-test-suite phoromatic.connect ' . phoromatic_web_socket_server_addr() . '</strong>. For more information view the instructions on the <a href="?systems">systems page</a>.</li>
					<li>Configure your <a href="?settings">account settings</a>.</li>
					<li><a href="?schedules">Create a test schedule</a>. A schedule is for running test(s) on selected system(s) on a routine, timed basis or whenever a custom trigger is passed to the Phoromatic server. A test schedule could be for running benchmarks on a daily basis, whenever a new Git commit is applied to a code-base, or other events occurred. You can also enrich the potential by adding pre/post-test hooks for ensuring the system is set to a proper state for benchmarking. Alternatively, you can <a href="?benchmark">create a benchmark ticket</a> for one-time testing on one or more systems.</li>
					<li>View the automatically generated <a href="?results">test results</a>.</li>';

			if(!empty($main_page_message))
				$main .= '<li><strong>' . $main_page_message . '</strong></li>';
			else
				$main .= '<li><strong>If you are interested in Phoromatic and the Phoronix Test Suite for enterprise testing, please <a href="http://commercial.phoronix-test-suite.com/">contact us</a> for commercial support, custom test development, custom engineering services, and other professional services. It\'s not without corporate support and sponsorship that we can continue to develop this leading open-source Linux benchmarking software. If you run into any problems with our open-source software or would like to contribute patches, you can do so via our <a href="https://www.github.com/phoronix-test-suite/phoronix-test-suite">GitHub project</a>.</strong></li>
				</ol>';

		}
		else if(!empty($main_page_message))
		{
			$main .= '<p><strong>' . $main_page_message . '</strong></p>';
		}

		$main .= '<hr /><div id="phoromatic_fixed_main_table">';

		$systems_needing_attention = phoromatic_server::systems_appearing_down($_SESSION['AccountID']);
		$systems_idling = phoromatic_server::systems_idling($_SESSION['AccountID']);
		$systems_shutdown = phoromatic_server::systems_shutdown($_SESSION['AccountID']);
		$systems_running_tests = phoromatic_server::systems_running_tests($_SESSION['AccountID']);

		$main .= '<div id="phoromatic_main_table_cell">
			<h2>' . pts_strings::plural_handler(count($systems_running_tests), 'System') . ' Running Tests</h2>
			<h2>' . pts_strings::plural_handler(count($systems_idling), 'System') . ' Idling</h2>
			<h2>' . pts_strings::plural_handler(count($systems_shutdown), 'System') . ' Shutdown</h2>
			<h2>' . pts_strings::plural_handler(count($systems_needing_attention), 'System') . ' Needing Attention</h2>';
		$main .= '<hr /><h2>Systems Running Tests</h2>';

		$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_systems WHERE AccountID = :account_id AND State >= 0 AND CurrentTask NOT LIKE \'%Idling%\' AND CurrentTask NOT LIKE \'%Shutdown%\' ORDER BY LastCommunication DESC');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$result = $stmt->execute();

		while($result && $row = $result->fetchArray())
		{
			$main .= '<div class="phoromatic_overview_box">';
			$main .= '<h1><a href="?systems/' . $row['SystemID'] . '">' . $row['Title'] . '</a></h1>';
			$main .= $row['CurrentTask'] . '<br />';

			if(!empty($row['CurrentProcessSchedule']))
			{
				$main .= '<a href="?schedules/' . $row['CurrentProcessSchedule'] . '">' . phoromatic_server::schedule_id_to_name($row['CurrentProcessSchedule']) . '</a><br />';
			}

			if(!empty($row['CurrentProcessSchedule']))
			{
				$main .= ' - <a href="/?schedules/' . $row['CurrentProcessSchedule'] . '">' . phoromatic_server::schedule_id_to_name($row['CurrentProcessSchedule']) . '</a><br />';
			}
			else if(!empty($row['CurrentProcessTicket']))
			{
				$main .= '   <a href="/?benchmark/' . $row['CurrentProcessTicket'] . '/&view_log=' . $row['SystemID'] . '">' . phoromatic_server::ticket_id_to_name($row['CurrentProcessTicket']) . '</a><br />';
			}

			$time_remaining = phoromatic_compute_estimated_time_remaining($row['EstimatedTimeForTask'], $row['LastCommunication']);
			if($time_remaining)
			{
				$main .= '<em>~ ' . pts_strings::plural_handler($time_remaining, 'Minute') . ' Remaining</em>';
			}
			$main .= '</div>';
		}
		$main .= '</div>';

		$results_today = phoromatic_server::test_results($_SESSION['AccountID'], strtotime('today'));
		$results_total = phoromatic_server::test_results_benchmark_count($_SESSION['AccountID']);
		$schedules_today = phoromatic_server::schedules_today($_SESSION['AccountID']);
		$schedules_total = phoromatic_server::schedules_total($_SESSION['AccountID']);
		$benchmark_tickets_today = phoromatic_server::benchmark_tickets_today($_SESSION['AccountID']);
		$main .= '<div id="phoromatic_main_table_cell">
		<h2>' . pts_strings::plural_handler(count($schedules_today), 'Schedule') . ' Active Today</h2>
		<h2>' . pts_strings::plural_handler(count($schedules_total), 'Schedule') . ' In Total</h2>
		<h2>' . pts_strings::plural_handler(count($benchmark_tickets_today), 'Active Benchmark Ticket') . '</h2>
		<h2>' . pts_strings::plural_handler(count($results_today), 'Test Result') . ' Today / ' . pts_strings::plural_handler($results_total, 'Benchmark Result') . ' Total</h2>';
		$main .= '<hr /><h2>Today\'s Scheduled Tests</h2>';

		foreach($schedules_today as &$row)
		{
			$systems_for_schedule = phoromatic_server::systems_associated_with_schedule($_SESSION['AccountID'], $row['ScheduleID']);

			$extra_css = null;
			if(empty($systems_for_schedule))
			{
				$extra_css = ' opacity: 0.4;';
			}

			list($h, $m) = explode('.', $row['RunAt']);

			$main .= '<div style="' . $extra_css . '" class="phoromatic_overview_box">';
			$main .= '<h1><a href="?schedules/' . $row['ScheduleID'] . '">' . $row['Title'] . '</a></h1>';

			if(!empty($systems_for_schedule))
			{
				if($row['RunAt'] > date('H.i'))
				{
					$run_in_future = true;
					$main .= '<h3>Runs In ' . pts_strings::format_time((($h * 60) + $m) - ((date('H') * 60) + date('i')), 'MINUTES') . '</h3>';
				}
				else
				{
					$run_in_future = false;
					$main .= '<h3>Triggered ' . pts_strings::format_time(max(1, (date('H') * 60) + date('i') - (($h * 60) + $m)), 'MINUTES') . ' Ago</h3>';
				}
			}

			foreach($systems_for_schedule as $system_id)
			{
				$pprid = self::result_match($row['ScheduleID'], $system_id, date('Y-m-d'));

				if($pprid)
					$main .= '<a href="?result/' . $pprid . '">';

				$main .= phoromatic_server::system_id_to_name($system_id);

				if($pprid)
					$main .= '</a>';
				else if(!$run_in_future)
				{
					$sys_info = self::system_info($system_id);
					$last_comm_diff = time() - strtotime($sys_info['LastCommunication']);

					$main .= ' <sup><a href="?systems/' . $system_id . '">';
					if($last_comm_diff > 3600)
					{
						$main .= '<strong>Last Communication: ' . pts_strings::format_time($last_comm_diff, 'SECONDS', true, 60) . ' Ago</strong>';
					}
					else
					{
						$main .= $sys_info['CurrentTask'];
					}
					$main .= '</a></sup>';
				}
				$main .= '<br />';
			}

			$main .= '</div>';
		}
		$main .= '</div>';

		$main .= '</div>';

/*
		$has_flagged_results = false;
		$stmt = phoromatic_server::$db->prepare('SELECT ScheduleID, GROUP_CONCAT(SystemID,\',\') AS Systems FROM phoromatic_results WHERE AccountID = :account_id AND ScheduleID NOT LIKE 0 GROUP BY ScheduleID ORDER BY UploadTime DESC');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$test_result_result = $stmt->execute();
		while($test_result_row = $test_result_result->fetchArray())
		{
			$systems = array_count_values(explode(',', $test_result_row['Systems']));

			foreach($systems as $system_id => $system_count)
			{
				if($system_count < 2)
					unset($systems[$system_id]);
			}

			$printed_schedule_name = false;
			if(!empty($systems))
			{
				foreach(array_keys($systems) as $system_id)
				{
					$stmt_uploads = phoromatic_server::$db->prepare('SELECT PPRID, UploadID FROM phoromatic_results WHERE AccountID = :account_id AND SystemID = :system_id AND ScheduleID = :schedule_id ORDER BY UploadTime DESC LIMIT 2');
					$stmt_uploads->bindValue(':account_id', $_SESSION['AccountID']);
					$stmt_uploads->bindValue(':system_id', $system_id);
					$stmt_uploads->bindValue(':schedule_id', $test_result_row['ScheduleID']);
					$result_uploads = $stmt_uploads->execute();

					$result_file = array();
					$pprids = array();
					while($result_uploads_row = $result_uploads->fetchArray())
					{
						$composite_xml = phoromatic_server::phoromatic_account_result_path($_SESSION['AccountID'], $result_uploads_row['UploadID']) . 'composite.xml';
						if(is_file($composite_xml))
						{
							array_push($result_file, new pts_result_merge_select($composite_xml));
						}
						array_push($pprids, $result_uploads_row['PPRID']);
					}
					$result_file = array_reverse($result_file);

					if(count($result_file) == 2)
					{
						$attributes = array();
						$result_file = new pts_result_file(array_shift($result_files), true);

						if(!empty($result_files))
						{
							$result_file->merge($result_files, $attributes);
						}

						foreach($result_file->get_result_objects('ONLY_CHANGED_RESULTS') as $i => $result_object)
						{
							$vari = round($result_object->largest_result_variation(), 3);
							if(abs($vari) < 0.03)
								continue;
							if(!$has_flagged_results)
							{
								$main .= '<hr /><h2>Flagged Results</h2>';
								$main .= '<p>Displayed are results for each system of each scheduled test where there is a measurable change (currently set to a 0.1% threshold) when comparing the most recent result to the previous result for that system for that test schedule. Click on the change to jump to that individualized result file comparison.</p>';
								$main .= '<span style="font-size: 80%;">';
								$has_flagged_results = true;
							}
							if(!$printed_schedule_name)
							{
								$main .= '<h3>' . phoromatic_schedule_id_to_name($test_result_row['ScheduleID']) . '</h3><p>';
								$printed_schedule_name = true;
							}

							$pcolor = $vari > 0 ? 'green' : 'red';

							$main .= '<a href="?result/' . implode(',', $pprids) . '#' . $result_object->get_comparison_hash(true, false) . '"><span style="color: ' . $pcolor . ';"><strong>' . phoromatic_system_id_to_name($system_id) . ' - ' . $result_object->test_profile->get_title() . ':</strong> ' . implode(' &gt; ', $result_file->get_system_identifiers()) . ': ' . ($vari * 100) . '%</span></a><br />';
						}
					}
				}
			}
			if($printed_schedule_name)
				$main .= '</p>';
		}
		if($has_flagged_results)
			$main .= '</span>';
*/

		// ACTIVE TEST SCHEDULES
		/*
		$main .= '<div style="float: left; width: 50%;"><ul><li><h1>Active Test Schedules</h1></li>';
		$stmt = phoromatic_server::$db->prepare('SELECT Title, ScheduleID, Description, RunTargetSystems, RunTargetGroups, ActiveOn, RunAt FROM phoromatic_schedules WHERE AccountID = :account_id AND State >= 1 ORDER BY Title ASC');
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
				$group_count = empty($row['RunTargetGroups']) ? 0 : count(explode(',', $row['RunTargetGroups']));
				$main .= '<a href="?schedules/' . $row['ScheduleID'] . '"><li>' . $row['Title'] . '<br /><table><tr><td>' . pts_strings::plural_handler(count(phoromatic_server::systems_associated_with_schedule($_SESSION['AccountID'], $row['ScheduleID'])), 'System') . '</td><td>' . pts_strings::plural_handler($group_count, 'Group') . '</td><td>' . pts_strings::plural_handler(phoromatic_results_for_schedule($row['ScheduleID']), 'Result') . '</td><td><strong>' . phoromatic_schedule_activeon_string($row['ActiveOn'], $row['RunAt']) . '</strong></td></tr></table></li></a>';
			}
			while($row = $result->fetchArray());
		}
		$main .= '</ul></div>';
		*/


		echo '<div id="pts_phoromatic_main_area">' . $main . '</div>';
		//echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in());
		echo phoromatic_webui_footer();
	}
}

?>
