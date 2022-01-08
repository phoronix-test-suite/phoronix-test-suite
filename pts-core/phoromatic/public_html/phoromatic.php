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

// INIT
define('PHOROMATIC_SERVER', true);
//ini_set('memory_limit', '64M');
define('PTS_MODE', 'WEB_CLIENT');
define('PTS_AUTO_LOAD_OBJECTS', true);
error_reporting(E_ALL);

include('../../pts-core.php');
pts_core::init();

$environment_variables = array(
	'aid' => 'ACCOUNT_ID',
	'sid' => 'SYSTEM_ID',
	'bid' => 'BENCHMARK_TICKET_ID',
//	'vid' => 'VALIDATE_ID',
	'gsid' => 'GSID',
	'a' => 'ACTIVITY',
	'r' => 'REQUEST',
	'pts' => 'CLIENT_VERSION',
	'pts_core' => 'CLIENT_CORE_VERSION',
	'h' => 'CLIENT_HARDWARE',
	's' => 'CLIENT_SOFTWARE',
	'pp' => 'PHODEVI_PROPERTIES',
	'i' => 'ID',
	'o' => 'OTHER',
	'nm' => 'NETWORK_CLIENT_MAC',
	'nw' => 'NETWORK_CLIENT_WOL',
	'n' => 'HOSTNAME',
	'ti' => 'TEST_IDENTIFIER',
	'ts' => 'TRIGGER_STRING',
	'time' => 'ESTIMATED_TIME',
	'pc' => 'PERCENT_COMPLETE',
	'c' => 'COMPOSITE_XML',
	'ob' => 'OPENBENCHMARKING_ID',
	'sched' => 'SCHEDULE_ID',
	'lip' => 'LOCAL_IP',
	'l' => 'LOGS',
	'j' => 'JSON',
	'composite_xml' => 'COMPOSITE_XML',
	'composite_xml_gz' => 'COMPOSITE_XML_GZ',
	'composite_xml_hash' => 'COMPOSITE_XML_HASH',
	'system_logs_type' => 'SYSTEM_LOGS_TYPE',
	'system_logs_zip' => 'SYSTEM_LOGS_ZIP',
	'system_logs_hash' => 'SYSTEM_LOGS_HASH',
	'msi' => 'PTS_MACHINE_SELF_ID',
	'err' => 'ERROR_MSG',
	'et' => 'ELAPSED_TIME',
	);

foreach($environment_variables as $get_var => $to_var)
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

if($CLIENT_CORE_VERSION < 5400)
{
	// Due to major PTS 5.4 development changes, client version bump will be necessary
	$json['phoromatic']['error'] = 'You must update your Phoronix Test Suite clients for compatibility with this Phoromatic server.';
	echo json_encode($json);
	exit;
}
define('CLIENT_CORE_VERSION', $CLIENT_CORE_VERSION);

// DATABASE SETUP
phoromatic_server::prepare_database();

if($ACCOUNT_ID == null && $PTS_MACHINE_SELF_ID != null)
{
	// Try to find the account
	$stmt = phoromatic_server::$db->prepare('SELECT AccountID FROM phoromatic_systems WHERE MachineSelfID = :machine_self_id');
	$stmt->bindValue(':machine_self_id', $PTS_MACHINE_SELF_ID);
	$result = $stmt->execute();

	if(!empty($result))
	{
		$result = $result->fetchArray();
		if($result['AccountID'] != null)
		{
			$json['phoromatic']['account_id'] = $result['AccountID'];
			echo json_encode($json);
			exit;
		}
	}

	// Try to find the account if there is an IP/MAC claim
	if(!empty($_SERVER['REMOTE_ADDR']) && !empty($NETWORK_CLIENT_MAC))
	{
		// IPAddress = :ip_address OR
		$stmt = phoromatic_server::$db->prepare('SELECT AccountID FROM phoromatic_system_association_claims WHERE NetworkMAC = :network_mac OR IPAddress = :ip_address ORDER BY CreationTime ASC LIMIT 1');
		$stmt->bindValue(':ip_address', $_SERVER['REMOTE_ADDR']);
		$stmt->bindValue(':network_mac', $NETWORK_CLIENT_MAC);
		$result = $stmt->execute();

		if(!empty($result))
		{
			$result = $result->fetchArray();
			$json['phoromatic']['account_id'] = $result['AccountID'];
			echo json_encode($json);
			exit;
		}
	}
}

if(($GSID == null && $PTS_MACHINE_SELF_ID == null) || $ACCOUNT_ID == null)
{
	$json['phoromatic']['error'] = 'Invalid Credentials';
	echo json_encode($json);
	exit;
}

// CHECK FOR VALID ACCOUNT
if(!phoromatic_server::is_phoromatic_account_path($ACCOUNT_ID))
{
	$json['phoromatic']['error'] = 'Invalid User';
	echo json_encode($json);
	exit;
}
define('ACCOUNT_ID', $ACCOUNT_ID);


// CHECK IF SYSTEM IS ALREADY CONNECTED TO THE ACCOUNT
if($PTS_MACHINE_SELF_ID != null)
{
	$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, Groups, State, MaintenanceMode, LastCommunication FROM phoromatic_systems WHERE AccountID = :account_id AND MachineSelfID = :machine_self_id');
	$stmt->bindValue(':account_id', ACCOUNT_ID);
	$stmt->bindValue(':machine_self_id', $PTS_MACHINE_SELF_ID);
	$result = $stmt->execute();
	$result = $result->fetchArray();
}


if(!isset($result) || empty($result))
{
	// If system was reloaded and MachineSelfID no longer matches but there is existing IP or MAC address claim against it
	// XXX dropped LastIP = :ip_address OR
	$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, Groups, State, MaintenanceMode, LastCommunication FROM phoromatic_systems WHERE AccountID = :account_id AND NetworkMAC = :network_mac');
	$stmt->bindValue(':account_id', ACCOUNT_ID);
	$stmt->bindValue(':ip_address', $_SERVER['REMOTE_ADDR']);
	$stmt->bindValue(':network_mac', $NETWORK_CLIENT_MAC);
	$result = $stmt->execute();
	$result = $result->fetchArray();
}

if(empty($result))
{
	$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_account_settings WHERE AccountID = :account_id');
	$stmt->bindValue(':account_id', ACCOUNT_ID);
	$result = $stmt->execute();
	$phoromatic_account_settings = $result->fetchArray(SQLITE3_ASSOC);
	unset($phoromatic_account_settings['AccountID']);

	// APPARENT FIRST TIME FOR THIS SYSTEM CONNECTING TO THIS ACCOUNT
	do
	{
		$system_id = pts_strings::random_characters(5, true);
		$matching_system = phoromatic_server::$db->querySingle('SELECT AccountID FROM phoromatic_systems WHERE SystemID = \'' . $system_id . '\'');
	}
	while(!empty($matching_system));
	$stmt = phoromatic_server::$db->prepare('INSERT INTO phoromatic_systems (AccountID, SystemID, Hardware, Software, SystemProperties, ClientVersion, GSID, CurrentTask, CreatedOn, LastCommunication, LastIP, LocalIP, Title, State, MachineSelfID, CoreVersion, NetworkMAC) VALUES (:account_id, :system_id, :client_hardware, :client_software, :phodevi_properties, :client_version, :gsid, :current_task, :current_time, :current_time, :access_ip, :local_ip, :title, :preset_state, :machine_self_id, :core_version, :network_mac)');
	$stmt->bindValue(':account_id', ACCOUNT_ID);
	$stmt->bindValue(':system_id', $system_id);
	$stmt->bindValue(':client_hardware', $CLIENT_HARDWARE);
	$stmt->bindValue(':client_software', $CLIENT_SOFTWARE);
	$stmt->bindValue(':phodevi_properties', $PHODEVI_PROPERTIES);
	$stmt->bindValue(':client_version', $CLIENT_VERSION);
	$stmt->bindValue(':gsid', $GSID);
	$stmt->bindValue(':access_ip', $_SERVER['REMOTE_ADDR']);
	$stmt->bindValue(':local_ip', $LOCAL_IP);
	$stmt->bindValue(':title', pts_strings::simple($HOSTNAME));
	$stmt->bindValue(':current_time', phoromatic_server::current_time());
	$stmt->bindValue(':machine_self_id', $PTS_MACHINE_SELF_ID);
	$stmt->bindValue(':core_version', $CLIENT_CORE_VERSION);
	$stmt->bindValue(':network_mac', $NETWORK_CLIENT_MAC);

	if($phoromatic_account_settings['AutoApproveNewSystems'])
	{
		$stmt->bindValue(':current_task', 'System Added');
		$stmt->bindValue(':preset_state', 1);
		$new_response = 'System Automatically Added To Account.';
	}
	else
	{
		$stmt->bindValue(':current_task', 'Awaiting Authorization');
		$stmt->bindValue(':preset_state', 0);
		$new_response = 'Information Added; Waiting For Approval From Administrator.';
	}

	$result = $stmt->execute();

	// Email notifications
	$stmt = phoromatic_server::$db->prepare('SELECT UserName, Email FROM phoromatic_users WHERE UserID IN (SELECT UserID FROM phoromatic_user_settings WHERE AccountID = :account_id AND NotifyOnNewSystems = 1) AND AccountID = :account_id');
	$stmt->bindValue(':account_id', ACCOUNT_ID);
	$result = $stmt->execute();
	while($row = $result->fetchArray())
	{
		phoromatic_server::send_email($row['Email'], 'Phoromatic New System Added', phoromatic_server::account_id_to_group_admin_email(ACCOUNT_ID), '<p><strong>' . $row['UserName'] . ':</strong></p><p>A new system is attempting to associate with a Phoromatic account for which you\'re associated.</p><p>Title: ' . $HOSTNAME . '<br />IP: ' . $LOCAL_IP . '<br />System Info: ' . $CLIENT_HARDWARE . ' ' . $CLIENT_SOFTWARE . '</p>');
	}

	// Send response back
	$json['phoromatic']['response'] = $new_response;
	echo json_encode($json);
	exit;
}

define('SYSTEM_ID', $result['SystemID']);
define('SYSTEM_NAME', $result['Title']);
define('SYSTEM_GROUPS', $result['Groups']);
$SYSTEM_STATE = $result['State'];
define('GSID', $GSID);
define('SYSTEM_IN_MAINTENANCE_MODE', ($result['MaintenanceMode'] == 1));

if(strtotime($result['LastCommunication']) < (time() - 300))
{
	// Avoid useless updates to the database if it's close to the same info in past 2 minutes
	$stmt = phoromatic_server::$db->prepare('UPDATE phoromatic_systems SET LastIP = :access_ip, LocalIP = :local_ip, LastCommunication = :current_time, Hardware = :client_hardware, Software = :client_software, SystemProperties = :phodevi_properties, ClientVersion = :client_version, MachineSelfID = :machine_self_id, NetworkMAC = :network_mac, NetworkWakeOnLAN = :network_wol, CoreVersion = :core_version WHERE AccountID = :account_id AND SystemID = :system_id');
	$stmt->bindValue(':account_id', $ACCOUNT_ID);
	$stmt->bindValue(':system_id', SYSTEM_ID);
	$stmt->bindValue(':client_hardware', $CLIENT_HARDWARE);
	$stmt->bindValue(':client_software', $CLIENT_SOFTWARE);
	$stmt->bindValue(':phodevi_properties', $PHODEVI_PROPERTIES);
	$stmt->bindValue(':client_version', $CLIENT_VERSION);
	$stmt->bindValue(':core_version', $CLIENT_CORE_VERSION);
	$stmt->bindValue(':access_ip', $_SERVER['REMOTE_ADDR']);
	$stmt->bindValue(':local_ip', $LOCAL_IP);
	$stmt->bindValue(':current_time', phoromatic_server::current_time());
	$stmt->bindValue(':machine_self_id', $PTS_MACHINE_SELF_ID);
	$stmt->bindValue(':network_mac', $NETWORK_CLIENT_MAC);
	$stmt->bindValue(':network_wol', $NETWORK_CLIENT_WOL);
	$stmt->execute();
}

//echo phoromatic_server::$db->lastErrorMsg();
if($SYSTEM_STATE < 1)
{
	$json['phoromatic']['response'] = 'Waiting For Approval From Administrator.';
	echo json_encode($json);
	exit;
}

define('AID', ACCOUNT_ID);
define('SID', SYSTEM_ID);

if(is_file('../communication-resources/' . $REQUEST . '.php'))
{
	require('../communication-resources/' . $REQUEST . '.php');
}
else
{
	$json['phoromatic']['error'] = 'Unknown Resource: ' . $REQUEST;
	echo json_encode($json);
}

//phoromatic_server::close_database();

?>
