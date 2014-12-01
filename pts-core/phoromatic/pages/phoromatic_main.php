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
		$main = '<h2 style="color: #cdcdcd;">This is the next-generation Phoromatic that achieved production-ready status with Phoronix Test Suite 5.4. All key functionality should be implemented and is ready for day-to-day use while more features and improvements are still forthcoming with Phoronix Test Suite 5.6 and future updates. Your contributions and any feedback are welcome; please keep up with the latest Git activity via <a href="https://www.github.com/phoronix-test-suite/phoronix-test-suite">GitHub</a>. We also accept <a href="http://www.phoronix-test-suite.com/?k=commercial">custom engineering work / commercial sponsorship</a> and other forms of support to continue its public, open-source development.</h2><hr />';
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
					<li>If you like Phoromatic and the Phoronix Test Suite for enterprise testing, please <a href="http://commercial.phoronix-test-suite.com/">contact us</a> for commercial support, our behind-the-firewall licensed versions of Phoromatic and OpenBenchmarking.org, custom engineering services, and other professional services. It\'s not without corporate support that we can continue to develop this leading Linux benchmarking software in our Phoronix mission of enriching the Linux hardware experience. If you run into any problems with our open-source software or would like to contribute patches, you can do so via our <a href="https://www.github.com/phoronix-test-suite/phoronix-test-suite">GitHub</a>.</li>
				</ol>';

		}


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
					$stmt_uploads = phoromatic_server::$db->prepare('SELECT UploadID FROM phoromatic_results WHERE AccountID = :account_id AND SystemID = :system_id AND ScheduleID = :schedule_id ORDER BY UploadTime DESC LIMIT 2');
					$stmt_uploads->bindValue(':account_id', $_SESSION['AccountID']);
					$stmt_uploads->bindValue(':system_id', $system_id);
					$stmt_uploads->bindValue(':schedule_id', $test_result_row['ScheduleID']);
					$result_uploads = $stmt_uploads->execute();

					$upload_ids = array();
					while($result_uploads_row = $result_uploads->fetchArray())
					{
						array_push($upload_ids, $result_uploads_row['UploadID']);
					}
					$upload_ids = array_reverse($upload_ids);

					$result_file = array();
					foreach($upload_ids as $upload_id)
					{
						$composite_xml = phoromatic_server::phoromatic_account_result_path($_SESSION['AccountID'], $upload_id) . 'composite.xml';
						if(is_file($composite_xml))
						{
							array_push($result_file, new pts_result_merge_select($composite_xml));
						}
					}

					if(count($result_file) == 2)
					{
						$writer = new pts_result_file_writer(null);
						$attributes = array();
						pts_merge::merge_test_results_process($writer, $result_file, $attributes);
						$result_file = new pts_result_file($writer->get_xml());

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

							$main .= '<a href="?result/' . implode(',', $upload_ids) . '#' . $result_object->get_comparison_hash(true, false) . '"><span style="color: ' . $pcolor . ';"><strong>' . phoromatic_system_id_to_name($system_id) . ' - ' . $result_object->test_profile->get_title() . ':</strong> ' . implode(' &gt; ', $result_file->get_system_identifiers()) . ': ' . ($vari * 100) . '%</span></a><br />';
						}
					}
				}
			}
			if($printed_schedule_name)
				$main .= '</p>';
		}
		if($has_flagged_results)
			$main .= '</span>';

		$main .= '<hr />

			<div class="pts_phoromatic_info_box_area">';

		// ACTIVE TEST SCHEDULES
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
		// TODAY'S TEST RESULTS
		$main .= '<div style="float: left; width: 50%;"><ul><li><h1>Today\'s Test Results</h1></li>';
		$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, ScheduleID, UploadID, UploadTime, TimesViewed FROM phoromatic_results WHERE AccountID = :account_id ORDER BY UploadTime DESC');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$test_result_result = $stmt->execute();

		$results_today = 0;
		while($test_result_row = $test_result_result->fetchArray())
		{
			if(substr($test_result_row['UploadTime'], 0, 10) != date('Y-m-d'))
			{
				break;
			}
			$main .= '<a href="?result/' . $test_result_row['UploadID'] . '"><li>' . $test_result_row['Title'] . '<br /><table><tr><td>' . phoromatic_system_id_to_name($test_result_row['SystemID']) . '</td><td>' . pts_strings::plural_handler($test_result_row['TimesViewed'], 'View') . '</td></tr></table></li></a>';
			$results_today++;

		}
		if($results_today == 0)
		{
			$main .= '<li class="light" style="text-align: center;">No Results Found</li>';
		}
		$main .= '</ul></div>';

		// YESTERDAY'S RESULTS
		if(false && $test_result_row && substr($test_result_row['UploadTime'], 0, 10) == date('Y-m-d', (time() - 60 * 60 * 24)))
		{
			$main .= '<div style="float: left; width: 50%;"><ul><li><h1>Yesterday\'s Test Results</h1></li>';

			do
			{
				if(substr($test_result_row['UploadTime'], 0, 10) != date('Y-m-d', (time() - 60 * 60 * 24)))
				{
					break;
				}
				$main .= '<a href="?result/' . $test_result_row['UploadID'] . '"><li>' . $test_result_row['Title'] . '<br /><table><tr><td>' . phoromatic_system_id_to_name($test_result_row['SystemID']) . '</td><td>' . pts_strings::plural_handler($test_result_row['TimesViewed'], 'View') . '</td></tr></table></li></a>';
			}
			while($test_result_row = $test_result_result->fetchArray());
			$main .= '</ul></div>';
		}

		// THIS WEEK'S RESULTS
		$one_week_ago = strtotime('-1 week');
		if(false && $test_result_row && strtotime($test_result_row['UploadTime']) > $one_week_ago)
		{
			$main .= '<div style="float: left; width: 50%;"><ul><li><h1>Other Test Results This Week</h1></li>';

			do
			{
				if(strtotime($test_result_row['UploadTime']) < $one_week_ago)
				{
					break;
				}
				$main .= '<a href="?result/' . $test_result_row['UploadID'] . '"><li>' . $test_result_row['Title'] . '<br /><table><tr><td>' . phoromatic_system_id_to_name($test_result_row['SystemID']) . '</td><td>' . phoromatic_user_friendly_timedate($test_result_row['UploadTime']) .  '</td><td>' . pts_strings::plural_handler($test_result_row['TimesViewed'], 'View') . '</td></tr></table></li></a>';
			}
			while($test_result_row = $test_result_result->fetchArray());
			$main .= '</ul></div>';
		}

		$main .= '</div>
			<div class="pts_phoromatic_info_box_area">

				<div style="float: left; width: 50%;">
					<ul>
						<li><h1>Recent System Activity</h1></li>';

		$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, CurrentTask, LastCommunication, EstimatedTimeForTask, TaskPercentComplete FROM phoromatic_systems WHERE AccountID = :account_id AND State >= 0 ORDER BY LastCommunication DESC');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$result = $stmt->execute();
		$row = $result->fetchArray();
		$sys_act = false;

		if($row)
		{
			do
			{
				if(strtotime($row['LastCommunication']) < (time() - 86400))
					break;
				if(stripos($row['CurrentTask'], 'shutdown') !== false || stripos($row['CurrentTask'], 'exit') !== false)
					continue;

				$main .= '<a href="?systems/' . $row['SystemID'] . '"><li>' . $row['Title'] . '<br /><table><tr><td>' . $row['CurrentTask'] . '</td><td><strong>' . phoromatic_compute_estimated_time_remaining_string($row['EstimatedTimeForTask'], $row['LastCommunication']) . ($row['TaskPercentComplete'] > 0 ? ' [' . $row['TaskPercentComplete'] . '% Complete]' : null) . '</strong></td><td>' . phoromatic_user_friendly_timedate($row['LastCommunication']) . '</td></tr></table></li></a>';
				$sys_act = true;
			}
			while($row = $result->fetchArray());
		}

		if(!$sys_act)
		{
			$main .= '<li class="light" style="text-align: center;">No Recent Activity</li>';
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
				$main .= '<a href="?systems/' . $row['SystemID'] . '"><li>' . $row['ErrorMessage'] . '<br /><table><tr><td>' . phoromatic_system_id_to_name($row['SystemID']) . '</td><td>' . phoromatic_user_friendly_timedate($row['UploadTime']) . '</td><td>' . $row['TestIdentifier'] . '</td></tr></table></li></a>';
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
