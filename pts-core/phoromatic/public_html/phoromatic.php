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

// INIT
define('REMOTE_ACCESS', true); // XXX TODO: Is this still used with new Phoromatic?
//ini_set('memory_limit', '64M');
define('PTS_MODE', 'WEB_CLIENT');
define('PTS_AUTO_LOAD_OBJECTS', true);
error_reporting(E_ALL);

include('../../pts-core.php');
pts_client::init();

$environmental_variables = array(
	'aid' => 'ACCOUNT_ID',
	'sid' => 'SYSTEM_ID',
//	'vid' => 'VALIDATE_ID',
	'gsid' => 'GSID',
	'a' => 'ACTIVITY',
	'r' => 'REQUEST',
	'pts' => 'CLIENT_VERSION',
	'h' => 'CLIENT_HARDWARE',
	's' => 'CLIENT_SOFTWARE',
	'i' => 'ID',
	'o' => 'OTHER',
	'n' => 'HOSTNAME',
	'ti' => 'TEST_IDENTIFIER',
	'ts' => 'TRIGGER_STRING',
	'time' => 'ESTIMATED_TIME',
	'c' => 'COMPOSITE_XML',
	'ob' => 'OPENBENCHMARKING_ID',
	'lip' => 'LOCAL_IP'
	);

foreach($environmental_variables as $get_var => $to_var)
{
	if(isset($_REQUEST[$get_var]) && !empty($_REQUEST[$get_var]))
	{
		$$to_var = $_REQUEST[$get_var];
	}
	else
	{
		$$to_var = null;
	}
}

if($GSID == null || $ACCOUNT_ID == null)
{
	$json['phoromatic']['error'] = 'Invalid Credentials';
	echo json_encode($json);
	exit;
}

// DATABASE SETUP
phoromatic_server::prepare_database();

// CHECK FOR VALID ACCOUNT
$stmt = phoromatic_server::$db->prepare('SELECT AccountID FROM phoromatic_accounts WHERE AccountID = :account_id');
$stmt->bindValue(':account_id', $ACCOUNT_ID);
$result = $stmt->execute();
$result = $result->fetchArray();
//var_dump($result = $result->fetchArray());
if(empty($result))
{
	$json['phoromatic']['error'] = 'Invalid User';
	echo json_encode($json);
	exit;
}
define('ACCOUNT_ID', $ACCOUNT_ID);


// CHECK IF SYSTEM IS ALREADY CONNECTED TO THE ACCOUNT
// self::$db->exec('CREATE TABLE phoromatic_systems (AccountID TEXT UNIQUE, SystemID TEXT UNIQUE, Title TEXT, Description TEXT, Groups TEXT Hardware TEXT, Software TEXT, ClientVersion TEXT, GSID TEXT, CurrentTask TEXT, EstimatedTimeForTask TEXT, CreatedOn TEXT, LastCommunication TEXT, LastIP TEXT, LocalIP TEXT)');
$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, Groups, State FROM phoromatic_systems WHERE AccountID = :account_id AND GSID = :gsid');
$stmt->bindValue(':account_id', ACCOUNT_ID);
$stmt->bindValue(':gsid', $GSID);
$result = $stmt->execute();
$result = $result->fetchArray();
if(empty($result))
{
	// APPARENT FIRST TIME FOR THIS SYSTEM CONNECTING TO THIS ACCOUNT
	do
	{
		$system_id = pts_strings::random_characters(5, true);
		$matching_system = phoromatic_server::$db->querySingle('SELECT AccountID FROM phoromatic_systems WHERE SystemID = \'' . $system_id . '\'');
	}
	while(!empty($matching_system));
	$stmt = phoromatic_server::$db->prepare('INSERT INTO phoromatic_systems (AccountID, SystemID, Hardware, Software, ClientVersion, GSID, CurrentTask, CreatedOn, LastCommunication, LastIP, LocalIP, Title, State) VALUES (:account_id, :system_id, :client_hardware, :client_software, :client_version, :gsid, \'Awaiting Authorization\', :current_time, :current_time, :access_ip, :local_ip, :title, 0)');
	$stmt->bindValue(':account_id', $ACCOUNT_ID);
	$stmt->bindValue(':system_id', $system_id);
	$stmt->bindValue(':client_hardware', $CLIENT_HARDWARE);
	$stmt->bindValue(':client_software', $CLIENT_SOFTWARE);
	$stmt->bindValue(':client_version', $CLIENT_VERSION);
	$stmt->bindValue(':gsid', $GSID);
	$stmt->bindValue(':access_ip', $_SERVER['REMOTE_ADDR']);
	$stmt->bindValue(':local_ip', $LOCAL_IP);
	$stmt->bindValue(':title', $HOSTNAME);
	$stmt->bindValue(':current_time', phoromatic_server::current_time());
	$result = $stmt->execute();
	$json['phoromatic']['response'] = 'Information Added; Waiting For Approval From Administrator.';
	echo json_encode($json);
	exit;
}
define('SYSTEM_ID', $result['SystemID']);
define('SYSTEM_NAME', $result['Title']);
define('SYSTEM_GROUPS', $result['Groups']);
$SYSTEM_STATE = $result['State'];
define('GSID', $GSID);

$stmt = phoromatic_server::$db->prepare('UPDATE phoromatic_systems SET LastIP = :access_ip, LocalIP = :local_ip, LastCommunication = :current_time, Hardware = :client_hardware, Software = :client_software, ClientVersion = :client_version WHERE AccountID = :account_id AND SystemID = :system_id');
$stmt->bindValue(':account_id', $ACCOUNT_ID);
$stmt->bindValue(':system_id', SYSTEM_ID);
$stmt->bindValue(':client_hardware', $CLIENT_HARDWARE);
$stmt->bindValue(':client_software', $CLIENT_SOFTWARE);
$stmt->bindValue(':client_version', $CLIENT_VERSION);
$stmt->bindValue(':access_ip', $_SERVER['REMOTE_ADDR']);
$stmt->bindValue(':local_ip', $LOCAL_IP);
$stmt->bindValue(':current_time', phoromatic_server::current_time());
$stmt->execute();
//echo phoromatic_server::$db->lastErrorMsg();
if($SYSTEM_STATE < 1)
{
	$json['phoromatic']['response'] = 'Waiting For Approval From Administrator.';
	echo json_encode($json);
	exit;
}

define('AID', ACCOUNT_ID);
define('SID', SYSTEM_ID);

	$json['phoromatic']['response'] = 'Test';
	echo json_encode($json);

if(is_file('../communication-resources/' . $REQUEST . '.php'))
{echo 333;
	require('../communication-resources/' . $REQUEST . '.php');
}
else
{
	$json['phoromatic']['error'] = 'Unknown Resource: ' . $REQUEST;
	echo json_encode($json);
}

?>
