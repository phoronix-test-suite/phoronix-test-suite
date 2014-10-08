<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2014, Phoronix Media
	Copyright (C) 2009 - 2014, Michael Larabel

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

$day_of_week_int = date('N') - 1;

$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_schedules WHERE AccountID = :account_id AND State = 1 AND (SELECT COUNT(*) FROM phoromatic_schedules_tests WHERE AccountID = :account_id AND ScheduleID = phoromatic_schedules.ScheduleID) > 0');
//echo phoromatic_server::$db->lastErrorMsg();
$stmt->bindValue(':account_id', ACCOUNT_ID);
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

	// See if test is a time-based schedule due to run today and now or past the time scheduled to run
	if(strpos($row['ActiveOn'], strval($day_of_week_int)) !== false && $row['RunAt'] <= date('H.i'))
	{
		$trigger_id = date('Y-m-d');
		if(!phoromatic_check_for_triggered_result($row['ScheduleID'], $trigger_id))
		{
			$result = phoromatic_generate_test_suite($row, $json, $trigger_id);
			if($result)
			{
				return;
			}
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
			if(!phoromatic_check_for_triggered_result($row['ScheduleID'], $trigger_row['Trigger']))
			{
				$result = phoromatic_generate_test_suite($row, $json, $trigger_row['Trigger']);
				if($result)
				{
					return;
				}
			}
		}
	}
}

$json['phoromatic']['response'] = '[' . date('H:i:s') . '] Idling, waiting for task assignment...';
echo json_encode($json);
return;

function phoromatic_check_for_triggered_result($schedule_id, $trigger_id)
{
	$stmt = phoromatic_server::$db->prepare('SELECT UploadID FROM phoromatic_results WHERE AccountID = :account_id AND ScheduleID = :schedule_id AND Trigger = :trigger AND SystemID = :system_id');
	$stmt->bindValue(':account_id', ACCOUNT_ID);
	$stmt->bindValue(':system_id', SYSTEM_ID);
	$stmt->bindValue(':schedule_id', $schedule_id);
	$stmt->bindValue(':trigger', $trigger_id);
	$result = $stmt->execute();

	if($result != false && $result->fetchArray() != false)
	{
		return true;
	}

	return false;
}
function phoromatic_generate_test_suite(&$test_schedule, &$json, $trigger_id)
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

	$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_account_settings WHERE AccountID = :account_id');
	$stmt->bindValue(':account_id', ACCOUNT_ID);
	$result = $stmt->execute();
	$json['phoromatic']['settings'] = $result->fetchArray(SQLITE3_ASSOC);
	unset($json['phoromatic']['settings']['AccountID']);

	/*
	$xml_writer->addXmlNode('PhoronixTestSuite/Phoromatic/General/UploadToGlobal', (pts_rmm_get_settings_value("UploadResultsToGlobal") == 1 ? "TRUE" : "FALSE"));
	$xml_writer->addXmlNode('PhoronixTestSuite/Phoromatic/General/ArchiveResultsLocally', (pts_rmm_get_settings_value("ArchiveResultsLocally") == 1 ? "TRUE" : "FALSE"));
	$xml_writer->addXmlNode('PhoronixTestSuite/Phoromatic/General/UploadTestLogs', (pts_rmm_get_settings_value("UploadTestLogsToServer") == 1 ? "TRUE" : "FALSE"));
	$xml_writer->addXmlNode('PhoronixTestSuite/Phoromatic/General/UploadSystemLogs', (pts_rmm_get_settings_value("UploadSystemLogsToServer") == 1 ? "TRUE" : "FALSE"));
	$xml_writer->addXmlNode('PhoronixTestSuite/Phoromatic/General/RunInstallCommand', (pts_rmm_get_settings_value("RunInstallCommand") == 1 ? "TRUE" : "FALSE"));
	$xml_writer->addXmlNode('PhoronixTestSuite/Phoromatic/General/ForceInstallTests', (pts_rmm_get_settings_value("ForceInstallTests") == 1 ? "TRUE" : "FALSE"));
	$xml_writer->addXmlNode('PhoronixTestSuite/Phoromatic/General/SetContextPreInstall', $record->SetContextPreInstall);
	$xml_writer->addXmlNode('PhoronixTestSuite/Phoromatic/General/SetContextPreRun', $record->SetContextPreRun);
	$xml_writer->addXmlNode('PhoronixTestSuite/Phoromatic/General/Trigger', $trigger_string);
	$xml_writer->addXmlNode('PhoronixTestSuite/Phoromatic/General/ID', $schedule_id);
	*/
	echo json_encode($json);
	return true;
}

return;

$result = mysql_query("SELECT Title, Description, ScheduleID, LastModifiedBy, SetContextPreInstall, SetContextPreRun FROM schedules WHERE AccountID = '" . AID . "' AND Status = 'ACTIVE' AND (SELECT COUNT(Test) FROM schedules_tests WHERE AccountID = '" . AID . "' AND ScheduleID = schedules.ScheduleID LIMIT 1) NOT LIKE '0' AND (SELECT COUNT(SystemID) FROM schedules_systems WHERE AccountID = '" . AID . "' AND ScheduleID = schedules.ScheduleID AND (SystemID = '" . SID . "' OR SystemID = '0') LIMIT 1) NOT LIKE '0' AND
(
(SUBSTRING(ActiveOn, DAYOFWEEK(NOW()), 1) = '1' AND RunAt <= CURRENT_TIME() AND '" . SID . "' NOT IN (SELECT SystemID FROM test_runs WHERE DATE(UploadTime) = CURRENT_DATE() AND TIME(UploadTime) > schedules.RunAt AND ScheduleID = schedules.ScheduleID))
 OR
((SELECT COUNT(TriggerString) FROM schedules_triggered WHERE AccountID = '" . AID . "' AND ScheduleID = schedules.ScheduleID AND TriggeredOn > DATE_SUB(NOW(), INTERVAL 1 DAY) AND '" . SID . "' NOT IN (SELECT SystemID FROM test_runs WHERE TriggerString = schedules_triggered.TriggerString AND ScheduleID = schedules.ScheduleID)) NOT LIKE '0')
)

 LIMIT 1");

$xml_writer = new nye_XmlWriter();
$xml_writer->addXmlNode('PhoronixTestSuite/Phoromatic/General/SystemName', SYSTEM_NAME);

if(mysql_num_rows($result) == 1)
{
	$record = mysql_fetch_object($result);
	mysql_free_result($result);
	$schedule_id = $record->ScheduleID;

	$result = mysql_query("SELECT TriggerString FROM schedules_triggered WHERE AccountID = '" . AID . "' AND ScheduleID = '" . $schedule_id . "' AND TriggeredOn > DATE_SUB(NOW(), INTERVAL 1 DAY) AND '" . SID . "' NOT IN (SELECT SystemID FROM test_runs WHERE TriggerString = schedules_triggered.TriggerString AND ScheduleID = '" . $schedule_id . "') ORDER BY TriggeredOn ASC LIMIT 1");

	if(mysql_num_rows($result) == 1)
	{
		$record_trigger = mysql_fetch_object($result);
		$trigger_string = $record_trigger->TriggerString;
	}
	else
	{
		$trigger_string = date('Y-m-d');
	}
	mysql_free_result($result);

	$xml_writer->addXmlNode('PhoronixTestSuite/Phoromatic/General/Response', 'benchmark');
	$xml_writer->addXmlNode('PhoronixTestSuite/Phoromatic/General/UploadToGlobal', (pts_rmm_get_settings_value("UploadResultsToGlobal") == 1 ? "TRUE" : "FALSE"));
	$xml_writer->addXmlNode('PhoronixTestSuite/Phoromatic/General/ArchiveResultsLocally', (pts_rmm_get_settings_value("ArchiveResultsLocally") == 1 ? "TRUE" : "FALSE"));
	$xml_writer->addXmlNode('PhoronixTestSuite/Phoromatic/General/UploadTestLogs', (pts_rmm_get_settings_value("UploadTestLogsToServer") == 1 ? "TRUE" : "FALSE"));
	$xml_writer->addXmlNode('PhoronixTestSuite/Phoromatic/General/UploadSystemLogs', (pts_rmm_get_settings_value("UploadSystemLogsToServer") == 1 ? "TRUE" : "FALSE"));
	$xml_writer->addXmlNode('PhoronixTestSuite/Phoromatic/General/RunInstallCommand', (pts_rmm_get_settings_value("RunInstallCommand") == 1 ? "TRUE" : "FALSE"));
	$xml_writer->addXmlNode('PhoronixTestSuite/Phoromatic/General/ForceInstallTests', (pts_rmm_get_settings_value("ForceInstallTests") == 1 ? "TRUE" : "FALSE"));
	$xml_writer->addXmlNode('PhoronixTestSuite/Phoromatic/General/SetContextPreInstall', $record->SetContextPreInstall);
	$xml_writer->addXmlNode('PhoronixTestSuite/Phoromatic/General/SetContextPreRun', $record->SetContextPreRun);
	$xml_writer->addXmlNode('PhoronixTestSuite/Phoromatic/General/Trigger', $trigger_string);
	$xml_writer->addXmlNode('PhoronixTestSuite/Phoromatic/General/ID', $schedule_id);

	$xml_writer->addXmlNode('PhoronixTestSuite/SuiteInformation/Title', $record->Title);
	$xml_writer->addXmlNode('PhoronixTestSuite/SuiteInformation/Version', "1.0.0");
	$xml_writer->addXmlNode('PhoronixTestSuite/SuiteInformation/Maintainer', $record->LastModifiedBy);
	$xml_writer->addXmlNode('PhoronixTestSuite/SuiteInformation/TestType', "System");
	$xml_writer->addXmlNode('PhoronixTestSuite/SuiteInformation/Description', $record->Description);

	$result = mysql_query("SELECT Test, TestArguments, TestDescription FROM schedules_tests WHERE AccountID = '" . AID . "' AND ScheduleID = '" . $schedule_id . "'");

	while($record = mysql_fetch_object($result))
	{
		$xml_writer->addXmlNode('PhoronixTestSuite/Execute/Test', $record->Test);

		if($record->TestArguments != null && $record->TestDescription != null)
		{
			$xml_writer->addXmlNode('PhoronixTestSuite/Execute/Arguments', $record->TestArguments);
			$xml_writer->addXmlNode('PhoronixTestSuite/Execute/Description', $record->TestDescription);
		}
	}
}
else
{
	$todo = 'idle';

	if(pts_rmm_get_settings_value('ExitDailyWhenDone') == 1)
	{
		// like the above command but with "AND RunAt <= CURRENT_TIME()" removed
		$result = mysql_query("SELECT RunAt FROM schedules WHERE AccountID = '" . AID . "' AND Status = 'ACTIVE' AND SUBSTRING(ActiveOn, DAYOFWEEK(NOW()), 1) = '1' AND (SELECT COUNT(SystemID) FROM schedules_systems WHERE AccountID = '" . AID . "' AND ScheduleID = schedules.ScheduleID AND (SystemID = '" . SID . "' OR SystemID = '0') LIMIT 1) NOT LIKE '0' AND (SELECT COUNT(Test) FROM schedules_tests WHERE AccountID = '" . AID . "' AND ScheduleID = schedules.ScheduleID LIMIT 1) NOT LIKE '0' AND " . SID . " NOT IN (SELECT SystemID FROM test_runs WHERE DATE(UploadTime) = CURRENT_DATE() AND TIME(UploadTime) > schedules.RunAt AND ScheduleID = schedules.ScheduleID) LIMIT 1");

		if(mysql_num_rows($result) == 0)
		{
			$todo = 'exit';
		}
	}

	$xml_writer->addXmlNode('PhoronixTestSuite/Phoromatic/General/Response', $todo);

}

echo $xml_writer->getXML();

?>
