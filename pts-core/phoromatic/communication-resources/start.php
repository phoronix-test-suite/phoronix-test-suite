<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2015, Phoronix Media
	Copyright (C) 2009 - 2015, Michael Larabel

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

if(SYSTEM_IN_MAINTENANCE_MODE)
{
	$json['phoromatic']['task'] = 'maintenance';
	//$json['phoromatic']['response'] = '[' . date('H:i:s') . '] System in maintenance mode.';
	echo json_encode($json);
	return;
}

$day_of_week_int = date('N') - 1;

$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_account_settings WHERE AccountID = :account_id');
$stmt->bindValue(':account_id', ACCOUNT_ID);
$result = $stmt->execute();
$phoromatic_account_settings = $result->fetchArray(SQLITE3_ASSOC);
unset($phoromatic_account_settings['AccountID']);

$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_schedules WHERE AccountID = :account_id AND State = 1 AND (SELECT COUNT(*) FROM phoromatic_schedules_tests WHERE AccountID = :account_id AND ScheduleID = phoromatic_schedules.ScheduleID) > 0');
//echo phoromatic_server::$db->lastErrorMsg();
$stmt->bindValue(':account_id', ACCOUNT_ID);
$result = $stmt->execute();

$tests_expected_later_today = false;

while($result && $row = $result->fetchArray())
{
	// Make sure this test schedule is supposed to work on given system
	if(!in_array(SYSTEM_ID, explode(',', $row['RunTargetSystems'])))
	{
		// The system ID isn't in the run target but see if system ID belongs to a group in the run target
		$stmt = phoromatic_server::$db->prepare('SELECT Groups FROM phoromatic_systems WHERE AccountID = :account_id AND SystemID = :system_id LIMIT 1');
		$stmt->bindValue(':account_id', ACCOUNT_ID);
		$stmt->bindValue(':system_id', SYSTEM_ID);
		$sys_result = $stmt->execute();
		$sys_row = $sys_result->fetchArray();

		$matches_to_group = false;
		foreach(explode(',', $row['RunTargetGroups']) as $group)
		{
			if(stripos($sys_row['Groups'], '#' . $group . '#') !== false)
			{
				$matches_to_group = true;
				break;
			}
		}

		if($matches_to_group == false)
			continue;
	}

	// See if test is a time-based schedule due to run today and now or past the time scheduled to run
	if(strpos($row['ActiveOn'], strval($day_of_week_int)) !== false)
	{
		if($row['RunAt'] <= date('H.i'))
		{
			$trigger_id = date('Y-m-d');
			if(!phoromatic_server::check_for_triggered_result_match($row['ScheduleID'], $trigger_id, ACCOUNT_ID, SYSTEM_ID))
			{
				$res = phoromatic_generate_test_suite($row, $json, $trigger_id, $phoromatic_account_settings);
				if($res)
				{
					return;
				}
			}
		}
		else
		{
			// Test is scheduled to run later today
			$tests_expected_later_today = true;
		}
	}

	// See if custom trigger...
	$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_schedules_triggers WHERE AccountID = :account_id AND ScheduleID = :schedule_id ORDER BY TriggeredOn DESC');
	$stmt->bindValue(':account_id', ACCOUNT_ID);
	$stmt->bindValue(':schedule_id', $row['ScheduleID']);
	$trigger_result = $stmt->execute();
	while($trigger_result && $trigger_row = $trigger_result->fetchArray())
	{
		if(substr($trigger_row['TriggeredOn'], 0, 10) == date('Y-m-d') || substr($trigger_row['TriggeredOn'], 0, 10) == date('Y-m-d', (time() - 60 * 60 * 24)))
		{
			if(!phoromatic_server::check_for_triggered_result_match($row['ScheduleID'], $trigger_row['Trigger'], ACCOUNT_ID, SYSTEM_ID))
			{
				$res = phoromatic_generate_test_suite($row, $json, $trigger_row['Trigger'], $phoromatic_account_settings);
				if($res)
				{
					return;
				}
			}
		}
	}
}

// BENCHMARK TICKET

$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_benchmark_tickets WHERE AccountID = :account_id AND State = 1 AND TicketIssueTime < :current_time AND TicketIssueTime > :yesterday');
//echo phoromatic_server::$db->lastErrorMsg();
$stmt->bindValue(':account_id', ACCOUNT_ID);
$stmt->bindValue(':current_time', time());
$stmt->bindValue(':yesterday', (time() - (60 * 60 * 24)));
$result = $stmt->execute();

while($result && $row = $result->fetchArray())
{
	// Make sure this test schedule is supposed to work on given system
	if(!in_array(SYSTEM_ID, explode(',', $row['RunTargetSystems'])))
	{
		// The system ID isn't in the run target but see if system ID belongs to a group in the run target
		$stmt = phoromatic_server::$db->prepare('SELECT Groups FROM phoromatic_systems WHERE AccountID = :account_id AND SystemID = :system_id LIMIT 1');
		$stmt->bindValue(':account_id', ACCOUNT_ID);
		$stmt->bindValue(':system_id', SYSTEM_ID);
		$sys_result = $stmt->execute();
		$sys_row = $sys_result->fetchArray();

		$matches_to_group = false;
		foreach(explode(',', $row['RunTargetGroups']) as $group)
		{
			if(stripos($sys_row['Groups'], '#' . $group . '#') !== false)
			{
				$matches_to_group = true;
				break;
			}
		}

		if($matches_to_group == false)
			continue;
	}

	if(!phoromatic_server::check_for_benchmark_ticket_result_match($row['TicketID'], ACCOUNT_ID, SYSTEM_ID, $row['Title']))
	{
		$res = phoromatic_generate_benchmark_ticket($row, $json, $phoromatic_account_settings);
		if($res)
		{
			return;
		}
	}
}

// END OF BENCHMARK TICKET

if($CLIENT_CORE_VERSION >= 5511 && date('i') == 0 && $phoromatic_account_settings['PreSeedTestInstalls'] == 1 && phoromatic_pre_seed_tests_to_install($json, $phoromatic_account_settings))
{
	// XXX TODO: with WS backend won't need to limit to on the hour attempt
	return;
}

if($tests_expected_later_today == false && $phoromatic_account_settings['PowerOffWhenDone'] == 1)
{
	$json['phoromatic']['response'] = '[' . date('H:i:s') . '] Shutting system down per user settings as no more tests scheduled for today...';
	$json['phoromatic']['task'] = 'shutdown';
	echo json_encode($json);
	return;
}

$json['phoromatic']['task'] = 'idle';
$json['phoromatic']['response'] = '[' . date('H:i:s') . '] Idling, waiting for task assignment...';
echo json_encode($json);
return;

function phoromatic_generate_test_suite(&$test_schedule, &$json, $trigger_id, $phoromatic_account_settings)
{
	$suite_writer = new pts_test_suite_writer();
	$suite_writer->add_suite_information($test_schedule['Title'], '1.0.0', $test_schedule['LastModifiedBy'], 'System', 'An automated Phoromatic test schedule.');

	$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_schedules_tests WHERE AccountID = :account_id AND ScheduleID = :schedule_id');
	$stmt->bindValue(':account_id', ACCOUNT_ID);
	$stmt->bindValue(':schedule_id', $test_schedule['ScheduleID']);
	$result = $stmt->execute();

	$test_count = 0;
	while($row = $result->fetchArray())
	{
		$suite_writer->add_to_suite($row['TestProfile'], $row['TestArguments'], $row['TestDescription']);
		$test_count++;
	}

	if($test_count == 0)
	{
		return false;
	}

	$json['phoromatic']['task'] = 'benchmark';
	$json['phoromatic']['save_identifier'] = $test_schedule['Title'] . ' - ' . $trigger_id;
	$json['phoromatic']['trigger_id'] = $trigger_id;
	$json['phoromatic']['schedule_id'] = $test_schedule['ScheduleID'];
	$json['phoromatic']['test_suite'] = $suite_writer->get_xml();

	$contexts = array('SetContextPreInstall' => 'pre_install_set_context', 'SetContextPostInstall' => 'post_install_set_context', 'SetContextPreRun' => 'pre_run_set_context', 'SetContextPostRun' => 'post_run_set_context');
	foreach($contexts as $context => $v)
	{
		$json['phoromatic'][$v] = null;

		if(isset($test_schedule[$context]) && !empty($test_schedule[$context]) && is_file(phoromatic_server::phoromatic_account_path(ACCOUNT_ID) . 'context_' . $test_schedule[$context]))
		{
			$json['phoromatic'][$v] = file_get_contents(phoromatic_server::phoromatic_account_path(ACCOUNT_ID) . 'context_' . $test_schedule[$context]);
		}
	}

	$json['phoromatic']['settings'] = $phoromatic_account_settings;

	echo json_encode($json);
	return true;
}
function phoromatic_generate_benchmark_ticket(&$ticket_row, &$json, $phoromatic_account_settings)
{
	$test_suite = phoromatic_server::phoromatic_account_suite_path(ACCOUNT_ID, $ticket_row['SuiteToRun']) . 'suite-definition.xml';
	if(!is_file($test_suite))
	{
		return false;
	}

	$json['phoromatic']['task'] = 'benchmark';
	$json['phoromatic']['save_identifier'] = $ticket_row['Title'];
	$json['phoromatic']['test_description'] = $ticket_row['Description'];
	$json['phoromatic']['trigger_id'] = $ticket_row['ResultIdentifier'];
	$json['phoromatic']['benchmark_ticket_id'] = $ticket_row['TicketID'];
	$json['phoromatic']['result_identifier'] = $ticket_row['ResultIdentifier'];
	$json['phoromatic']['test_suite'] = file_get_contents($test_suite);
	$json['phoromatic']['settings'] = $phoromatic_account_settings;

	echo json_encode($json);
	return true;
}
function phoromatic_pre_seed_tests_to_install(&$json, $phoromatic_account_settings)
{
	$suite_writer = new pts_test_suite_writer();
	$suite_writer->add_suite_information('Pre-Seed', '1.0.0', 'Phoromatic', 'System', 'An automated Phoromatic test schedule.');

	$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_schedules_tests WHERE AccountID = :account_id');
	$stmt->bindValue(':account_id', ACCOUNT_ID);
	$result = $stmt->execute();

	$test_count = 0;
	while($row = $result->fetchArray())
	{
		$suite_writer->add_to_suite($row['TestProfile'], null, null);
		$test_count++;
	}

	if($test_count == 0)
	{
		return false;
	}

	$json['phoromatic']['task'] = 'install';
	$json['phoromatic']['test_suite'] = $suite_writer->get_xml();
	$json['phoromatic']['settings'] = $phoromatic_account_settings;

	echo json_encode($json);
	return true;
}

?>
