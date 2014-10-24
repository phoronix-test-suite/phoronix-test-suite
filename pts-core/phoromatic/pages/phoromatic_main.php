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
	public static function render_page_process($PATH)
	{
		echo phoromatic_webui_header_logged_in();
		$main = '<h2 style="">Phoromatic is under very active development right now for this version of the Phoronix Test Suite. Please keep up with the latest Git activity via <a href="https://github.com/phoronix-test-suite/phoronix-test-suite">GitHub</a>. All basic functionality should be implemented while other features are forthcoming (<em>see the TODO list for more details</em>). Your code contributions are welcome via our GitHub. We also accept <a href="http://www.phoronix-test-suite.com/?k=commercial">custom engineering work / commercial sponsorship</a> and other forms of support to continue its public, open-source development.</h2><hr />';
		$main .= '<h1>Phoromatic</h1>';

		$main .= phoromatic_systems_needing_attention();
		$main .= '<h2>Welcome</h2>
				<p>Phoromatic is the remote management and test orchestration component to the <a href="http://www.phoronix-test-suite.com/">Phoronix Test Suite</a>. Phoromatic allows you to exploit the Phoronix Test Suite\'s vast feature-set across multiple systems over the LAN/WAN, manage entire test farms of systems for benchmarking via a centralized interface, centrally collect test results, and carry out other enteprise-focused tasks.</p>';

		if(!PHOROMATIC_USER_IS_VIEWER)
		{
			$main .= '<p>To get started with your new account, the basic steps to get started include:</p>
				<ol>
					<li>Connect/sync the Phoronix Test Suite client systems (the systems to be benchmarked) to this account. In the simplest form, you just need to run the following command on the test systems: <strong>phoronix-test-suite phoromatic.connect ' . phoromatic_web_socket_server_addr() . '</strong>. For more information view the instructions on the <a href="?systems">systems page</a>.</li>
					<li>Configure your <a href="?settings">account settings</a>.</li>
					<li><a href="?schedules">Create a test schedule</a>. A schedule is for running test(s) on selected system(s) on a routine, timed basis or whenever a custom trigger is passed to the Phoromatic server. A test schedule could be for running benchmarks on a daily basis, whenever a new Git commit is applied to a code-base, or other events occurred. You can also enrich the potential by adding pre/post-test hooks for ensuring the system is set to a proper state for benchmarking.</li>
					<li>View the automatically generated <a href="?results">test results</a>.</li>
					<li>If you like Phoromatic and the Phoronix Test Suite for enterprise testing, please <a href="http://commercial.phoronix-test-suite.com/">contact us</a> for commercial support, our behind-the-firewall licensed versions of Phoromatic and OpenBenchmarking.org, custom engineering services, and other professional services. It\'s not without corporate support that we can continue to develop this leading Linux benchmarking software in our Phoronix mission of enriching the Linux hardware experience. If you run into any problems with our open-source software or would like to contribute patches, you can do so via our <a href="https://github.com/phoronix-test-suite/phoronix-test-suite">GitHub</a>.</li>
				</ol>';

		}

		$main .= '<hr />

			<div class="pts_phoromatic_info_box_area">';

		// ACTIVE TEST SCHEDULES
		$main .= '<div style="float: left; width: 50%;"><ul><li><h1>Active Test Schedules</h1></li>';
		$stmt = phoromatic_server::$db->prepare('SELECT Title, ScheduleID, Description, RunTargetSystems, RunTargetGroups FROM phoromatic_schedules WHERE AccountID = :account_id AND State >= 1 ORDER BY Title ASC');
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
				$system_count = empty($row['RunTargetSystems']) ? 0 : count(explode(',', $row['RunTargetSystems']));
				$group_count = empty($row['RunTargetGroups']) ? 0 : count(explode(',', $row['RunTargetGroups']));
				$main .= '<a href="?schedules/' . $row['ScheduleID'] . '"><li>' . $row['Title'] . '<br /><em><strong>' . $system_count . ' Systems | ' . $group_count . ' Groups | ' . phoromatic_results_for_schedule($row['ScheduleID']) . ' Results</strong> ' . $row['Description'] . ' </em></li></a>';
			}
			while($row = $result->fetchArray());
		}
		$main .= '</ul></div>';
		// TODAY'S TEST RESULTS
		$main .= '<div style="float: left; width: 50%;"><ul><li><h1>Today\'s Test Results</h1></li>';
		$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, ScheduleID, UploadID, UploadTime FROM phoromatic_results WHERE AccountID = :account_id ORDER BY UploadTime DESC');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$test_result_result = $stmt->execute();

		$results_today = 0;
		while($test_result_row = $test_result_result->fetchArray())
		{
			if(substr($test_result_row['UploadTime'], 0, 10) != date('Y-m-d'))
			{
				break;
			}
			$main .= '<a href="?result/' . $test_result_row['UploadID'] . '"><li>' . $test_result_row['Title'] . '<br /><em>' . phoromatic_system_id_to_name($test_result_row['SystemID']) . '</em></li></a>';
			$results_today++;

		}
		if($results_today == 0)
		{
			$main .= '<li class="light" style="text-align: center;">No Results Found</li>';
		}
		$main .= '</ul></div>';

		// YESTERDAY'S RESULTS
		if($test_result_row && substr($test_result_row['UploadTime'], 0, 10) == date('Y-m-d', (time() - 60 * 60 * 24)))
		{
			$main .= '<div style="float: left; width: 50%;"><ul><li><h1>Yesterday\'s Test Results</h1></li>';

			do
			{
				if(substr($test_result_row['UploadTime'], 0, 10) != date('Y-m-d', (time() - 60 * 60 * 24)))
				{
					break;
				}
				$main .= '<a href="?result/' . $test_result_row['UploadID'] . '"><li>' . $test_result_row['Title'] . '<br /><em>' . phoromatic_system_id_to_name($test_result_row['SystemID']) . '</em></li></a>';
			}
			while($test_result_row = $test_result_result->fetchArray());
			$main .= '</ul></div>';
		}

		// THIS WEEK'S RESULTS
		$one_week_ago = strtotime('-1 week');
		if($test_result_row && strtotime($test_result_row['UploadTime']) > $one_week_ago)
		{
			$main .= '<div style="float: left; width: 50%;"><ul><li><h1>Other Test Results This Week</h1></li>';

			do
			{
				if(strtotime($test_result_row['UploadTime']) < $one_week_ago)
				{
					break;
				}
				$main .= '<a href="?result/' . $test_result_row['UploadID'] . '"><li>' . $test_result_row['Title'] . '<br /><em>' . phoromatic_system_id_to_name($test_result_row['SystemID']) . ' - ' . phoromatic_user_friendly_timedate($test_result_row['UploadTime']) .  '</em></li></a>';
			}
			while($test_result_row = $test_result_result->fetchArray());
			$main .= '</ul></div>';
		}

		$main .= '</div>
			<div class="pts_phoromatic_info_box_area">

				<div style="float: left; width: 50%;">
					<ul>
						<li><h1>Recent System Activity</h1></li>';

		$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, LocalIP, CurrentTask FROM phoromatic_systems WHERE AccountID = :account_id AND State >= 0 ORDER BY LastCommunication DESC LIMIT 10');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$result = $stmt->execute();
		$row = $result->fetchArray();

		if($row == false)
		{
			$main .= '<li class="light" style="text-align: center;">No Systems Found</li>';
		}
		else
		{
			do
			{
				$main .= '<a href="?systems/' . $row['SystemID'] . '"><li>' . $row['Title'] . '<br /><em>' . $row['LocalIP'] . ' - ' . $row['CurrentTask'] . '</em></li></a>';
			}
			while($row = $result->fetchArray());
		}

		$main .= '</ul>
				</div>
				<div style="float: left; width: 50%;">
					<ul>
						<li><h1>Recent System Warnings &amp; Errors</h1></li>';

		$stmt = phoromatic_server::$db->prepare('SELECT ErrorMessage, UploadTime, SystemID, TestIdentifier FROM phoromatic_system_client_errors WHERE AccountID = :account_id ORDER BY UploadTime DESC LIMIT 10');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$result = $stmt->execute();
		$row = $result->fetchArray();

		if($row == false)
		{
			$main .= '<li class="light" style="text-align: center;">No Warnings Or Errors At This Time</li>';
		}
		else
		{
			do
			{
				$main .= '<a href="?systems/' . $row['SystemID'] . '"><li>' . $row['ErrorMessage'] . '<br /><em>' . $row['UploadTime'] . ' - ' . $row['TestIdentifier'] . '</em></li></a>';
			}
			while($row = $result->fetchArray());
		}


		$main .= '	</ul>
				</div>
			</div>';

		echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in());
		echo phoromatic_webui_footer();
	}
}

?>
