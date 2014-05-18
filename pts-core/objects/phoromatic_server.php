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

class phoromatic_server
{
	public static $db = null;

	public static function current_time()
	{
		return date('Y-m-d H:i:s');
	}
	public static function read_database_version()
	{
		$result = self::$db->query('PRAGMA user_version');
		$result = $result->fetchArray();
		return isset($result['user_version']) && is_numeric($result['user_version']) ? $result['user_version'] : 0;
	}
	public static function phoromatic_path()
	{
		$PHOROMATIC_PATH = PTS_USER_PATH . 'phoromatic/';
		pts_file_io::mkdir($PHOROMATIC_PATH);
		return $PHOROMATIC_PATH;
	}
	public static function prepare_database()
	{
		self::$db = new SQLite3(self::phoromatic_path() . 'phoromatic.db');

		if(self::read_database_version() == 0)
		{
			// Account Database
			self::$db->exec('CREATE TABLE phoromatic_accounts (AccountID TEXT PRIMARY KEY, ValidateID TEXT NOT NULL, CreatedOn TEXT NOT NULL, Salt TEXT NOT NULL)');
			self::$db->exec('CREATE TABLE phoromatic_account_settings (AccountID TEXT PRIMARY KEY, ArchiveResultsLocally INTEGER, UploadSystemLogs INTEGER, RunInstallCommand INTEGER, ForceInstallTests INTEGER, SystemSensorMonitoring INTEGER)');
			self::$db->exec('CREATE TABLE phoromatic_users (UserID TEXT PRIMARY KEY, AccountID TEXT NOT NULL, UserName TEXT UNIQUE, Email TEXT, Password TEXT NOT NULL, CreatedOn TEXT NOT NULL, LastLogin TEXT, LastIP TEXT)');
			self::$db->exec('CREATE TABLE phoromatic_schedules (AccountID TEXT, ScheduleID INTEGER, Title TEXT, Description TEXT, State INTEGER, ActiveOn TEXT, RunAt TEXT, SetContextPreInstall TEXT, SetContextPostInstall TEXT, SetContextPreRun TEXT, SetContextPostRun TEXT, LastModifiedBy TEXT, LastModifiedOn TEXT, PublicKey TEXT, UNIQUE(AccountID, ScheduleID) ON CONFLICT IGNORE)');
			//self::$db->exec('CREATE TABLE phoromatic_schedules_systems (AccountID TEXT UNIQUE, ScheduleID INTEGER UNIQUE, SystemID TEXT UNIQUE)');
			self::$db->exec('CREATE TABLE phoromatic_schedules_tests (AccountID TEXT, ScheduleID INTEGER, TestProfile TEXT, TestArguments TEXT, TestDescription TEXT, UNIQUE(AccountID, ScheduleID, TestArguments) ON CONFLICT REPLACE)');
			self::$db->exec('CREATE TABLE phoromatic_schedules_triggers (AccountID TEXT, ScheduleID INTEGER, Trigger TEXT, TriggerTarget TEXT, TriggeredOn TEXT, UNIQUE(AccountID, ScheduleID, Trigger) ON CONFLICT IGNORE)');
			self::$db->exec('CREATE TABLE phoromatic_user_settings (AccountID TEXT UNIQUE, UserID TEXT UNIQUE, NotifyOnResultUploads INTEGER, NotifyOnWarnings INTEGER, NotifyOnNewSystems INTEGER)');
			self::$db->exec('CREATE TABLE phoromatic_systems (AccountID TEXT, SystemID TEXT, Title TEXT, Description TEXT, Groups TEXT, Hardware TEXT, Software TEXT, ClientVersion TEXT, GSID TEXT, CurrentTask TEXT, EstimatedTimeForTask TEXT, CreatedOn TEXT, LastCommunication TEXT, LastIP TEXT, State INTEGER, LocalIP TEXT, NetworkMAC TEXT, Flags TEXT, UNIQUE(AccountID, SystemID) ON CONFLICT IGNORE)');
			self::$db->exec('CREATE TABLE phoromatic_system_warnings (AccountID TEXT, SystemID TEXT, Warning TEXT, WarningTime TEXT)');
			self::$db->exec('CREATE TABLE phoromatic_results (AccountID TEXT UNIQUE, UploadID INTEGER, ScheduleID INTEGER, Trigger TEXT, UploadTime TEXT, Title TEXT, OpenBenchmarkingID TEXT, SystemID TEXT)');
			self::$db->exec('CREATE TABLE phoromatic_groups (AccountID TEXT, GroupName TEXT, Description TEXT, UNIQUE(AccountID, GroupName) ON CONFLICT IGNORE)');

			self::$db->exec('PRAGMA user_version = 1');
		}
	}
	public static function send_email($to, $subject, $from, $body)
	{
		$msg = '<html><body>' . $body . '</body></html>';
		$headers = "MIME-Version: 1.0\r\n";
		$headers .= "Content-type:text/html;charset=UTF-8\r\n";
		$headers .= "From: <" . $from . ">\r\n";

		mail($to, $subject, $msg, $headers);
	}
}

?>
