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
//error_reporting(E_ALL | E_NOTICE | E_STRICT);

$json = array();

if($COMPOSITE_XML != null)
{
	$composite_xml = base64_decode($_POST['composite_xml']);
}
else if($COMPOSITE_XML_GZ != null && function_exists('gzinflate'))
{
	$composite_xml = gzinflate(base64_decode($_POST['composite_xml_gz']));
}
else
{
	$composite_xml = null;
}

if($composite_xml == null || sha1($composite_xml) != $COMPOSITE_XML_HASH)
{
	$json['phoromatic']['error'] = 'XML Hash Mismatch';
	echo json_encode($json);
	return false;
}

// VALIDATE
$result_file = new pts_result_file($composite_xml);

// Validate the XML
if($result_file->validate() == false)
{
	$json['phoromatic']['error'] = 'XML Did Not Match Schema Definition';
	echo json_encode($json);
	return false;
}

if($result_file->get_title() == null || $result_file->get_system_count() == 0 || $result_file->get_test_count() == 0)
{
	$json['phoromatic']['error'] = 'Invalid Result File';
	echo json_encode($json);
	return false;
}

/*$Featured_Results = -1;
$Featured = pts_result_file_analyzer::analyze_result_file_intent($result_file, $Featured_Results);

if(!is_array($Featured))
{
	$Featured = array(null, null);
}*/

// DETERMINE UNIQUE UPLOAD ID
// IP = INET_ATON($_SERVER["REMOTE_ADDR"]);

$upload_id = false;
$to_update = false;
$progressive_upload = isset($_POST['progressive_upload']) ? $_POST['progressive_upload'] : false;

if($progressive_upload && $progressive_upload > 0)
{
	// See if previously uploaded
	if(!empty($BENCHMARK_TICKET_ID))
		$stmt = phoromatic_server::$db->prepare('SELECT UploadID FROM phoromatic_results WHERE AccountID = :account_id AND SystemID = :system_id AND BenchmarkTicketID = :benchmark_ticket_id AND InProgress > 0 ORDER BY UploadID DESC LIMIT 1');
	else
		$stmt = phoromatic_server::$db->prepare('SELECT UploadID FROM phoromatic_results WHERE AccountID = :account_id AND SystemID = :system_id AND ScheduleID = :schedule_id AND Trigger = :trigger AND InProgress > 0 ORDER BY UploadID DESC LIMIT 1');

	$stmt->bindValue(':account_id', ACCOUNT_ID);
	$stmt->bindValue(':system_id', SYSTEM_ID);
	$stmt->bindValue(':schedule_id', $SCHEDULE_ID);
	$stmt->bindValue(':benchmark_ticket_id', $BENCHMARK_TICKET_ID);
	$stmt->bindValue(':trigger', $TRIGGER_STRING);
	$result = $stmt->execute();
	$row = $result->fetchArray();
	$upload_id = isset($row['UploadID']) ? $row['UploadID'] : false;
	if($upload_id)
	{
		$to_update = true;
	}
}
if($upload_id == false)
{
	$stmt = phoromatic_server::$db->prepare('SELECT UploadID FROM phoromatic_results WHERE AccountID = :account_id ORDER BY UploadID DESC LIMIT 1');
	$stmt->bindValue(':account_id', ACCOUNT_ID);
	$result = $stmt->execute();
	$row = $result->fetchArray();
	$upload_id = (isset($row['UploadID']) ? $row['UploadID'] : 0) + 1;
}
$upload_time = phoromatic_server::current_time();
$xml_upload_hash = sha1($composite_xml);

if($to_update)
{
	$stmt = phoromatic_server::$db->prepare('UPDATE phoromatic_results SET UploadTime = :upload_time, Title = :title, Description = :description, ResultCount = :result_count, XmlUploadHash = :xml_upload_hash, ComparisonHash = :comparison_hash, ElapsedTime = :elapsed_time, InProgress = :in_progress WHERE AccountID = :account_id AND UploadID = :upload_id AND SystemID = :system_id');
}
else
{
	$stmt = phoromatic_server::$db->prepare('INSERT INTO phoromatic_results (AccountID, SystemID, UploadID, ScheduleID, BenchmarkTicketID, Trigger, UploadTime, Title, Description, SystemCount, ResultCount, DisplayStatus, XmlUploadHash, ComparisonHash, ElapsedTime, PPRID, InProgress) VALUES (:account_id, :system_id, :upload_id, :schedule_id, :benchmark_ticket_id, :trigger, :upload_time, :title, :description, :system_count, :result_count, :display_status, :xml_upload_hash, :comparison_hash, :elapsed_time, :pprid, :in_progress)');
}
$stmt->bindValue(':account_id', ACCOUNT_ID);
$stmt->bindValue(':system_id', SYSTEM_ID);
$stmt->bindValue(':upload_id', $upload_id);
$stmt->bindValue(':schedule_id', $SCHEDULE_ID);
$stmt->bindValue(':benchmark_ticket_id', $BENCHMARK_TICKET_ID);
$stmt->bindValue(':trigger', $TRIGGER_STRING);
$stmt->bindValue(':upload_time', $upload_time);
$stmt->bindValue(':title', pts_strings::sanitize($result_file->get_title()));
$stmt->bindValue(':description', pts_strings::sanitize($result_file->get_description()));
$stmt->bindValue(':system_count', $result_file->get_system_count());
$stmt->bindValue(':result_count', $result_file->get_test_count());
$stmt->bindValue(':display_status', 1);
$stmt->bindValue(':xml_upload_hash', $xml_upload_hash);
$stmt->bindValue(':comparison_hash', $result_file->get_contained_tests_hash(false));
$stmt->bindValue(':elapsed_time', (empty($ELAPSED_TIME) || !is_numeric($ELAPSED_TIME) || $ELAPSED_TIME < 0 ? 0 : $ELAPSED_TIME));
$stmt->bindValue(':pprid', phoromatic_server::compute_pprid(ACCOUNT_ID, SYSTEM_ID, $upload_time, $xml_upload_hash));
$stmt->bindValue(':in_progress', ($progressive_upload == 1 ? 1 : 0));

$result = $stmt->execute();
//echo phoromatic_server::$db->lastErrorMsg();
$result_directory = phoromatic_server::phoromatic_account_result_path(ACCOUNT_ID, $upload_id);
pts_file_io::mkdir($result_directory);
//phoromatic_add_activity_stream_event('result', $upload_id, 'uploaded');

file_put_contents($result_directory . 'composite.xml', $composite_xml);

if($SYSTEM_LOGS_ZIP != null && $SYSTEM_LOGS_HASH != null)
{
	if(sha1($SYSTEM_LOGS_ZIP) == $SYSTEM_LOGS_HASH)
	{
		$system_logs_zip = $result_directory . 'system-logs.zip';
		file_put_contents($system_logs_zip, base64_decode($_POST['system_logs_zip']));

		/*if(filesize($system_logs_zip) > 2097152)
		{
			unlink($system_logs_zip);
		}*/
	}

	unset($SYSTEM_LOGS_ZIP);
}

$relative_id = 0;
foreach($result_file->get_result_objects() as $result_object)
{
	$relative_id++;
	$stmt = phoromatic_server::$db->prepare('INSERT INTO phoromatic_results_results (AccountID, UploadID, AbstractID, TestProfile, ComparisonHash) VALUES (:account_id, :upload_id, :abstract_id, :test_profile, :comparison_hash)');
	$stmt->bindValue(':account_id', ACCOUNT_ID);
	$stmt->bindValue(':upload_id', $upload_id);
	$stmt->bindValue(':abstract_id', $relative_id);
	$stmt->bindValue(':test_profile', $result_object->test_profile->get_identifier());
	$stmt->bindValue(':comparison_hash', $result_object->get_comparison_hash(true, false));
	$result = $stmt->execute();
}

if($relative_id > 0)
{
	foreach($result_file->get_systems() as $s)
	{
		$stmt = phoromatic_server::$db->prepare('INSERT INTO phoromatic_results_systems (AccountID, UploadID, SystemIdentifier, Hardware, Software) VALUES (:account_id, :upload_id, :system_identifier, :hardware, :software)');
		$stmt->bindValue(':account_id', ACCOUNT_ID);
		$stmt->bindValue(':upload_id', $upload_id);
		$stmt->bindValue(':system_identifier', pts_strings::sanitize($s->get_identifier()));
		$stmt->bindValue(':hardware', pts_strings::sanitize($s->get_hardware()));
		$stmt->bindValue(':software', pts_strings::sanitize($s->get_software()));
		$result = $stmt->execute();
	}

	$json['phoromatic']['upload_id'] = $upload_id;
	$json['phoromatic']['response'] = 'Result Upload: ' . $upload_id;
	echo json_encode($json);

	if($progressive_upload != 1)
	{
		// Email notifications
		$stmt = phoromatic_server::$db->prepare('SELECT UserName, Email FROM phoromatic_users WHERE UserID IN (SELECT UserID FROM phoromatic_user_settings WHERE AccountID = :account_id AND NotifyOnResultUploads = 1) AND AccountID = :account_id');
		$stmt->bindValue(':account_id', ACCOUNT_ID);
		$result = $stmt->execute();
		while($row = $result->fetchArray())
		{
			phoromatic_server::send_email($row['Email'], 'Phoromatic Result Upload', phoromatic_server::account_id_to_group_admin_email(ACCOUNT_ID), '<p><strong>' . $row['UserName'] . ':</strong></p><p>A new result file has been uploaded to Phoromatic.</p><p>Upload ID: ' . $upload_id . '<br />Upload Time: ' . phoromatic_server::current_time() . '<br />Title: ' . pts_strings::sanitize($result_file->get_title()) . '<br />System: ' . SYSTEM_NAME . '</p>');
		}
	}

	return true;
}

$json['phoromatic']['error'] = 'End Termination Error';
echo json_encode($json);
return false;

?>
