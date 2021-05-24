<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2014 - 2018, Phoronix Media
	Copyright (C) 2014 - 2018, Michael Larabel
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
	protected function send_wol_wakeup($mac, $ip)
	{
		$sent_wol_request = false;
		if(PTS_IS_DAEMONIZED_SERVER_PROCESS)
		{
			foreach(array('etherwake', 'ether-wake') as $etherwake)
			{
				if(pts_client::executable_in_path($etherwake))
				{
					shell_exec($etherwake . ' ' . $mac . ' 2>&1');
					$sent_wol_request = true;
					sleep(6);
					break;
				}
			}
		}
		if(true || $sent_wol_request == false) // TODO XXX: right now sending both packets while debugging network issues
		{
			pts_network::send_wol_packet($ip, $mac);
			$sent_wol_request = true;
			sleep(6);
		}

		return $sent_wol_request;
	}
	public static function ob_cache_run()
	{
		pts_openbenchmarking::available_tests(true);
		pts_openbenchmarking::available_suites(true);
	}
	public function __construct()
	{
		$systems_already_reported = array();
		pts_client::fork(array('pts_phoromatic_event_server', 'ob_cache_run'), null);
		$is_first_run = true;

		while(true)
		{
			$hour = date('G');
			$minute = date('i');

			phoromatic_server::prepare_database();
			if($is_first_run || $minute == 0)
			{
				if($is_first_run || $hour == 8)
				{
					pts_client::fork(array('pts_phoromatic_event_server', 'ob_cache_run'), null);
				}

				// Check for basic hung systems
				$stmt = phoromatic_server::$db->prepare('SELECT LastCommunication, CurrentTask, EstimatedTimeForTask, SystemID, AccountID, LastIP FROM phoromatic_systems WHERE State > 0 ORDER BY LastCommunication DESC');
				$result = $stmt ? $stmt->execute() : false;

				while($result && $row = $result->fetchArray())
				{
					$last_comm = strtotime($row['LastCommunication']);

					if($last_comm > (time() - 3600))
						continue; // if last comm time is less than an hour, still might be busy testing

					if($last_comm < (time() - (3600 * 3)) && !$is_first_run)
						continue; // it's already been reported enough for now...

					if(stripos($row['CurrentTask'], 'shutdown') !== false || stripos($row['CurrentTask'], 'shutting down') !== false)
						continue; // if the system shutdown, no reason to report it

					if(phoromatic_server::estimated_time_remaining_diff($row['EstimatedTimeForTask'], $row['LastCommunication']) > 0)
						continue; // system task time didn't run out yet

					// UPDATE SYSTEM STATUS TO "UNKNOWN"
					$stmt_unknown = phoromatic_server::$db->prepare('UPDATE phoromatic_systems SET CurrentTask = :unknown_state WHERE AccountID = :account_id AND SystemID = :system_id');
					$stmt_unknown->bindValue(':account_id', $row['AccountID']);
					$stmt_unknown->bindValue(':system_id', $row['SystemID']);
					$stmt_unknown->bindValue(':unknown_state', 'Unknown');
					$stmt_unknown->execute();

					$stmt_email = phoromatic_server::$db->prepare('SELECT UserName, Email FROM phoromatic_users WHERE UserID IN (SELECT UserID FROM phoromatic_user_settings WHERE AccountID = :account_id AND NotifyOnHungSystems = 1) AND AccountID = :account_id');
					$stmt_email->bindValue(':account_id', $row['AccountID']);
					$result_email = $stmt_email->execute();
					while($row_email = $result_email->fetchArray())
					{
						if(empty($row_email['Email']))
							continue;

						phoromatic_server::send_email($row_email['Email'], 'Phoromatic System Potential Hang: ' . phoromatic_server::system_id_to_name($row['SystemID'], $row['AccountID']), phoromatic_server::account_id_to_group_admin_email($row['AccountID']), '<p><strong>' . $row_email['UserName'] . ':</strong></p><p>One of the systems associated with your Phoromatic account has not been communicating with the Phoromatic Server in more than sixty minutes. Below is the system information details:</p><p><strong>System:</strong> ' . phoromatic_server::system_id_to_name($row['SystemID'], $row['AccountID']) . '<br /><strong>Last Communication:</strong> ' . phoromatic_server::user_friendly_timedate($row['LastCommunication']) . '<br /><strong>Last Task:</strong> ' . $row['CurrentTask'] . '<br /><strong>Local IP:</strong> ' . $row['LastIP'] . '</p>');
					}
				}
			}
			if($is_first_run || $minute % 2 == 0)
			{
				// Check for systems to wake
				$stmt = phoromatic_server::$db->prepare('SELECT LastCommunication, CurrentTask, SystemID, AccountID, NetworkMAC, LastIP, MaintenanceMode FROM phoromatic_systems WHERE State > 0 AND NetworkMAC NOT LIKE \'\' ORDER BY LastCommunication DESC'); // dropped this after PTS 7.4 AND NetworkWakeOnLAN LIKE \'%g%\'
				$result = $stmt ? $stmt->execute() : false;

				while($result && $row = $result->fetchArray())
				{
					if(!isset($phoromatic_account_settings[$row['AccountID']]))
					{
						$stmt1 = phoromatic_server::$db->prepare('SELECT NetworkPowerUpWhenNeeded, PowerOnSystemDaily FROM phoromatic_account_settings WHERE AccountID = :account_id');
						$stmt1->bindValue(':account_id', $row['AccountID']);
						$result1 = $stmt1->execute();
						$phoromatic_account_settings[$row['AccountID']] = $result1->fetchArray(SQLITE3_ASSOC);
					}

					$last_comm = strtotime($row['LastCommunication']);
					if($last_comm < (time() - 360) && $row['MaintenanceMode'] == 1)
					{
						self::send_wol_wakeup($row['NetworkMAC'], $row['LastIP']);
						continue;
					}
					if($minute % 20 == 0 && $last_comm < (time() - (3600 * 18)) && $phoromatic_account_settings[$row['AccountID']]['PowerOnSystemDaily'] == 1)
					{
						// Daily power on test if system hasn't communicated / powered on in a day
						// XXX right now the "daily" power on test is 18 hours. change or make user value in future?
						// Just doing this check every 20 minutes as not too vital
						self::send_wol_wakeup($row['NetworkMAC'], $row['LastIP']);
						continue;
					}
					if($last_comm < (time() - 600) || stripos($row['CurrentTask'], 'Shutdown') !== false)
					{
						// System hasn't communicated in a number of minutes so it might be powered off

						if(phoromatic_server::system_has_outstanding_jobs($row['AccountID'], $row['SystemID'], 0, false) !== false)
						{
							// Make sure account has network WoL enabled
							if($phoromatic_account_settings[$row['AccountID']]['NetworkPowerUpWhenNeeded'] == 1)
							{
								self::send_wol_wakeup($row['NetworkMAC'], $row['LastIP']);
							}
						}
					}
				}
			}
			if($minute % 8 == 0 && $hour > 1)
			{
				// See if system appears down
				$stmt = phoromatic_server::$db->prepare('SELECT LastCommunication, CurrentTask, SystemID, AccountID, LastIP FROM phoromatic_systems WHERE State > 0 ORDER BY LastCommunication DESC');
				$result = $stmt ? $stmt->execute() : false;

				while($result && ($row = $result->fetchArray()))
				{
					$sys_hash = sha1($row['AccountID'] . $row['SystemID']);

					// Avoid sending duplicate messages over time
					if(isset($systems_already_reported[$sys_hash]) && $systems_already_reported[$sys_hash] > (time() - (3600 * 24)))
						continue;

					if(phoromatic_server::system_check_if_down($row['AccountID'], $row['SystemID'], $row['LastCommunication'], $row['CurrentTask']))
					{
						if(strtotime($row['LastCommunication']) < (time() - (86400 * 7)))
						{
							// If system hasn't been online in a week, likely has bigger worries...
							continue;
						}

						$stmt_email = phoromatic_server::$db->prepare('SELECT UserName, Email FROM phoromatic_users WHERE UserID IN (SELECT UserID FROM phoromatic_user_settings WHERE AccountID = :account_id AND NotifyOnHungSystems = 1) AND AccountID = :account_id');
						$stmt_email->bindValue(':account_id', $row['AccountID']);
						$result_email = $stmt_email->execute();
						while($row_email = $result_email->fetchArray())
						{
							if(empty($row_email['Email']))
								continue;

							phoromatic_server::send_email($row_email['Email'], 'Phoromatic System Potential Problem: ' . phoromatic_server::system_id_to_name($row['SystemID'], $row['AccountID']), phoromatic_server::account_id_to_group_admin_email($row['AccountID']), '<p><strong>' . $row_email['UserName'] . ':</strong></p><p>One of the systems associated with your Phoromatic account has not been communicating with the Phoromatic Server and is part of a current active test schedule. Below is the system information details:</p><p><strong>System:</strong> ' . phoromatic_server::system_id_to_name($row['SystemID'], $row['AccountID']) . '<br /><strong>Last Communication:</strong> ' . phoromatic_server::user_friendly_timedate($row['LastCommunication']) . '<br /><strong>Last Task:</strong> ' . $row['CurrentTask'] . '<br /><strong>Local IP:</strong> ' . $row['LastIP'] . '</p>');
						}
						$systems_already_reported[$sys_hash] = time();
					}
				}
			}
			if($is_first_run || $last_ob_relay_ping < (time() - 86400))
			{
				$last_ob_relay_ping = time();
				// Zeroconf via OpenBenchmarking.org
				if(pts_config::read_user_config('PhoronixTestSuite/Options/Server/AdvertiseServiceOpenBenchmarkRelay', 'TRUE') && pts_network::internet_support_available())
				{
					pts_openbenchmarking::make_openbenchmarking_request('phoromatic_server_relay', array('local_ip' => phodevi::read_property('network', 'ip'), 'local_port' => getenv('PTS_WEB_PORT')));
				}
			}
			phoromatic_server::close_database();
			sleep((60 - date('s') + 1));
			$is_first_run = false;
		}
	}
}

?>
