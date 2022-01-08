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
		/*
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
		*/
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

			$main .= '</ol>';

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

			$time_remaining = phoromatic_server::estimated_time_remaining_diff($row['EstimatedTimeForTask'], $row['LastCommunication']);
			if($time_remaining > 0)
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

		echo '<div id="pts_phoromatic_main_area">' . $main . '</div>';
		//echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in());
		echo phoromatic_webui_footer();
	}
}

?>
