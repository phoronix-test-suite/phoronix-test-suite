<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2014 - 2015, Phoronix Media
	Copyright (C) 2014 - 2015, Michael Larabel

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
//error_reporting(E_ALL | E_NOTICE | E_STRICT);

/*
		$server_response = phoromatic::upload_to_remote_server(array(
			'test' => $test_run_request->test_profile->get_identifier(),
			'test_args' => $test_run_request->get_arguments_description()
			));
*/

$stmt = phoromatic_server::$db->prepare('INSERT INTO phoromatic_system_client_errors (AccountID, SystemID, UploadTime, ScheduleID, TriggerID, ErrorMessage, TestIdentifier, TestArguments) VALUES (:account_id, :system_id, :upload_time, :schedule_id, :trigger_id, :error_msg, :test_identifier, :test_arguments)');
$stmt->bindValue(':account_id', ACCOUNT_ID);
$stmt->bindValue(':system_id', SYSTEM_ID);
$stmt->bindValue(':upload_time', phoromatic_server::current_time());
$stmt->bindValue(':schedule_id', $SCHEDULE_ID);
$stmt->bindValue(':trigger_id', $TRIGGER_STRING);
$stmt->bindValue(':error_msg', pts_strings::sanitize($ERROR_MSG));
$stmt->bindValue(':test_identifier', $TEST_IDENTIFIER);
$stmt->bindValue(':test_arguments', $OTHER);
$result = $stmt->execute();

// Email notifications
$stmt = phoromatic_server::$db->prepare('SELECT UserName, Email FROM phoromatic_users WHERE UserID IN (SELECT UserID FROM phoromatic_user_settings WHERE AccountID = :account_id AND NotifyOnWarnings = 1) AND AccountID = :account_id');
$stmt->bindValue(':account_id', ACCOUNT_ID);
$result = $stmt->execute();
while($row = $result->fetchArray())
{
	phoromatic_server::send_email($row['Email'], 'Phoromatic System Error/Warning', phoromatic_server::account_id_to_group_admin_email(ACCOUNT_ID), '<p><strong>' . $row['UserName'] . ':</strong></p><p>A warning or error has been reported by a system associated with the Phoromatic account.</p><p>System: ' . SYSTEM_NAME . '<br />Trigger String: ' . pts_strings::sanitize($TRIGGER_STRING) . '<br />Test Identifier: ' . pts_strings::sanitize($TEST_IDENTIFIER) . '<br />Message: ' . pts_strings::sanitize($ERROR_MSG) . '</p>');
}

?>
