<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2014, Phoronix Media
	Copyright (C) 2014, Michael Larabel
	pts-web-socket: A simple WebSocket implementation, inspired by designs of https://github.com/varspool/Wrench and http://code.google.com/p/phpwebsocket/

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

class pts_phoromatic_event_server
{
	public function __construct()
	{
		phoromatic_server::prepare_database(true);

		while(true)
		{
			$hour = date('G');
			$minute = date('i');

			if($minute == 0)
			{
				$stmt = phoromatic_server::$db->prepare('SELECT LastCommunication, CurrentTask, SystemID, AccountID, LastIP FROM phoromatic_systems WHERE State > 0 ORDER BY LastCommunication DESC');
				$result = $stmt->execute();

				while($row = $result->fetchArray())
				{
					$last_comm = strtotime($row['LastCommunication']);

					if($last_comm > (time() - 3600))
						continue; // if last comm time is less than an hour, still might be busy testing

					if($last_comm < (time() - (3600 * 3)))
						break; // it's already been reported enough for now...

					if(stripos($row['CurrentTask'], 'shutdown') !== false || stripos($row['CurrentTask'], 'shutting down') !== false)
						continue; // if the system shutdown, no reason to report it

					$stmt_email = phoromatic_server::$db->prepare('SELECT UserName, Email FROM phoromatic_users WHERE UserID IN (SELECT UserID FROM phoromatic_user_settings WHERE AccountID = :account_id AND NotifyOnHungSystems = 1) AND AccountID = :account_id');
					$stmt_email->bindValue(':account_id', $row['AccountID']);
					$result_email = $stmt_email->execute();
					while($row_email = $result_email->fetchArray())
					{
						if(empty($row_email['Email']))
							continue;

						phoromatic_server::send_email($row_email['Email'], 'Phoromatic System Potential Hang: ' . phoromatic_server::system_id_to_name($row['SystemID'], $row['AccountID']), 'no-reply@phoromatic.com', '<p><strong>' . $row_email['UserName'] . ':</strong></p><p>One of the systems associated with your Phoromatic account has not been communicating with the Phoromatic Server in more than sixty minutes. Below is the system information details:</p><p><strong>System:</strong> ' . phoromatic_server::system_id_to_name($row['SystemID'], $row['AccountID']) . '<br /><strong>Last Communication:</strong> ' . $row['LastCommunication'] . '<br /><strong>Last Task:</strong> ' . $row['CurrentTask'] . '<br /><strong>Local IP:</strong> ' . $row['LastIP'] . '</p>');
					}
				}
			}
			if($minute % 5 == 0)
			{
				$stmt = phoromatic_server::$db->prepare('SELECT LastCommunication, SystemID, AccountID, NetworkMAC FROM phoromatic_systems WHERE State > 0 AND NetworkMAC NOT LIKE \'\' AND NetworkWakeOnLAN LIKE \'%g%\' ORDER BY LastCommunication DESC');
				$result = $stmt->execute();

				while($row = $result->fetchArray())
				{
					$last_comm = strtotime($row['LastCommunication']);
					if($last_comm < (time() - (3600 * 25)))
						break; // System likely has some other issue if beyond a day it's been offline
					if($last_comm < (time() - 600))
					{
						// System hasn't communicated in a number of minutes so it might be powered off

						if(self::system_has_outstanding_jobs($row['AccountID'], $row['SystemID']))
						{
							// Make sure account has network WoL enabled
							$stmt1 = phoromatic_server::$db->prepare('SELECT NetworkPowerUpWhenNeeded FROM phoromatic_account_settings WHERE AccountID = :account_id');
							$stmt1->bindValue(':account_id', $row['AccountID']);
							$result1 = $stmt1->execute();
							$phoromatic_account_settings = $result1->fetchArray(SQLITE3_ASSOC);

							if(isset($phoromatic_account_settings['NetworkPowerUpWhenNeeded']) && $phoromatic_account_settings['NetworkPowerUpWhenNeeded'] == 1)
							{
								foreach(array('etherwake', 'ether-wake') as $etherwake)
								{
									if(pts_client::executable_in_path($etherwake))
									{
										shell_exec($etherwake . ' ' . $row['NetworkMAC']);
										break;
									}
								}
							}
						}
					}
				}
			}

			sleep((60 - date('s') + 1));
		}
	}
	protected static function system_has_outstanding_jobs($account_id, $system_id)
	{
		$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_schedules WHERE AccountID = :account_id AND State = 1 AND (SELECT COUNT(*) FROM phoromatic_schedules_tests WHERE AccountID = :account_id AND ScheduleID = phoromatic_schedules.ScheduleID) > 0');
		$stmt->bindValue(':account_id', $account_id);
		$result = $stmt->execute();
		$day_of_week_int = date('N') - 1;

		while($result && $row = $result->fetchArray())
		{
			// Make sure this test schedule is supposed to work on given system
			if(!in_array($system_id, explode(',', $row['RunTargetSystems'])))
			{
				$stmt = phoromatic_server::$db->prepare('SELECT Groups FROM phoromatic_systems WHERE AccountID = :account_id AND SystemID = :system_id LIMIT 1');
				$stmt->bindValue(':account_id', $account_id);
				$stmt->bindValue(':system_id', $system_id);
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
					if(!phoromatic_server::check_for_triggered_result_match($row['ScheduleID'], $trigger_id, $account_id, $system_id))
					{
						return true;
					}
				}
			}

			// See if custom trigger...
			$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_schedules_triggers WHERE AccountID = :account_id AND ScheduleID = :schedule_id ORDER BY TriggeredOn DESC');
			$stmt->bindValue(':account_id', $account_id);
			$stmt->bindValue(':schedule_id', $row['ScheduleID']);
			$trigger_result = $stmt->execute();
			while($trigger_result && $trigger_row = $trigger_result->fetchArray())
			{
				if(substr($trigger_row['TriggeredOn'], 0, 10) == date('Y-m-d') || substr($trigger_row['TriggeredOn'], 0, 10) == date('Y-m-d', (time() - 60 * 60 * 24)))
				{
					if(!phoromatic_server::check_for_triggered_result_match($row['ScheduleID'], $trigger_row['Trigger'], $account_id, SYSTEM_ID))
					{
						return true;
					}
				}
			}
		}

		return false;
	}
}

?>
