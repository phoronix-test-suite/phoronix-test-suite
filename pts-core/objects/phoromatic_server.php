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
	private static $json_storage = null;

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
		$PHOROMATIC_PATH = pts_client::parse_home_directory(pts_config::read_user_config('PhoronixTestSuite/Options/Server/PhoromaticStorage', ''));

		if(empty($PHOROMATIC_PATH) || ((is_dir($PHOROMATIC_PATH) && !is_writable($PHOROMATIC_PATH)) || !is_writable(dirname($PHOROMATIC_PATH))))
		{
			$PHOROMATIC_PATH = PTS_USER_PATH . 'phoromatic/';
		}

		pts_file_io::mkdir($PHOROMATIC_PATH);
		return $PHOROMATIC_PATH;
	}
	public static function phoromatic_account_path($account_id)
	{
		return self::phoromatic_path() . 'accounts/' . $account_id . '/';
	}
	public static function phoromatic_account_result_path($account_id, $result_id = null)
	{
		return self::phoromatic_account_path($account_id) . 'results/' . ($result_id != null ? $result_id . '/' : null);
	}
	public static function read_setting($setting)
	{
		return pts_storage_object::read_from_file(self::$json_storage, $setting);
	}
	public static function save_setting($setting, $value)
	{
		return pts_storage_object::set_in_file(self::$json_storage, $setting, $value);
	}
	public static function prepare_database()
	{
		self::$json_storage = self::phoromatic_path() . 'phoromatic-settings.pt2so';
		if(!is_file(self::$json_storage))
		{
			$pt2so = new pts_storage_object();
			$pt2so->save_to_file(self::$json_storage);
		}

		$db_file = self::phoromatic_path() . 'phoromatic.db';
		self::$db = new SQLite3($db_file);

		switch(self::read_database_version())
		{
			case 0:
				// Account Database
				self::$db->exec('CREATE TABLE phoromatic_accounts (AccountID TEXT PRIMARY KEY, ValidateID TEXT NOT NULL, CreatedOn TEXT NOT NULL, Salt TEXT NOT NULL)');
				self::$db->exec('CREATE TABLE phoromatic_account_settings (AccountID TEXT PRIMARY KEY, ArchiveResultsLocally INTEGER, UploadSystemLogs INTEGER, RunInstallCommand INTEGER, ForceInstallTests INTEGER, SystemSensorMonitoring INTEGER)');
				self::$db->exec('CREATE TABLE phoromatic_users (UserID TEXT PRIMARY KEY, AccountID TEXT NOT NULL, UserName TEXT UNIQUE, Email TEXT, Password TEXT NOT NULL, CreatedOn TEXT NOT NULL, LastLogin TEXT, LastIP TEXT)');
				self::$db->exec('CREATE TABLE phoromatic_schedules (AccountID TEXT, ScheduleID INTEGER, Title TEXT, Description TEXT, State INTEGER, ActiveOn TEXT, RunAt TEXT, SetContextPreInstall TEXT, SetContextPostInstall TEXT, SetContextPreRun TEXT, SetContextPostRun TEXT, LastModifiedBy TEXT, LastModifiedOn TEXT, PublicKey TEXT, UNIQUE(AccountID, ScheduleID) ON CONFLICT IGNORE)');
				//self::$db->exec('CREATE TABLE phoromatic_schedules_systems (AccountID TEXT UNIQUE, ScheduleID INTEGER UNIQUE, SystemID TEXT UNIQUE)');
				self::$db->exec('CREATE TABLE phoromatic_schedules_tests (AccountID TEXT, ScheduleID INTEGER, TestProfile TEXT, TestArguments TEXT, TestDescription TEXT, UNIQUE(AccountID, ScheduleID, TestProfile, TestArguments) ON CONFLICT REPLACE)');
				self::$db->exec('CREATE TABLE phoromatic_schedules_triggers (AccountID TEXT, ScheduleID INTEGER, Trigger TEXT, TriggerTarget TEXT, TriggeredOn TEXT, UNIQUE(AccountID, ScheduleID, Trigger) ON CONFLICT IGNORE)');
				self::$db->exec('CREATE TABLE phoromatic_user_settings (AccountID TEXT, UserID TEXT, NotifyOnResultUploads INTEGER, NotifyOnWarnings INTEGER, NotifyOnNewSystems INTEGER, UNIQUE(AccountID, UserID) ON CONFLICT IGNORE)');
				self::$db->exec('CREATE TABLE phoromatic_systems (AccountID TEXT, SystemID TEXT, Title TEXT, Description TEXT, Groups TEXT, Hardware TEXT, Software TEXT, ClientVersion TEXT, GSID TEXT, CurrentTask TEXT, EstimatedTimeForTask TEXT, CreatedOn TEXT, LastCommunication TEXT, LastIP TEXT, State INTEGER, LocalIP TEXT, NetworkMAC TEXT, Flags TEXT, UNIQUE(AccountID, SystemID) ON CONFLICT IGNORE)');
				self::$db->exec('CREATE TABLE phoromatic_system_warnings (AccountID TEXT, SystemID TEXT, Warning TEXT, WarningTime TEXT)');
				self::$db->exec('CREATE TABLE phoromatic_results (AccountID TEXT, UploadID INTEGER, ScheduleID INTEGER, Trigger TEXT, UploadTime TEXT, Title TEXT, OpenBenchmarkingID TEXT, SystemID TEXT, UNIQUE(AccountID, UploadID) ON CONFLICT IGNORE)');
				self::$db->exec('CREATE TABLE phoromatic_groups (AccountID TEXT, GroupName TEXT, Description TEXT, UNIQUE(AccountID, GroupName) ON CONFLICT IGNORE)');

				self::$db->exec('PRAGMA user_version = 1');
			case 1:
				// phoromatic_results changes for schema mostly from OB
				// Changes made 20 September / post 5.4-M1
				self::$db->exec('ALTER TABLE phoromatic_results ADD COLUMN Description TEXT');
				self::$db->exec('ALTER TABLE phoromatic_results ADD COLUMN SystemCount INTEGER');
				self::$db->exec('ALTER TABLE phoromatic_results ADD COLUMN ResultCount INTEGER');
				self::$db->exec('ALTER TABLE phoromatic_results ADD COLUMN DisplayStatus INTEGER DEFAULT 1');
				self::$db->exec('ALTER TABLE phoromatic_results ADD COLUMN TimesViewed INTEGER DEFAULT 0');
				self::$db->exec('ALTER TABLE phoromatic_results ADD COLUMN XmlUploadHash TEXT');
				self::$db->exec('ALTER TABLE phoromatic_results ADD COLUMN ComparisonHash TEXT');
				// Add phoromatic_results_results as test_results_results equivalent from OB
				self::$db->exec('CREATE TABLE phoromatic_results_results (AccountID TEXT, UploadID INTEGER, AbstractID INTEGER, TestProfile TEXT, ComparisonHash TEXT, UNIQUE(AccountID, UploadID, AbstractID) ON CONFLICT IGNORE)');
				self::$db->exec('CREATE TABLE phoromatic_results_systems (AccountID TEXT, UploadID INTEGER, SystemIdentifier TEXT, Hardware TEXT, Software TEXT, UNIQUE(AccountID, UploadID, SystemIdentifier) ON CONFLICT IGNORE)');
				self::$db->exec('PRAGMA user_version = 2');
			case 2:
				// Change made 4 October to introduce machine self ID as a new identifier for local systems without Internet not having OpenBenchmarking.org GSID, etc
				self::$db->exec('ALTER TABLE phoromatic_systems ADD COLUMN MachineSelfID TEXT');
				self::$db->exec('PRAGMA user_version = 3');
			case 3:
				// Change made 8 October for targeting the SystemID / GroupNames of systems to test in schedules
				self::$db->exec('ALTER TABLE phoromatic_schedules ADD COLUMN RunTargetSystems TEXT');
				self::$db->exec('ALTER TABLE phoromatic_schedules ADD COLUMN RunTargetGroups TEXT');
				self::$db->exec('PRAGMA user_version = 4');
			case 4:
				// Change made 11 October for administrative level
				self::$db->exec('ALTER TABLE phoromatic_users ADD COLUMN AdminLevel INTEGER DEFAULT 1');
				self::$db->exec('PRAGMA user_version = 5');
			case 5:
				self::$db->exec('CREATE TABLE phoromatic_activity_stream (AccountID TEXT, ActivityTime TEXT, ActivityCreator TEXT, ActivityCreatorType TEXT, ActivityEvent TEXT, ActivityEventID TEXT, ActivityEventType TEXT)');
				self::$db->exec('PRAGMA user_version = 6');
			case 6:
				self::$db->exec('CREATE TABLE phoromatic_system_client_errors (AccountID TEXT, SystemID TEXT, UploadTime TEXT, ScheduleID INTEGER, TriggerID TEXT, ErrorMessage TEXT, TestIdentifier TEXT, TestArguments TEXT)');
				self::$db->exec('PRAGMA user_version = 7');
			case 7:
				// Change made 11 October for administrative level
				self::$db->exec('ALTER TABLE phoromatic_account_settings ADD COLUMN UploadResultsToOpenBenchmarking INTEGER DEFAULT 0');
				self::$db->exec('PRAGMA user_version = 8');
			case 8:
				// Change made 24 November 2014 Wake On LAN info for client systems
				self::$db->exec('ALTER TABLE phoromatic_systems ADD COLUMN NetworkWakeOnLAN TEXT');
				self::$db->exec('PRAGMA user_version = 9');
			case 9:
				// Change made 24 November 2014 for new user/account settings
				self::$db->exec('ALTER TABLE phoromatic_user_settings ADD COLUMN NotifyOnHungSystems INTEGER DEFAULT 0');
				self::$db->exec('ALTER TABLE phoromatic_account_settings ADD COLUMN PowerOffWhenDone INTEGER DEFAULT 0');
				self::$db->exec('ALTER TABLE phoromatic_account_settings ADD COLUMN NetworkPowerUpWhenNeeded INTEGER DEFAULT 0');
				self::$db->exec('PRAGMA user_version = 10');
		}
		chmod($db_file, 0600);
	}
	public static function send_email($to, $subject, $from, $body)
	{
		$msg = '<html><body>' . $body . '
		<hr />
		<p><img src="http://www.phoronix-test-suite.com/web/pts-logo-60.png" /></p>
		<h6><em>The <a href="http://www.phoronix-test-suite.com/">Phoronix Test Suite</a>, <a href="http://www.phoromatic.com/">Phoromatic</a>, and <a href="http://openbenchmarking.org/">OpenBenchmarking.org</a> are products of <a href="http://www.phoronix-media.com/">Phoronix Media</a>.<br />The Phoronix Test Suite is open-source under terms of the GNU GPL. Commercial support, custom engineering, and other services are available by contacting Phoronix Media.<br />&copy; ' . date('Y') . ' Phoronix Media.</em></h6>
		</body></html>';
		$headers = "MIME-Version: 1.0\r\n";
		$headers .= "Content-type:text/html;charset=UTF-8\r\n";
		$headers .= "From: <" . $from . ">\r\n";

		mail($to, $subject, $msg, $headers);
	}
	public static function system_id_to_name($system_id, $aid = false)
	{
		static $system_names;

		if(!isset($system_names[$system_id]))
		{
			$stmt = phoromatic_server::$db->prepare('SELECT Title FROM phoromatic_systems WHERE AccountID = :account_id AND SystemID = :system_id');
			$stmt->bindValue(':account_id', ($aid ? $aid : $_SESSION['AccountID']));
			$stmt->bindValue(':system_id', $system_id);
			$result = $stmt->execute();
			$row = $result->fetchArray();
			$system_names[$system_id] = $row['Title'];
		}

		return $system_names[$system_id];
	}
	public static function check_for_triggered_result_match($schedule_id, $trigger_id, $account_id, $system_id)
	{
		$stmt = phoromatic_server::$db->prepare('SELECT UploadID FROM phoromatic_results WHERE AccountID = :account_id AND ScheduleID = :schedule_id AND Trigger = :trigger AND SystemID = :system_id');
		$stmt->bindValue(':account_id', $account_id);
		$stmt->bindValue(':system_id', $system_id);
		$stmt->bindValue(':schedule_id', $schedule_id);
		$stmt->bindValue(':trigger', $trigger_id);
		$result = $stmt->execute();

		if($result != false && $result->fetchArray() != false)
		{
			return true;
		}

		// See if the system attempted to run the trigger/schedule combination but reported an error during the process....
		$stmt = phoromatic_server::$db->prepare('SELECT ErrorMessage FROM phoromatic_system_client_errors WHERE AccountID = :account_id AND SystemID = :system_id AND ScheduleID = :schedule_id AND TriggerID = :trigger ORDER BY UploadTime DESC LIMIT 10');
		$stmt->bindValue(':account_id', $account_id);
		$stmt->bindValue(':system_id', $system_id);
		$stmt->bindValue(':schedule_id', $schedule_id);
		$stmt->bindValue(':trigger', $trigger_id);
		$result = $stmt->execute();

		if($result != false && $result->fetchArray() != false)
		{
			return true;
		}

		return false;
	}
}

if(!is_dir(phoromatic_server::phoromatic_path() . 'accounts'))
{
	mkdir(phoromatic_server::phoromatic_path() . 'accounts');
}

?>
