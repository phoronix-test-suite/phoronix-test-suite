<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2016 - 2022, Phoronix Media
	Copyright (C) 2016 - 2022, Michael Larabel

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

class phoromatic_testing implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Testing';
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

		$main = '<h1>Phoromatic Testing Options</h1><h2>Test Schedules</h2>
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

		$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_benchmark_tickets WHERE AccountID = :account_id AND State >= 0 AND TicketIssueTime > :time_cutoff ORDER BY TicketIssueTime DESC LIMIT 30');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$stmt->bindValue(':time_cutoff', (time() - (60 * 60 * 24 * 14)));
		$result = $stmt->execute();
		$right = '<ul><li>Benchmark Tickets</li>';

		if($result)
		{
			$main .= '<div class="pts_phoromatic_info_box_area">
				<ul>
				<li><h1>Active Benchmark Tickets</h1></li>';

			$row = $result->fetchArray();

			if(!empty($row))
			{
				do
				{
					$main .= '<a href="?benchmark/' . $row['TicketID'] . '"><li>' . $row['Title'] . '</li></a>';
				}
				while($row = $result->fetchArray());
			}
			else
			{
				$main .= '<li class="light" style="text-align: center;">No Tickets Found</li>';	
			}
		}
		$main .= '</ul>
		</div>';

		if(!PHOROMATIC_USER_IS_VIEWER)
		{
			$main .= '
			<hr />
			<h2>Run A Benchmark</h2>
			<p><a href="?benchmark">Run a benchmark</a> is the area where you can run a one-time benchmark on selected system(s) and is also where to go for setting up a stress-run benchmark.</p>
			<hr />
			<h2>Create A Suite</h2>
			<p><a href="?build_suite">Build a suite</a>, which is a collection of predefined test profiles.</p>
			<hr />
			<h2>View Local Suites</h2>
			<p><a href="?local_suites">See local suites</a> available for your benchmarking needs.</p>';
		}

		echo '<div id="pts_phoromatic_main_area">' . $main . '</div>';
		echo phoromatic_webui_footer();
	}
}

?>
