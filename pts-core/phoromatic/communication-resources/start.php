<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2021, Phoronix Media
	Copyright (C) 2009 - 2021, Michael Larabel

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
$json = array();
$json['phoromatic']['system_id'] = SYSTEM_ID;
$json['phoromatic']['server_core_version'] = PTS_CORE_VERSION;

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

$sys_stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_systems WHERE AccountID = :account_id AND SystemID = :system_id LIMIT 1');
$sys_stmt->bindValue(':account_id', ACCOUNT_ID);
$sys_stmt->bindValue(':system_id', SYSTEM_ID);
$sys_result = $sys_stmt->execute();
$sys_row = $sys_result->fetchArray();

// SEE IF SCHEDULE NEEDS TO RUN
$schedule_row = phoromatic_server::system_check_for_open_schedule_run(ACCOUNT_ID, SYSTEM_ID, 0, $sys_row);
if($schedule_row != false)
{
	$res = phoromatic_generate_test_suite($schedule_row, $json, $phoromatic_account_settings, $sys_row);
	if($res)
	{
		return;
	}
}
// END OF SCHEDULE RUN

// BENCHMARK TICKET
$ticket_row = phoromatic_server::system_check_for_open_benchmark_ticket(ACCOUNT_ID, SYSTEM_ID, $sys_row);
if($ticket_row != false)
{
	pts_logger::add_to_log(SYSTEM_ID . ' - needs to benchmark ticket for ' . $ticket_row['Title']);
	$res = phoromatic_generate_benchmark_ticket($ticket_row, $json, $phoromatic_account_settings, $sys_row);
	if($res)
	{
		return;
	}
}
// END OF BENCHMARK TICKET

if($CLIENT_CORE_VERSION >= 5511 && date('i') == 0 && $phoromatic_account_settings['PreSeedTestInstalls'] == 1 && phoromatic_pre_seed_tests_to_install($json, $phoromatic_account_settings, $sys_row))
{
	// XXX TODO: with WS backend won't need to limit to on the hour attempt
	return;
}

// Provide client with update script to ensure client is updated if it's doing nothing besides idling/shutting down
$update_script_path = phoromatic_server::phoromatic_account_path(ACCOUNT_ID) . 'client-update-script.sh';
if(is_file($update_script_path))
{
	$json['phoromatic']['client_update_script'] = file_get_contents($update_script_path);
}

if($phoromatic_account_settings['PowerOffWhenDone'] == 1 && $sys_row['BlockPowerOffs'] != 1)
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

function phoromatic_generate_test_suite(&$test_schedule, &$json, $phoromatic_account_settings, &$sys_row)
{
	if(isset($test_schedule['Trigger']))
	{
		$trigger_id = $test_schedule['Trigger'];
	}
	else
	{
		$trigger_id = date('Y-m-d');
	}

	$new_suite = new pts_test_suite();
	$new_suite->set_title($test_schedule['Title']);
	$new_suite->set_version('1.0.0');
	$new_suite->set_maintainer($test_schedule['LastModifiedBy']);
	$new_suite->set_suite_type('System');
	$new_suite->set_description($test_schedule['Description']);

	$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_schedules_tests WHERE AccountID = :account_id AND ScheduleID = :schedule_id');
	$stmt->bindValue(':account_id', ACCOUNT_ID);
	$stmt->bindValue(':schedule_id', $test_schedule['ScheduleID']);
	$result = $stmt->execute();

	while($row = $result->fetchArray())
	{
		$new_suite->add_to_suite($row['TestProfile'], $row['TestArguments'], $row['TestDescription']);
	}

	if($new_suite->get_test_count() == 0)
	{
		return false;
	}

	$json['phoromatic']['task'] = 'benchmark';
	$json['phoromatic']['save_identifier'] = $test_schedule['Title'] . ' - ' . $trigger_id;
	$json['phoromatic']['trigger_id'] = $trigger_id;
	$json['phoromatic']['schedule_id'] = $test_schedule['ScheduleID'];
	$json['phoromatic']['environment_variables'] = $test_schedule['EnvironmentVariables'];
	$json['phoromatic']['test_suite'] = $new_suite->get_xml(null, false, true, false);
	$json['phoromatic']['pre_set_sys_env_vars'] = $sys_row['SystemVariables'];

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
function phoromatic_generate_benchmark_ticket(&$ticket_row, &$json, $phoromatic_account_settings, &$sys_row)
{
	$test_suite = phoromatic_server::find_suite_file(ACCOUNT_ID, $ticket_row['SuiteToRun']);
	if(!is_file($test_suite))
	{
		return false;
	}
	$test_suite = new pts_test_suite($test_suite);
	if($test_suite->get_test_count() == 0)
	{
		return false;
	}

	$json['phoromatic']['task'] = 'benchmark';
	$json['phoromatic']['save_identifier'] = $ticket_row['Title'];
	$json['phoromatic']['test_description'] = $ticket_row['Description'];
	$json['phoromatic']['trigger_id'] = $ticket_row['ResultIdentifier'];
	$json['phoromatic']['benchmark_ticket_id'] = $ticket_row['TicketID'];
	$json['phoromatic']['result_identifier'] = $ticket_row['ResultIdentifier'];
	$json['phoromatic']['test_suite'] = $test_suite->get_xml(null, false, true, false);
	$json['phoromatic']['settings'] = $phoromatic_account_settings;
	$json['phoromatic']['environment_variables'] = $ticket_row['EnvironmentVariables'];
	$json['phoromatic']['pre_set_sys_env_vars'] = $sys_row['SystemVariables'];

	echo json_encode($json);
	return true;
}
function phoromatic_pre_seed_tests_to_install(&$json, $phoromatic_account_settings, &$sys_row)
{
	$new_suite = new pts_test_suite();
	$new_suite->set_title('Pre-Seed');
	$new_suite->set_version('1.0.0');
	$new_suite->set_maintainer('Phoromatic');
	$new_suite->set_suite_type('System');
	$new_suite->set_description('Pre-seeding commonly used tests to host.');

	$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_schedules_tests WHERE AccountID = :account_id');
	$stmt->bindValue(':account_id', ACCOUNT_ID);
	$result = $stmt->execute();

	$test_count = 0;
	while($row = $result->fetchArray())
	{
		$new_suite->add_to_suite($row['TestProfile']);
		$test_count++;
	}

	if($test_count == 0)
	{
		return false;
	}

	$json['phoromatic']['task'] = 'install';
	$json['phoromatic']['test_suite'] = $new_suite->get_xml();
	$json['phoromatic']['settings'] = $phoromatic_account_settings;
	$json['phoromatic']['pre_set_sys_env_vars'] = $sys_row['SystemVariables'];

	echo json_encode($json);
	return true;
}

?>
