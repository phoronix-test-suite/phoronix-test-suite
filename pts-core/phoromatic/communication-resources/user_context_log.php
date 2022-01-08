<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2014, Phoronix Media
	Copyright (C) 2014, Michael Larabel

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

$stmt = phoromatic_server::$db->prepare('INSERT INTO phoromatic_system_context_logs (AccountID, SystemID, UploadTime, ScheduleID, TriggerID, UserContextStep, UserContextLog) VALUES (:account_id, :system_id, :upload_time, :schedule_id, :trigger_id, :user_context_step, :user_context_log)');
$stmt->bindValue(':account_id', ACCOUNT_ID);
$stmt->bindValue(':system_id', SYSTEM_ID);
$stmt->bindValue(':upload_time', phoromatic_server::current_time());
$stmt->bindValue(':schedule_id', $SCHEDULE_ID);
$stmt->bindValue(':trigger_id', $TRIGGER_STRING);
$stmt->bindValue(':user_context_step', $ID);
$stmt->bindValue(':user_context_log', pts_strings::sanitize($OTHER));
$result = $stmt->execute();

?>
