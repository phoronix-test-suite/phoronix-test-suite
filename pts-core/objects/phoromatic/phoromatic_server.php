<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2014 - 2021, Phoronix Media
	Copyright (C) 2014 - 2021, Michael Larabel

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

		if($result == false)
			return -1;

		$result = $result->fetchArray();
		return isset($result['user_version']) && is_numeric($result['user_version']) ? $result['user_version'] : 0;
	}
	public static function phoromatic_path()
	{
		$PHOROMATIC_PATH = pts_config::read_path_config('PhoronixTestSuite/Options/Server/PhoromaticStorage', '');

		if(empty($PHOROMATIC_PATH) || ((is_dir($PHOROMATIC_PATH) && !is_writable($PHOROMATIC_PATH)) || !is_writable(dirname($PHOROMATIC_PATH))))
		{
			$PHOROMATIC_PATH = PTS_USER_PATH . 'phoromatic/';
		}

		pts_file_io::mkdir($PHOROMATIC_PATH);
		return $PHOROMATIC_PATH;
	}
	public static function find_download_cache()
	{
		static $dc_file = false;

		if($dc_file == false)
		{
			if(($dc = pts_client::download_cache_path()) && is_file($dc . 'pts-download-cache.json'))
			{
				$dc_file = $dc . 'pts-download-cache.json';
			}
			else if(is_file(PTS_DOWNLOAD_CACHE_PATH . 'pts-download-cache.json'))
			{
				$dc_file = PTS_DOWNLOAD_CACHE_PATH . 'pts-download-cache.json';
			}
			else if(is_file('/var/cache/phoronix-test-suite/download-cache/pts-download-cache.json'))
			{
				$dc_file = '/var/cache/phoronix-test-suite/download-cache/pts-download-cache.json';
			}
			else if(is_file(PTS_SHARE_PATH . 'download-cache/pts-download-cache.json'))
			{
				$dc_file = PTS_SHARE_PATH . 'download-cache/pts-download-cache.json';
			}
		}

		return $dc_file;
	}
	public static function download_cache_items()
	{
		$items = array();

		if(($dc = phoromatic_server::find_download_cache()))
		{
			$cache_json = file_get_contents($dc);
			$cache_json = json_decode($cache_json, true);

			if($cache_json && isset($cache_json['phoronix-test-suite']['download-cache']))
			{
				foreach($cache_json['phoronix-test-suite']['download-cache'] as $file_name => $info)
				{
					$items[$file_name] = $info;
				}
			}

			// looking for files added but not in the JSON cache, a.k.a. files uploaded but not make-download-cache run since
			foreach(pts_file_io::glob(dirname($dc) . '/*') as $file_in_dir)
			{
				$f = basename($file_in_dir);
				if($f == 'pts-download-cache.json')
				{
					continue;
				}
				if(!isset($items[$f]))
				{
					$items[$f] = array(
					'file_name' => $f,
					'file_size' => filesize($file_in_dir),
					'associated_tests' => array(),
					'md5' => md5_file($file_in_dir),
					'sha256' => hash_file('sha256', $file_in_dir),
					);
				}
			}
			ksort($items);
		}
		return $items;
	}
	public static function is_phoromatic_account_path($account_id)
	{
		return $account_id != null && is_dir(self::phoromatic_path() . 'accounts/' . $account_id);
	}
	public static function phoromatic_account_path($account_id)
	{
		return self::phoromatic_path() . 'accounts/' . $account_id . '/';
	}
	public static function phoromatic_account_result_path($account_id, $result_id = null)
	{
		return self::phoromatic_account_path($account_id) . 'results/' . ($result_id != null ? $result_id . '/' : null);
	}
	public static function phoromatic_account_suite_path($account_id, $suite_id = null)
	{
		return self::phoromatic_account_path($account_id) . 'suites/' . ($suite_id != null ? $suite_id . '/' : null);
	}
	public static function find_suite_file($account_id, $suite_name)
	{
		$suite_file = false;
		$xml_path = phoromatic_server::phoromatic_account_suite_path($account_id, $suite_name) . 'suite-definition.xml';
		if(is_file($xml_path))
		{
			// Local account-created suite
			$suite_file = $xml_path;
		}
		else
		{
			if(is_file(PTS_TEST_SUITE_PATH . $suite_name . '/suite-definition.xml'))
			{
				// is a PTS suite
				$suite_file = PTS_TEST_SUITE_PATH . $suite_name . '/suite-definition.xml';
			}
		}

		return $suite_file;
	}
	public static function phoromatic_account_system_path($account_id, $system_id = null)
	{
		return self::phoromatic_account_path($account_id) . 'systems/' . ($system_id != null ? $system_id . '/' : null);
	}
	public static function phoromatic_account_stress_log_path($account_id, $ticket_id = null)
	{
		return self::phoromatic_account_path($account_id) . 'stress-logs/' . ($ticket_id != null ? $ticket_id . '/' : null);
	}
	public static function read_setting($setting)
	{
		return pts_storage_object::read_from_file(self::$json_storage, $setting);
	}
	public static function save_setting($setting, $value)
	{
		return pts_storage_object::set_in_file(self::$json_storage, $setting, $value);
	}
	public static function close_database()
	{
		if(self::$db != null)
		{
			self::$db->close();
			self::$db = null;
		}
	}
	public static function prepare_database($read_only = false)
	{
		self::$json_storage = self::phoromatic_path() . 'phoromatic-settings.pt2so';
		if(!is_file(self::$json_storage))
		{
			$pt2so = new pts_storage_object();
			$pt2so->save_to_file(self::$json_storage);
		}

		$db_file = self::phoromatic_path() . 'phoromatic.db';
		$db_flags = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE;

		if($read_only && is_file($db_file))
		{
			$db_flags = SQLITE3_OPEN_READONLY;
		}
		else if(is_file($db_file) && !is_writable($db_file))
		{
			echo 'The database file is not writable!' . PHP_EOL . 'DB: ' . $db_file . PHP_EOL;
		}
		else if(!is_writable(dirname($db_file)))
		{
			echo 'The database directory is not writable!' . PHP_EOL . 'DB: ' . dirname($db_file) . PHP_EOL;
		}

		self::$db = new SQLite3($db_file, $db_flags);
		self::$db->busyTimeout(10000);

		if($read_only && is_file($db_file))
		{
			return true;
		}

		switch(self::read_database_version())
		{
			case 0:
				// Account Database
				self::$db->exec('CREATE TABLE phoromatic_accounts (AccountID TEXT PRIMARY KEY, ValidateID TEXT NOT NULL, CreatedOn TEXT NOT NULL, Salt TEXT NOT NULL)');
				self::$db->exec('CREATE TABLE phoromatic_account_settings (AccountID TEXT PRIMARY KEY, ArchiveResultsLocally INTEGER, UploadSystemLogs INTEGER DEFAULT 1, RunInstallCommand INTEGER DEFAULT 1, ForceInstallTests INTEGER, SystemSensorMonitoring INTEGER)');
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
			case 10:
				// Change made 25 November for user context logging
				self::$db->exec('CREATE TABLE phoromatic_system_context_logs (AccountID TEXT, SystemID TEXT, UploadTime TEXT, ScheduleID INTEGER, TriggerID TEXT, UserContextStep TEXT, UserContextLog TEXT)');
				self::$db->exec('PRAGMA user_version = 11');
			case 11:
				// Change made 27 November for time elapsed during benchmarking
				self::$db->exec('ALTER TABLE phoromatic_results ADD COLUMN ElapsedTime INTEGER DEFAULT 0');
				self::$db->exec('PRAGMA user_version = 12');
			case 12:
				// Change made 27 November for IP/MAC address claiming to accounts
				self::$db->exec('CREATE TABLE phoromatic_system_association_claims (AccountID TEXT, IPAddress TEXT, NetworkMAC TEXT, CreationTime TEXT, UNIQUE(IPAddress, NetworkMAC) ON CONFLICT IGNORE)');
				self::$db->exec('PRAGMA user_version = 13');
			case 13:
				// Change made 30 November for percent complete
				self::$db->exec('ALTER TABLE phoromatic_systems ADD COLUMN TaskPercentComplete INTEGER DEFAULT 0');
				self::$db->exec('PRAGMA user_version = 14');
			case 14:
				// Change made 1 December for more reporting features
				self::$db->exec('ALTER TABLE phoromatic_systems ADD COLUMN CurrentProcessSchedule INTEGER');
				self::$db->exec('ALTER TABLE phoromatic_systems ADD COLUMN TimeToNextCommunication INTEGER DEFAULT 0');
				self::$db->exec('PRAGMA user_version = 15');
			case 15:
				// Change made 1 December for maintenance mode
				self::$db->exec('ALTER TABLE phoromatic_systems ADD COLUMN MaintenanceMode INTEGER DEFAULT 0');
				self::$db->exec('PRAGMA user_version = 16');
			case 16:
				// Change made 31 January for group name
				self::$db->exec('ALTER TABLE phoromatic_accounts ADD COLUMN GroupName TEXT');
				self::$db->exec('PRAGMA user_version = 17');
			case 17:
				// Change made 31 January for Phoromatic Public Result ID
				self::$db->exec('ALTER TABLE phoromatic_results ADD COLUMN PPRID TEXT');
				self::$db->exec('PRAGMA user_version = 18');
			case 18:
				// Change made 31 January for Phoromatic Public Result ID
				self::rebuild_pprid_entries();
				self::$db->exec('CREATE UNIQUE INDEX IF NOT EXISTS public_result_id ON phoromatic_results (PPRID)');
				self::$db->exec('PRAGMA user_version = 19');
			case 19:
				// Change made 31 January
				self::$db->exec('ALTER TABLE phoromatic_account_settings ADD COLUMN LetOtherGroupsViewResults INTEGER DEFAULT 0');
				self::$db->exec('PRAGMA user_version = 20');
			case 20:
				// Change made 4 February
				self::$db->exec('ALTER TABLE phoromatic_account_settings ADD COLUMN PreSeedTestInstalls INTEGER DEFAULT 0');
				self::$db->exec('PRAGMA user_version = 21');
			case 21:
				// Change made 8 February
				self::$db->exec('CREATE TABLE phoromatic_benchmark_tickets (AccountID TEXT, TicketID INTEGER, TicketIssueTime TEXT, Title TEXT, ResultIdentifier TEXT, SuiteToRun TEXT, Description TEXT, State INTEGER DEFAULT 1, LastModifiedBy TEXT, LastModifiedOn TEXT, RunTargetSystems TEXT, RunTargetGroups TEXT, UNIQUE(AccountID, TicketID) ON CONFLICT IGNORE)');
				self::$db->exec('PRAGMA user_version = 22');
			case 22:
				// Change made 8 February
				self::$db->exec('ALTER TABLE phoromatic_results ADD COLUMN BenchmarkTicketID INTEGER');
				self::$db->exec('PRAGMA user_version = 23');
			case 23:
				// Change made 24 February
				self::$db->exec('ALTER TABLE phoromatic_systems ADD COLUMN SystemVariables TEXT');
				self::$db->exec('PRAGMA user_version = 24');
			case 24:
				// Change made 24 February
				self::$db->exec('ALTER TABLE phoromatic_benchmark_tickets ADD COLUMN EnvironmentVariables TEXT');
				self::$db->exec('PRAGMA user_version = 25');
			case 25:
				// Change made 10 March
				self::$db->exec('CREATE TABLE phoromatic_annotations (AccountID TEXT, Type TEXT, ID TEXT, SecondaryID TEXT, AnnotatedTime TEXT, AnnotatedBy TEXT, Annotation TEXT)');
				self::$db->exec('PRAGMA user_version = 26');
			case 26:
				// Change made 26 March
				self::$db->exec('ALTER TABLE phoromatic_systems ADD COLUMN BlockPowerOffs INTEGER DEFAULT 0');
				self::$db->exec('PRAGMA user_version = 27');
			case 27:
				// Change made 27 March
				self::$db->exec('ALTER TABLE phoromatic_account_settings ADD COLUMN PowerOnSystemDaily INTEGER DEFAULT 0');
				self::$db->exec('PRAGMA user_version = 28');
			case 28:
				// Change made 13 April
				self::$db->exec('ALTER TABLE phoromatic_account_settings ADD COLUMN LetPublicViewResults INTEGER DEFAULT 0');
				self::$db->exec('PRAGMA user_version = 29');
			case 29:
				// Change made 12 May
				self::$db->exec('ALTER TABLE phoromatic_systems ADD COLUMN TickThreadEvent TEXT');
				self::$db->exec('PRAGMA user_version = 30');
			case 30:
				// Change made 3 June
				self::$db->exec('ALTER TABLE phoromatic_systems ADD COLUMN CoreVersion INTEGER');
				self::$db->exec('PRAGMA user_version = 31');
			case 31:
				// Change made 5 June
				self::$db->exec('CREATE TABLE phoromatic_schedules_trigger_skips (AccountID TEXT, ScheduleID INTEGER, Trigger TEXT, UNIQUE(AccountID, ScheduleID, Trigger) ON CONFLICT IGNORE)');
				self::$db->exec('PRAGMA user_version = 32');
			case 32:
				// Change made 10 June
				self::$db->exec('ALTER TABLE phoromatic_schedules_triggers ADD COLUMN SubTarget TEXT');
				self::$db->exec('PRAGMA user_version = 33');
			case 33:
				// Change made 4 October
				self::$db->exec('ALTER TABLE phoromatic_account_settings ADD COLUMN AutoApproveNewSystems INTEGER DEFAULT 0');
				self::$db->exec('PRAGMA user_version = 34');
			case 34:
				// Change made 7 January 2016
				self::$db->exec('ALTER TABLE phoromatic_account_settings ADD COLUMN LimitNetworkCommunication INTEGER DEFAULT 0');
				self::$db->exec('PRAGMA user_version = 35');
			case 35:
				// Change made 15 April 2016
				self::$db->exec('ALTER TABLE phoromatic_systems ADD COLUMN CurrentProcessTicket INTEGER DEFAULT 0');
				self::$db->exec('PRAGMA user_version = 36');
			case 36:
				// Change made 30 May 2017 for introducing run priority
				self::$db->exec('ALTER TABLE phoromatic_schedules ADD COLUMN RunPriority INTEGER DEFAULT 100');
				self::$db->exec('PRAGMA user_version = 37');
			case 37:
				// Previously these were called on each load but no reason to redundantly do so...
				self::$db->exec('PRAGMA journal_mode = WAL');
				self::$db->exec('PRAGMA synchronous = NORMAL');
				self::$db->exec('PRAGMA user_version = 38');
			case 38:
			case 39:
				// Change made 15 May 2021
				self::$db->exec('ALTER TABLE phoromatic_systems ADD COLUMN SystemProperties TEXT');
				self::$db->exec('PRAGMA user_version = 40');
			case 40:
				// Change made 6 July 2021
				self::$db->exec('ALTER TABLE phoromatic_account_settings ADD COLUMN AllowAnyDataForLogFiles INTEGER DEFAULT 0');
				self::$db->exec('PRAGMA user_version = 41');
			case 41:
				// Change made November 2021
				self::$db->exec('ALTER TABLE phoromatic_account_settings ADD COLUMN UploadInstallLogs INTEGER DEFAULT 0');
				self::$db->exec('ALTER TABLE phoromatic_account_settings ADD COLUMN UploadRunLogs INTEGER DEFAULT 0');
				self::$db->exec('PRAGMA user_version = 42');
			case 42:
				// Change made November 2021
				self::$db->exec('ALTER TABLE phoromatic_schedules ADD COLUMN EnvironmentVariables TEXT');
				self::$db->exec('PRAGMA user_version = 43');
			case 43:
				// Change made December 2021
				self::$db->exec('ALTER TABLE phoromatic_account_settings ADD COLUMN GlobalEnvironmentVariables TEXT');
				self::$db->exec('PRAGMA user_version = 44');
			case 44:
				// Change made December 2021
				self::$db->exec('ALTER TABLE phoromatic_account_settings ADD COLUMN ProgressiveResultUploads INTEGER DEFAULT 0');
				self::$db->exec('ALTER TABLE phoromatic_results ADD COLUMN InProgress INTEGER DEFAULT 0');
				self::$db->exec('PRAGMA user_version = 45');
		}
		chmod($db_file, 0600);
		if(!defined('PHOROMATIC_DB_INIT'))
		{
			define('PHOROMATIC_DB_INIT', true);
		}
	}
	public static function generate_result_export_dump($account_id)
	{
		ini_set('memory_limit', '4G');
		pts_file_io::mkdir(self::phoromatic_path() . 'result-export/');
		$export_path = self::phoromatic_path() . 'result-export/' . $account_id . '/';
		pts_file_io::mkdir($export_path);

		$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_schedules WHERE AccountID = :account_id AND State = 1 AND (SELECT COUNT(*) FROM phoromatic_schedules_tests WHERE AccountID = :account_id AND ScheduleID = phoromatic_schedules.ScheduleID) > 0 AND (SELECT COUNT(*) FROM phoromatic_results WHERE AccountID = :account_id AND ScheduleID = phoromatic_schedules.ScheduleID) > 4 ORDER BY Title ASC');
		$stmt->bindValue(':account_id', $account_id);
		$result = $stmt->execute();

		$exported_result_index = array('phoromatic' => array());
		$error_index = array('phoromatic' => array());
		while($result && $row = $result->fetchArray())
		{
			$id = str_replace(' ', '-', strtolower($row['Title']));
			$triggers = array();
			$first_time = time();
			$latest_time = 0;
			$stmt2 = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_results WHERE AccountID = :account_id AND ScheduleID = :schedule_id ORDER BY UploadTime DESC');
			$stmt2->bindValue(':account_id', $row['AccountID']);
			$stmt2->bindValue(':schedule_id', $row['ScheduleID']);
			$result2 = $stmt2->execute();
			pts_file_io::mkdir($export_path);

			while($result2 && $row2 = $result2->fetchArray())
			{
				$composite_xml = phoromatic_server::phoromatic_account_result_path($row2['AccountID'], $row2['UploadID']) . 'composite.xml';
				if(is_file($composite_xml))
				{
					pts_file_io::mkdir($export_path . $id . '/' . $row2['Trigger']);
					pts_file_io::mkdir($export_path . $id . '/' . $row2['Trigger'] . '/' . phoromatic_server::system_id_to_name($row2['SystemID'], $row2['AccountID']));
					copy($composite_xml, $export_path . $id . '/' . $row2['Trigger'] . '/' . phoromatic_server::system_id_to_name($row2['SystemID'], $row2['AccountID']) . '/composite.xml');
				}
				pts_arrays::unique_push($triggers, $row2['Trigger']);

				$utime = strtotime($row2['UploadTime']);
				$first_time = min($first_time, $utime);
				$latest_time = max($latest_time, $utime);
			}

			$exported_result_index['phoromatic'][$id] = array(
				'title' => $row['Title'],
				'id' => $id,
				'description' => $row['Description'],
				'triggers' => $triggers,
				'first_result_time' => $first_time,
				'last_result_time' => $latest_time
				);

			$stmt2 = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_system_client_errors WHERE AccountID = :account_id AND ScheduleID = :schedule_id ORDER BY UploadTime DESC');
			$stmt2->bindValue(':account_id', $row['AccountID']);
			$stmt2->bindValue(':schedule_id', $row['ScheduleID']);
			$result2 = $stmt2->execute();
			$error_index['phoromatic'][$id] = array();
			while($result2 && $row2 = $result2->fetchArray())
			{
				// Only show last month of errors for now
				if(strtotime('-1 month') > strtotime($row2['UploadTime']))
					continue;

				$error_index['phoromatic'][$id][] = array(
					'system' => phoromatic_server::system_id_to_name($row2['SystemID'], $row2['AccountID']),
					'trigger' => $row2['TriggerID'],
					'test' => $row2['TestIdentifier'],
					'test_description' => $row2['TestArguments'],
					'error' => $row2['ErrorMessage'],
					'error_time' => strtotime($row2['UploadTime']),
					);
			}

		}
		$exported_result_index = json_encode($exported_result_index, JSON_PRETTY_PRINT);
		$error_index = json_encode($error_index, JSON_PRETTY_PRINT);
		file_put_contents($export_path . '/export-index.json', $exported_result_index);
		file_put_contents($export_path . '/export-test-errors.json', $error_index);
	}
	public static function send_email($to, $subject, $from, $body)
	{
	//	return;
		$msg = '<html><body>' . $body . '
		<hr />
		<p><img src="http://www.phoronix-test-suite.com/web/pts-logo-60.png" /></p>
		<h6><em>The <a href="http://www.phoronix-test-suite.com/">Phoronix Test Suite</a>, <a href="http://www.phoromatic.com/">Phoromatic</a>, and <a href="http://openbenchmarking.org/">OpenBenchmarking.org</a> are products of <a href="http://www.phoronix-media.com/">Phoronix Media</a>.<br />The Phoronix Test Suite is open-source under terms of the GNU GPL. Commercial support, custom engineering, and other services are available by contacting Phoronix Media.<br />&copy; ' . date('Y') . ' Phoronix Media.</em></h6>
		</body></html>';
		$headers = "MIME-Version: 1.0\r\n";
		$headers .= "Content-type:text/html;charset=UTF-8\r\n";
		$headers .= "From: Phoromatic - Phoronix Test Suite <no-reply@phoromatic.com>\r\n";
		$headers .= "Reply-To: " . $from . " <" . $from . ">\r\n";
		mail($to, $subject, $msg, $headers);
	}
	protected static function rebuild_pprid_entries()
	{
		$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_results ORDER BY UploadTime ASC');
		$result = $stmt ? $stmt->execute() : false;

		while($result && ($row = $result->fetchArray()))
		{
			$stmt = phoromatic_server::$db->prepare('UPDATE phoromatic_results SET PPRID = :pprid WHERE AccountID = :account_id AND UploadID = :upload_id');
			$stmt->bindValue(':account_id', $row['AccountID']);
			$stmt->bindValue(':upload_id', $row['UploadID']);
			$stmt->bindValue(':pprid', phoromatic_server::compute_pprid($row['AccountID'], $row['SystemID'], $row['UploadTime'], $row['XmlUploadHash']));
			$stmt->execute();
		}
	}
	public static function compute_pprid($account_id, $system_id, $upload_time, $xml_upload_hash)
	{
		return base_convert(sha1($account_id . ' ' . $system_id . ' ' . $xml_upload_hash . ' ' . $upload_time), 16, 36);
	}
	public static function system_id_to_name($system_id, $aid = false)
	{
		static $system_names;

		if(!isset($system_names[$system_id]) || empty($system_names[$system_id]))
		{
			$stmt = phoromatic_server::$db->prepare('SELECT Title FROM phoromatic_systems WHERE AccountID = :account_id AND SystemID = :system_id');
			$stmt->bindValue(':account_id', ($aid ? $aid : $_SESSION['AccountID']));
			$stmt->bindValue(':system_id', $system_id);
			$result = $stmt->execute();
			$row = $result ? $result->fetchArray() : false;
			$system_names[$system_id] = isset($row['Title']) ? $row['Title'] : false;
		}

		return $system_names[$system_id];
	}
	public static function system_id_variables($system_id, $aid = false)
	{
		$stmt = phoromatic_server::$db->prepare('SELECT SystemVariables FROM phoromatic_systems WHERE AccountID = :account_id AND SystemID = :system_id');
		$stmt->bindValue(':account_id', ($aid ? $aid : $_SESSION['AccountID']));
		$stmt->bindValue(':system_id', $system_id);
		$result = $stmt->execute();
		$row = $result ? $result->fetchArray() : false;
		return isset($row['SystemVariables']) ? $row['SystemVariables'] : null;
	}
	public static function schedule_id_to_name($schedule_id, $aid = false)
	{
		static $schedule_names;

		if(!isset($schedule_names[$schedule_id]) || empty($schedule_names[$schedule_id]))
		{
			$stmt = phoromatic_server::$db->prepare('SELECT Title FROM phoromatic_schedules WHERE AccountID = :account_id AND ScheduleID = :schedule_id');
			$stmt->bindValue(':account_id', ($aid ? $aid : $_SESSION['AccountID']));
			$stmt->bindValue(':schedule_id', $schedule_id);
			$result = $stmt->execute();
			$row = $result ? $result->fetchArray() : false;
			$schedule_names[$schedule_id] = isset($row['Title']) ? $row['Title'] : false;
		}

		return $schedule_names[$schedule_id];
	}
	public static function ticket_id_to_name($ticket_id, $aid = false)
	{
		static $ticket_names;

		if(!isset($ticket_names[$ticket_id]) || empty($ticket_names[$ticket_id]))
		{
			$stmt = phoromatic_server::$db->prepare('SELECT Title FROM phoromatic_benchmark_tickets WHERE AccountID = :account_id AND TicketID = :ticket_id');
			$stmt->bindValue(':account_id', ($aid ? $aid : $_SESSION['AccountID']));
			$stmt->bindValue(':ticket_id', $ticket_id);
			$result = $stmt->execute();
			$row = $result ? $result->fetchArray() : false;
			$ticket_names[$ticket_id] = isset($row['Title']) ? $row['Title'] : false;
		}

		return $ticket_names[$ticket_id];
	}
	public static function account_id_to_group_admin_email($account_id)
	{
		$stmt = phoromatic_server::$db->prepare('SELECT Email FROM phoromatic_users WHERE AccountID = :account_id AND AdminLevel = 1 ORDER BY CreatedOn ASC LIMIT 1');
		$stmt->bindValue(':account_id', $account_id);
		$result = $stmt->execute();
		$row = $result ? $result->fetchArray() : false;
		return isset($row['Email']) ? $row['Email'] : false;
	}
	public static function account_id_to_group_name($account_id)
	{
		static $group_names;

		if(!isset($group_names[$account_id]) || empty($group_names[$account_id]))
		{
			$stmt = phoromatic_server::$db->prepare('SELECT GroupName FROM phoromatic_accounts WHERE AccountID = :account_id');
			$stmt->bindValue(':account_id', $account_id);
			$result = $stmt->execute();
			$row = $result ? $result->fetchArray() : false;
			$group_names[$account_id] = isset($row['GroupName']) ? $row['GroupName'] : null;
		}

		return $group_names[$account_id];
	}
	public static function account_created_on($account_id)
	{
		static $created_dates;

		if(!isset($created_dates[$account_id]) || empty($created_dates[$account_id]))
		{
			$stmt = phoromatic_server::$db->prepare('SELECT CreatedOn FROM phoromatic_accounts WHERE AccountID = :account_id');
			$stmt->bindValue(':account_id', $account_id);
			$result = $stmt->execute();
			$row = $result ? $result->fetchArray() : false;
			$created_dates[$account_id] = isset($row['CreatedOn']) ? $row['CreatedOn'] : null;
		}

		return $created_dates[$account_id];
	}
	public static function recently_active_systems($account_id)
	{
		$systems = array();
		$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_systems WHERE AccountID = :account_id AND State >= 0 ORDER BY LastCommunication DESC');
		$stmt->bindValue(':account_id', $account_id);
		$result = $stmt->execute();

		while($result && $row = $result->fetchArray())
		{
			if(strtotime($row['LastCommunication']) < (time() - 21600))
				break;
			if(stripos($row['CurrentTask'], 'shutdown') !== false || stripos($row['CurrentTask'], 'exit') !== false)
				continue;

			$systems[] = $row;
		}

		return $systems;
	}
	public static function check_for_benchmark_ticket_result_match($benchmark_id, $account_id, $system_id, $ticket_issue_time)
	{
		$stmt = phoromatic_server::$db->prepare('SELECT UploadID FROM phoromatic_results WHERE AccountID = :account_id AND SystemID = :system_id AND BenchmarkTicketID = :benchmark_id AND UploadTime > :ticket_issue_time AND InProgress = 0');
		$stmt->bindValue(':account_id', $account_id);
		$stmt->bindValue(':system_id', $system_id);
		$stmt->bindValue(':benchmark_id', $benchmark_id);
		$stmt->bindValue(':ticket_issue_time', $ticket_issue_time);
		$result = $stmt->execute();

		if($result && $result->fetchArray() != false)
		{
			return true;
		}

		return false;
	}
	public static function check_for_triggered_result_match($schedule_id, $trigger_id, $account_id, $system_id)
	{
		$stmt = phoromatic_server::$db->prepare('SELECT UploadID FROM phoromatic_results WHERE AccountID = :account_id AND ScheduleID = :schedule_id AND Trigger = :trigger AND SystemID = :system_id AND InProgress = 0');
		$stmt->bindValue(':account_id', $account_id);
		$stmt->bindValue(':system_id', $system_id);
		$stmt->bindValue(':schedule_id', $schedule_id);
		$stmt->bindValue(':trigger', $trigger_id);
		$result = $stmt->execute();

		if($result && $result->fetchArray() != false)
		{
			return true;
		}

		if(!defined('CLIENT_CORE_VERSION') || CLIENT_CORE_VERSION < 10601)
		{
			/*
			Traditionally to avoid a test trigger/schedule getting caught in a possible endless loop if all contained test(s) failed
			where there would then be no results to upload so at the end of the run would just re-query Phoromatic Server and start over,
			the below check would compare the error count associated with that system and schedule/trigger exceeded the count of tests to run.
			The check works "okay" but if rebooting mid-test and then repeating any prior failed tests, etc, could be among the scenarios leading to higher
			error count and thus abandoning that test trigger even if not all the tests were yet attempted. Plus some test runs could generate multiple errors per test.

			With PTS 10.6 the pieces are in place to allow run errors to be embedded into the XML result file with null results and work gracefully throughout the system.
			So even if all the test runs fail / error out, there will still be a valid result file and thus still uploading the result file to the Phoromatic Server.

			Thus this check with newer versions isn't necessary since the result file check will be accurate and always be uploaded if all tests fail,
			plus being more convenient for showing errors within the result page, etc.
			*/

			// See if the system attempted to run the trigger/schedule combination but reported an error during the process....
			$stmt = phoromatic_server::$db->prepare('SELECT COUNT(ErrorMessage) AS ErrorCount FROM phoromatic_system_client_errors WHERE AccountID = :account_id AND SystemID = :system_id AND ScheduleID = :schedule_id AND TriggerID = :trigger');
			$stmt->bindValue(':account_id', $account_id);
			$stmt->bindValue(':system_id', $system_id);
			$stmt->bindValue(':schedule_id', $schedule_id);
			$stmt->bindValue(':trigger', $trigger_id);
			$result = $stmt->execute();

			if($result != false && ($row = $result->fetchArray()) != false)
			{
				$error_count = $row['ErrorCount'];
				$stmt = phoromatic_server::$db->prepare('SELECT COUNT(*) AS TestCount FROM phoromatic_schedules_tests WHERE AccountID = :account_id AND ScheduleID = :schedule_id');
				$stmt->bindValue(':account_id', $account_id);
				$stmt->bindValue(':schedule_id', $schedule_id);
				$result = $stmt->execute();
				$row = $result ? $result->fetchArray() : null;

				// See if error count was greater than test count, meaning all of the tests might have failed
				if($error_count >= $row['TestCount'])
				{
					return true;
				}
			}
		}

		$stmt = phoromatic_server::$db->prepare('SELECT ScheduleID FROM phoromatic_schedules_trigger_skips WHERE AccountID = :account_id AND ScheduleID = :schedule_id AND Trigger = :trigger');
		$stmt->bindValue(':account_id', $account_id);
		$stmt->bindValue(':schedule_id', $schedule_id);
		$stmt->bindValue(':trigger', $trigger_id);
		$result = $stmt->execute();

		if($result && $result->fetchArray() != false)
		{
			return true;
		}

		return false;
	}
	public static function user_friendly_timedate($time)
	{
		return date('j F H:i', strtotime($time));
	}
	public static function get_system_details($account_id, $system_id)
	{
		$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_systems WHERE AccountID = :account_id AND SystemID = :system_id LIMIT 1');
		$stmt->bindValue(':account_id', $account_id);
		$stmt->bindValue(':system_id', $system_id);
		$result = $stmt->execute();
		$row = $result ? $result->fetchArray() : null;

		return $row;
	}
	public static function systems_associated_with_schedule($account_id, $schedule_id)
	{
		$system_ids = array();
		$stmt = phoromatic_server::$db->prepare('SELECT RunTargetSystems, RunTargetGroups FROM phoromatic_schedules WHERE AccountID = :account_id AND ScheduleID = :schedule_id LIMIT 1');
		$stmt->bindValue(':account_id', $account_id);
		$stmt->bindValue(':schedule_id', $schedule_id);
		$result = $stmt->execute();

		if($result && $row = $result->fetchArray())
		{
			foreach(explode(',', $row['RunTargetSystems']) as $sys)
			{
				if(empty($sys))
					continue;

				$system_ids[] = $sys;
			}

			foreach(explode(',', $row['RunTargetGroups']) as $group)
			{
				if(empty($group))
					continue;

				$stmt = phoromatic_server::$db->prepare('SELECT SystemID FROM phoromatic_systems WHERE AccountID = :account_id AND Groups LIKE :sgroup AND State > 0 ORDER BY Title ASC');
				$stmt->bindValue(':account_id', $account_id);
				$stmt->bindValue(':sgroup', '%#' . $group . '#%');
				$result = $stmt->execute();

				while($result && $row = $result->fetchArray())
				{
					$system_ids[] = $row['SystemID'];
				}
			}
		}

		return array_unique($system_ids);
	}
	public static function schedules_that_run_on_system($account_id, $system_id)
	{
		$schedules = array();
		$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_schedules WHERE AccountID = :account_id AND State = 1 ORDER BY TITLE ASC');
		$stmt->bindValue(':account_id', $account_id);
		$result = $stmt->execute();

		while($result && $row = $result->fetchArray())
		{
			// Make sure this test schedule is supposed to work on given system
			if(!in_array($system_id, explode(',', $row['RunTargetSystems'])))
			{
				$stmt = phoromatic_server::$db->prepare('SELECT Groups FROM phoromatic_systems WHERE AccountID = :account_id AND SystemID = :system_id LIMIT 1');
				$stmt->bindValue(':account_id', $account_id);
				$stmt->bindValue(':system_id', $system_id);
				$sys_result = $stmt->execute();
				$sys_row = $sys_result ? $sys_result->fetchArray() : null;

				if(!isset($sys_row['Groups']) || empty($sys_row['Groups']))
				{
					continue;
				}

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

			$schedules[] = $row;
		}

		return $schedules;
	}
	public static function system_has_outstanding_jobs($account_id, $system_id, $time_offset = 0, $include_low_priority_work = true)
	{
		$stmt = phoromatic_server::$db->prepare('SELECT Groups FROM phoromatic_systems WHERE AccountID = :account_id AND SystemID = :system_id LIMIT 1');
		$stmt->bindValue(':account_id', $account_id);
		$stmt->bindValue(':system_id', $system_id);
		$sys_result = $stmt->execute();
		$sys_row = $sys_result ? $sys_result->fetchArray() : null;


		// See if there's an open schedule to run for system
		$schedule_row = self::system_check_for_open_schedule_run($account_id, $system_id, $time_offset, $sys_row, $include_low_priority_work);
		if($schedule_row != false)
		{
			return $schedule_row;
		}

		// See if there's an open benchmark ticket for system
		$ticket_row = self::system_check_for_open_benchmark_ticket($account_id, $system_id, $sys_row);
		if($ticket_row != false)
		{
			return $ticket_row;
		}

		return false;
	}
	public static function system_check_for_open_schedule_run($account_id, $system_id, $time_offset = 0, &$sys_row = null, $include_low_priority_work = true)
	{
		if($include_low_priority_work)
		{
			$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_schedules WHERE AccountID = :account_id AND State = 1 AND (SELECT COUNT(*) FROM phoromatic_schedules_tests WHERE AccountID = :account_id AND ScheduleID = phoromatic_schedules.ScheduleID) > 0 ORDER BY RunPriority DESC');
		}
		else
		{
			// Only include higher priority work
			$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_schedules WHERE AccountID = :account_id AND State = 1 AND RunPriority >= 100 AND (SELECT COUNT(*) FROM phoromatic_schedules_tests WHERE AccountID = :account_id AND ScheduleID = phoromatic_schedules.ScheduleID) > 0 ORDER BY RunPriority DESC');
		}

		$stmt->bindValue(':account_id', $account_id);
		$result = $stmt->execute();
		$day_of_week_int = date('N') - 1;

		while($result && $row = $result->fetchArray())
		{
			// Make sure this test schedule is supposed to work on given system
			if(!in_array($system_id, explode(',', $row['RunTargetSystems'])))
			{
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
				if($row['RunAt'] <= date('H.i', (time() + $time_offset)))
				{
					$trigger_id = date('Y-m-d');
					if(!phoromatic_server::check_for_triggered_result_match($row['ScheduleID'], $trigger_id, $account_id, $system_id))
					{
						return $row;
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
				// See if any sub-targeting is happening
				if($trigger_row['SubTarget'] !== null)
				{
					$sub_targets = explode(',', $trigger_row['SubTarget']);
					if(!empty($sub_targets) && !in_array($system_id, $sub_targets))
					{
						// This system isn't part of the sub-targeted trigger
						continue;
					}
				}

				if(substr($trigger_row['TriggeredOn'], 0, 10) == date('Y-m-d') || substr($trigger_row['TriggeredOn'], 0, 10) == date('Y-m-d', (time() - 60 * 60 * 24)))
				{
					if(!phoromatic_server::check_for_triggered_result_match($row['ScheduleID'], $trigger_row['Trigger'], $account_id, $system_id))
					{
						$row['Trigger'] = $trigger_row['Trigger'];
						return $row;
					}
				}
			}
		}

		return false;
	}
	public static function system_check_for_open_benchmark_ticket($account_id, $system_id, &$sys_row)
	{
		$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_benchmark_tickets WHERE AccountID = :account_id AND State = 1 AND TicketIssueTime < :current_time AND TicketIssueTime > :yesterday ORDER BY TicketIssueTime ASC');
		//echo phoromatic_server::$db->lastErrorMsg();
		$stmt->bindValue(':account_id', $account_id);
		$stmt->bindValue(':current_time', time());
		$stmt->bindValue(':yesterday', (time() - (60 * 60 * 24)));
		$result = $stmt->execute();

		while($result && $row = $result->fetchArray())
		{
			// Make sure this test schedule is supposed to work on given system
			if(!in_array($system_id, explode(',', $row['RunTargetSystems'])))
			{
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

			if(!phoromatic_server::check_for_benchmark_ticket_result_match($row['TicketID'], $account_id, $system_id, $row['TicketIssueTime']))
			{
				if(strpos($row['EnvironmentVariables'], 'PTS_CONCURRENT_TEST_RUNS') !== false && is_file(phoromatic_server::phoromatic_account_stress_log_path($account_id, $row['TicketID']) . $system_id . '.log') && filemtime(phoromatic_server::phoromatic_account_stress_log_path($account_id, $row['TicketID']) . $system_id . '.log') > $row['TicketIssueTime'])
				{
					// Stress log uploaded
					continue;
				}

				return $row;
			}
		}

		return false;
	}
	public static function time_to_next_scheduled_job($account_id, $system_id)
	{
		$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_schedules WHERE AccountID = :account_id AND State = 1 AND (SELECT COUNT(*) FROM phoromatic_schedules_tests WHERE AccountID = :account_id AND ScheduleID = phoromatic_schedules.ScheduleID) > 0');
		$stmt->bindValue(':account_id', $account_id);
		$result = $stmt->execute();
		$scheduled_times = array();

		while($result && $row = $result->fetchArray())
		{
			if(empty($row['RunAt']))
			{
				continue;
			}

			// Make sure this test schedule is supposed to work on given system
			if(!in_array($system_id, explode(',', $row['RunTargetSystems'])))
			{
				$stmt = phoromatic_server::$db->prepare('SELECT Groups FROM phoromatic_systems WHERE AccountID = :account_id AND SystemID = :system_id LIMIT 1');
				$stmt->bindValue(':account_id', $account_id);
				$stmt->bindValue(':system_id', $system_id);
				$sys_result = $stmt->execute();
				$sys_row = $sys_result ? $sys_result->fetchArray() : null;

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

			list($hour, $minute) = explode('.', $row['RunAt']);
			if(!is_numeric($hour) || !is_numeric($minute))
			{
				continue;
			}

			foreach(explode(',', $row['ActiveOn']) as $active_day)
			{
				$scheduled_times[] = (($active_day * 1440) + ($hour * 60) + $minute );
			}
		}

		sort($scheduled_times);

		$now_time = ((date('N') - 1) * 1440) + (date('G') * 60) + date('i');
		foreach($scheduled_times as $i => $time_to_next_job)
		{
			if($now_time > $time_to_next_job)
				unset($scheduled_times[$i]);
		}

		if(!empty($scheduled_times))
			return array_shift($scheduled_times) - $now_time;

		return false;
	}
	public static function estimated_time_remaining_diff($estimated_minutes, $last_comm)
	{
		if($estimated_minutes > 0)
		{
			$estimated_completion = strtotime($last_comm) + ($estimated_minutes * 60);

			if(time() < $estimated_completion)
			{
				return ceil(($estimated_completion - time()) / 60);
			}
		}

		return 0;
	}
	public static function systems_appearing_down($account_id = null)
	{
		if(isset($_SESSION['AccountID']))
			$account_id = $_SESSION['AccountID'];

		$systems = array();
		$stmt = phoromatic_server::$db->prepare('SELECT SystemID, Title, LastCommunication, CurrentTask FROM phoromatic_systems WHERE AccountID = :account_id AND State >= 0 ORDER BY LastCommunication DESC');
		$stmt->bindValue(':account_id', $account_id);
		$result = $stmt->execute();
		while($result && $row = $result->fetchArray())
		{
			if(phoromatic_server::system_check_if_down($_SESSION['AccountID'], $row['SystemID'], $row['LastCommunication'], $row['CurrentTask']))
			{
				$systems[] = $row['SystemID'];
			}
		}

		return $systems;
	}
	public static function schedules_today($account_id)
	{
		$schedules = array();
		$show_day_of_week = date('N') - 1;
		$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_schedules WHERE AccountID = :account_id AND State = 1 AND (SELECT COUNT(*) FROM phoromatic_schedules_tests WHERE AccountID = :account_id AND ScheduleID = phoromatic_schedules.ScheduleID) > 0 AND ActiveOn LIKE :active_day ORDER BY RunAt ASC');
		$stmt->bindValue(':account_id', $account_id);
		$stmt->bindValue(':active_day', '%' . $show_day_of_week . '%');
		$result = $stmt->execute();

		while($result && $row = $result->fetchArray())
		{
			$schedules[] = $row;
		}

		return $schedules;
	}
	public static function schedules_total($account_id)
	{
		$schedules = array();
		$show_day_of_week = date('N') - 1;
		$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_schedules WHERE AccountID = :account_id AND State = 1 AND (SELECT COUNT(*) FROM phoromatic_schedules_tests WHERE AccountID = :account_id AND ScheduleID = phoromatic_schedules.ScheduleID) > 0 ORDER BY RunAt ASC');
		$stmt->bindValue(':account_id', $account_id);
		$result = $stmt->execute();

		while($result && $row = $result->fetchArray())
		{
			$schedules[] = $row;
		}

		return $schedules;
	}
	public static function benchmark_tickets_today($account_id)
	{
		$tickets = array();
		$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_benchmark_tickets WHERE AccountID = :account_id AND State >= 0 AND TicketIssueTime > :time_cutoff ORDER BY TicketIssueTime DESC');
		$stmt->bindValue(':account_id', $account_id);
		$stmt->bindValue(':time_cutoff', (time() - (60 * 60 * 24 * 14)));
		$result = $stmt->execute();

		while($result && $row = $result->fetchArray())
		{
			$tickets[] = $row;
		}

		return $tickets;
	}
	public static function systems_idling($account_id)
	{
		$systems = array();
		$stmt = phoromatic_server::$db->prepare('SELECT SystemID FROM phoromatic_systems WHERE AccountID = :account_id AND State >= 0 AND CurrentTask LIKE \'%Idling%\' ORDER BY LastCommunication DESC');
		$stmt->bindValue(':account_id', $account_id);
		$result = $stmt->execute();
		while($result && $row = $result->fetchArray())
		{
			$systems[] = $row;
		}

		return $systems;
	}
	public static function systems_shutdown($account_id)
	{
		$systems = array();
		$stmt = phoromatic_server::$db->prepare('SELECT SystemID FROM phoromatic_systems WHERE AccountID = :account_id AND State >= 0 AND CurrentTask LIKE \'%Shutdown%\' ORDER BY LastCommunication DESC');
		$stmt->bindValue(':account_id', $account_id);
		$result = $stmt->execute();
		while($result && $row = $result->fetchArray())
		{
			$systems[] = $row;
		}

		return $systems;
	}
	public static function systems_running_tests($account_id)
	{
		$systems = array();
		$stmt = phoromatic_server::$db->prepare('SELECT SystemID FROM phoromatic_systems WHERE AccountID = :account_id AND State >= 0 AND (CurrentTask LIKE \'%Running%\' OR CurrentTask LIKE \'%Installing%\' OR CurrentTask LIKE \'%Benchmark%\') ORDER BY LastCommunication DESC');
		$stmt->bindValue(':account_id', $account_id);
		$result = $stmt->execute();
		while($result && $row = $result->fetchArray())
		{
			$systems[] = $row;
		}

		return $systems;
	}
	public static function systems_total($account_id)
	{
		$systems = array();
		$stmt = phoromatic_server::$db->prepare('SELECT SystemID FROM phoromatic_systems WHERE AccountID = :account_id AND State >= 0 ORDER BY LastCommunication DESC');
		$stmt->bindValue(':account_id', $account_id);
		$result = $stmt->execute();
		while($result && $row = $result->fetchArray())
		{
			$systems[] = $row;
		}

		return $systems;
	}
	public static function test_result_count_for_test_profile($account_id, $test_profile)
	{
		$stmt = phoromatic_server::$db->prepare('SELECT COUNT(*) As TotalCount FROM phoromatic_results_results WHERE AccountID = :account_id AND TestProfile LIKE :tp');
		$stmt->bindValue(':account_id', $account_id);
		$stmt->bindValue(':tp', $test_profile . '%');
		$result = $stmt->execute();
		if($result && $row = $result->fetchArray())
		{
			return $row['TotalCount'];
		}

		return 0;
	}
	public static function test_result_count_for_test_profiles($account_id)
	{
		$stmt = phoromatic_server::$db->prepare('SELECT COUNT(*) As Count, TestProfile FROM phoromatic_results_results WHERE AccountID = :account_id GROUP BY TestProfile ORDER BY TestProfile ASC');
		$stmt->bindValue(':account_id', $account_id);
		$result = $stmt->execute();
		$tests = array();
		while($result && $row = $result->fetchArray())
		{
			$tests[$row['TestProfile']] = $row['Count'];
		}

		return $tests;
	}
	public static function test_results($account_id, $time_limit = false)
	{
		$results = array();
		$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_results WHERE AccountID = :account_id ORDER BY UploadTime DESC');
		$stmt->bindValue(':account_id', $account_id);
		$result = $stmt->execute();
		while($result && $row = $result->fetchArray())
		{
			if($time_limit != false && strtotime($row['UploadTime']) < $time_limit)
			{
				break;
			}

			$results[] = $row;
		}

		return $results;
	}
	public static function test_results_total($account_id)
	{
		$stmt = phoromatic_server::$db->prepare('SELECT COUNT(*) As ResultCount FROM phoromatic_results WHERE AccountID = :account_id');
		$stmt->bindValue(':account_id', $account_id);
		$result = $stmt->execute();
		if($result && $row = $result->fetchArray())
		{
			return $row['ResultCount'];
		}

		return 0;
	}
	public static function test_results_benchmark_count($account_id)
	{
		$results = array();
		$stmt = phoromatic_server::$db->prepare('SELECT COUNT(*) AS BenchmarkCount FROM phoromatic_results_results WHERE AccountID = :account_id');
		$stmt->bindValue(':account_id', $account_id);
		$result = $stmt->execute();
		if($result && $row = $result->fetchArray())
		{
			return $row['BenchmarkCount'];
		}

		return 0;
	}
	public static function system_check_if_down($account_id, $system_id, $last_communication, $current_task)
	{
		$last_comm = strtotime($last_communication);
		return ((phoromatic_server::system_has_outstanding_jobs($account_id, $system_id, -600) && (($last_comm < (time() - 5400) && stripos($current_task, 'Running') === false) || $last_comm < (time() - 7200) || ($last_comm < (time() - 600) && stripos($current_task, 'Shutdown') !== false))) || ($last_comm < (time() -7200) && (stripos($current_task, 'running') !== false ||  stripos($current_task, 'setting') !== false))) || $current_task == 'Unknown';
	}
	public static function estimated_time_remaining_string($estimated_minutes, $last_comm, $append = 'Remaining')
	{
		$remaining = phoromatic_server::estimated_time_remaining_diff($estimated_minutes, $last_comm);
		return $remaining > 0 ? '~' . pts_strings::plural_handler($remaining, 'Minute') . ' ' . $append : null;
	}
}

if(!is_dir(phoromatic_server::phoromatic_path() . 'accounts'))
{
	mkdir(phoromatic_server::phoromatic_path() . 'accounts');
}

?>
