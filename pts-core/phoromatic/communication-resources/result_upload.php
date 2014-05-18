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

if($SCHEDULE_ID == null || $TRIGGER_STRING == null || $OTHER == null || $OPENBENCHMARKING_ID == null)
{
	$json['phoromatic']['error'] = 'Missing Information For Result Upload.';
	echo json_encode($json);
	exit;
}

$stmt = phoromatic_server::$db->prepare('SELECT UploadID FROM phoromatic_results WHERE AccountID = :account_id ORDER BY UploadID DESC LIMIT 1');
$stmt->bindValue(':account_id', ACCOUNT_ID);
$result = $stmt->execute();
$row = $result->fetchArray();
$upload_id = (isset($row['UploadID']) ? $row['UploadID'] : 0) + 1;

$stmt = phoromatic_server::$db->prepare('INSERT INTO phoromatic_results (AccountID, SystemID, UploadID, ScheduleID, Trigger, UploadTime, Title, OpenBenchmarkingID) VALUES (:account_id, :system_id, :upload_id, :schedule_id, :trigger, :upload_time, :openbenchmarking_id)');
$stmt->bindValue(':account_id', ACCOUNT_ID);
$stmt->bindValue(':system_id', SYSTEM_ID);
$stmt->bindValue(':upload_id', $upload_id);
$stmt->bindValue(':schedule_id', $SCHEDULE_ID);
$stmt->bindValue(':trigger', $TRIGGER_STRING);
$stmt->bindValue(':upload_time', phoromatic_server::current_time());
$stmt->bindValue(':title', $OTHER);
$stmt->bindValue(':openbenchmarking_id', $OPENBENCHMARKING_ID);
$result = $stmt->execute();

$json['phoromatic']['response'] = 'Result Upload: ' . $unique_id;
echo json_encode($json);
exit;


?>
