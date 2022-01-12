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
define('PHOROMATIC_SERVER', true);
//ini_set('memory_limit', '64M');
define('PTS_MODE', 'WEB_CLIENT');
define('PTS_AUTO_LOAD_OBJECTS', true);
error_reporting(E_ALL);

include('../../pts-core.php');
pts_core::init();

phoromatic_server::prepare_database();

if(!isset($_GET['type']))
{
	echo 'Missing type.';
	return;
}

switch($_GET['type'])
{
	case 'trigger':
		if(!isset($_GET['user']) || !isset($_GET['public_key']) || !isset($_GET['trigger']))
		{
			echo 'Missing user, public_key, or trigger.';
			return;
		}
		$stmt = phoromatic_server::$db->prepare('SELECT AccountID FROM phoromatic_users WHERE UserName = :user_name');
		$stmt->bindValue(':user_name', $_GET['user']);
		$result = $stmt->execute();
		if(empty($result))
		{
			echo 'Incorrect user information.';
			return;
		}
		$user_row = $result->fetchArray();
		$stmt = phoromatic_server::$db->prepare('SELECT ScheduleID FROM phoromatic_schedules WHERE AccountID = :account_id AND PublicKey = :public_key');
		$stmt->bindValue(':account_id', $user_row['AccountID']);
		$stmt->bindValue(':public_key', $_GET['public_key']);
		$result = $stmt->execute();
		if(empty($result))
		{
			echo 'Incorrect schedule information.';
			return;
		}
		$schedule_row = $result->fetchArray();
		$sub_target = null;

		if(isset($_GET['sub_target_this_ip']))
		{
			$stmt = phoromatic_server::$db->prepare('SELECT SystemID FROM phoromatic_systems WHERE AccountID = :account_id AND LastIP = :this_ip');
			$stmt->bindValue(':account_id', $user_row['AccountID']);
			$stmt->bindValue(':this_ip', $_SERVER['REMOTE_ADDR']);
			$result = $stmt->execute();
			if(empty($result))
			{
				echo 'No system found associated to this IP address [' . $_SERVER['REMOTE_ADDR'] . '].';
				return;
			}
			else
			{
				$sys_row = $result->fetchArray();
				$sub_target = $sys_row['SystemID'];
			}
		}

		$trigger = pts_strings::sanitize($_GET['trigger']);
		$stmt = phoromatic_server::$db->prepare('INSERT INTO phoromatic_schedules_triggers (AccountID, ScheduleID, Trigger, TriggeredOn, SubTarget) VALUES (:account_id, :schedule_id, :trigger, :triggered_on, :sub_target)');
		$stmt->bindValue(':account_id',	$user_row['AccountID']);
		$stmt->bindValue(':schedule_id', $schedule_row['ScheduleID']);
		$stmt->bindValue(':trigger', $trigger);
		$stmt->bindValue(':triggered_on', phoromatic_server::current_time());
		$stmt->bindValue(':sub_target', $sub_target);
		if($stmt->execute())
		{
			echo 'Trigger ' . $trigger . ' added!';
		}
		break;

}
?>
