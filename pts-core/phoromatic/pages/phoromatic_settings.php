<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2022, Phoronix Media
	Copyright (C) 2008 - 2022, Michael Larabel

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

class phoromatic_settings implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Settings';
	}
	public static function page_header()
	{
		return null;
	}
	public static function preload($PAGE)
	{
		return true;
	}
	public static function render_page_process($PATH)
	{
			echo phoromatic_webui_header_logged_in();

			$main = '<h1>Settings</h1>
				<h2>User Settings</h2>
				<p>User settings are specific to your particular account, in cases where there are multiple individuals/accounts managing the same test systems and data.</p>
				';

			$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_user_settings WHERE AccountID = :account_id AND UserID = :user_id');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$stmt->bindValue(':user_id', $_SESSION['UserID']);
			$result = $stmt->execute();
			$row = $result->fetchArray();

			$user_settings = array(
				'Email' => array(
					'NotifyOnResultUploads' => 'Send notification when test results are uploaded to Phoromatic.',
					'NotifyOnWarnings' => 'Send notification when any warnings are generated on a test system.',
					'NotifyOnNewSystems' => 'Send notification when new test systems are added.',
					'NotifyOnHungSystems' => 'Send notification when system(s) appear hung.'
					)
				);

			$main .= '<form name="system_form" id="system_form" action="?settings" method="post">';
			foreach($user_settings as $section => $section_settings)
			{
				$main .= '<h3>' . $section . '</h3><p>';
				foreach($section_settings as $key => $setting)
				{
					if(isset($_POST['user_settings_update']))
					{
						if(isset($_POST[$key]) && $_POST[$key] == 'yes')
						{
							$row[$key] = 1;
						}
						else
						{
							$row[$key] = 0;
						}

						$stmt = phoromatic_server::$db->prepare('UPDATE phoromatic_user_settings SET ' . $key . ' = :val WHERE AccountID = :account_id AND UserID = :user_id');
						$stmt->bindValue(':account_id', $_SESSION['AccountID']);
						$stmt->bindValue(':user_id', $_SESSION['UserID']);
						$stmt->bindValue(':val', $row[$key]);
						$stmt->execute();
						//echo phoromatic_server::$db->lastErrorMsg();
					}

					$main .= '<input type="checkbox" name="' . $key . '" ' . (isset($row[$key]) && $row[$key] == 1 ? 'checked="checked" ' : '') . 'value="yes" /> ' . $setting . '<br />';
				}
				$main .= '</p>';
			}
			$main .= '<p><input type="hidden" value="1" name="user_settings_update" /><input type="submit" value="Save User Settings" /></p>';
			$main .= '</form>';

			if(!PHOROMATIC_USER_IS_VIEWER)
			{
				$main .= '<hr />
				<h2>Account Settings</h2>
				<p>Account settings are system-wide, in cases where there are multiple individuals/accounts managing the same test systems and data.</p>';

				$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_account_settings WHERE AccountID = :account_id');
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$result = $stmt->execute();
				$row = $result->fetchArray();

				$env_vars_show = !empty($row['GlobalEnvironmentVariables']) ? pts_strings::parse_value_string_vars($row['GlobalEnvironmentVariables']) : array();

				$account_settings = array(
					'Global Settings' => array(
						'ArchiveResultsLocally' => 'Archive test results on local test systems after the results have been uploaded.',
						'UploadSystemLogs' => 'Upload system logs from clients when uploading test results.',
						'UploadInstallLogs' => 'Upload test installation logs from clients when uploading test results.',
						'UploadRunLogs' => 'Upload test run-time logs from clients when uploading test results.',
						'ProgressiveResultUploads' => 'Allow clients to stream results progressively to the Phoromatic Server as tests are finished (the ability to see in-progress result files on the Phoromatic Server rather than waiting until all tests are finished).',
						'RunInstallCommand' => 'For all test schedules, always run the install command for test(s) prior to running them on the system.',
						'ForceInstallTests' => 'For all test schedules, force the test installation/re-installation of tests each time prior to running the test.',
						//'SystemSensorMonitoring' => 'Enable the system sensor monitoring while tests are taking place.',
						'UploadResultsToOpenBenchmarking' => 'For all test schedules, also upload test results to OpenBenchmarking.org.',
						// AllowAnyDataForLogFiles is enabled by default on PTS 10.8.1+
						//'AllowAnyDataForLogFiles' => 'When clients are uploading system log files to the Phoromatic Server, allow any data (non-text data) to be uploaded rather than enforcing text-only log files.',
						'PowerOffWhenDone' => 'Power off system(s) when scheduled tests are completed for the day.',
						'PreSeedTestInstalls' => 'Attempt to pre-install commonly used tests on client systems while idling.',
						'NetworkPowerUpWhenNeeded' => 'Use network Wake-On-LAN to power on systems when needed.',
						'LetOtherGroupsViewResults' => 'Let other accounts/groups on this Phoromatic Server view (read-only) this account\'s results.',
						'LetPublicViewResults' => 'Allow public/unauthenticated visitors to access these test results from <a href="/public.php">the public viewer page</a>.',
						'PowerOnSystemDaily' => 'Attempt to power-on systems daily (unless there\'s a daily test schedule / trigger on the system) to maintain the DHCP lease on the network, update any software/hardware information, etc. When the daily update is done, the system will power off unless there\'s a test to run and the power-off setting above is enabled. This option is namely useful for systems that otherwise may be idling/powered-off for long periods of time between tests.',
						'AutoApproveNewSystems' => 'Enabling this option will make new test systems immediately available for this account rather than the default behavior of first needing an administrator to approve/deny the system via the Phoromatic Server web interface. With this option enabled, the systems are automatically approved by default but can be later disabled/removed via the Phoromatic web interface.',
						'LimitNetworkCommunication' => 'Limit network communication. Only enable this option if your Phoromatic Server is slow, there are thousands of systems running benchmarks, and/or you are not interested in the real-time system monitoring and other functionality. This setting will limit the network communication to the point of the Phoromatic Server mostly being used just as a result aggregation point.'
						)
					);

				$main .= '<form name="system_form" id="system_form" action="?settings" method="post">';
				$settings_updated = false;
				foreach($account_settings as $section => $section_settings)
				{
					$main .= '<h3>' . $section . '</h3><p>';
					foreach($section_settings as $key => $setting)
					{
						if(isset($_POST['account_settings_update']))
						{
							if(isset($_POST[$key]) && $_POST[$key] == 'yes')
							{
								$row[$key] = 1;
							}
							else
							{
								$row[$key] = 0;
							}

							$stmt = phoromatic_server::$db->prepare('UPDATE phoromatic_account_settings SET ' . $key . ' = :val WHERE AccountID = :account_id');
							$stmt->bindValue(':account_id', $_SESSION['AccountID']);
							$stmt->bindValue(':val', $row[$key]);
							$stmt->execute();

							if($settings_updated == false)
							{
								phoromatic_add_activity_stream_event('settings', null, 'modified');
								$settings_updated = true;
							}
							//echo phoromatic_server::$db->lastErrorMsg();
						}

						$main .= '<input type="checkbox" name="' . $key . '" ' . (isset($row[$key]) && $row[$key] === 1 ? 'checked="checked" ' : '') . 'value="yes" /> ' . $setting . '<br />';
					}
					$main .= '</p>';
				}

				if(isset($_POST['env_var_update']))
				{
					$env_vars_show = array();
					$env_vars = array();
					foreach(pts_env::get_posted_options('phoromatic') as $ei => $ev)
					{
						array_push($env_vars, $ei . '=' . $ev);
						$env_vars_show[$ei] = $ev;
					}
					$env_vars = implode(';', $env_vars);
					$stmt = phoromatic_server::$db->prepare('UPDATE phoromatic_account_settings SET GlobalEnvironmentVariables = :val WHERE AccountID = :account_id');
					$stmt->bindValue(':account_id', $_SESSION['AccountID']);
					$stmt->bindValue(':val', $env_vars);
					$stmt->execute();
				}

				$main .= '<p><input type="hidden" value="1" name="account_settings_update" /><input type="submit" value="Save Account Settings" /></p>';
				$main .= '</form>';

				$main .= '<form name="system_form" id="system_form" action="?settings" method="post"><hr />';
				$main .= '<h2>Global Environment Variable Option Overrides</h2> <p>The below options are for environment variable controls that can be set remotely by the Phoromatic Server for use with Phoromatic clients be on the Phoronix Test Suite 10.8 or newer. See the Phoronix Test Suite documentation for more information on these environment variables. The below options will set the values unconditionally for all test schedules / benchmark tickets. Via the individual test schedules / benchmark tickets the environment variables can be set for that given testing rather than globally.</p>' . pts_env::get_html_options('phoromatic', $env_vars_show);

				$main .= '<p><input type="hidden" value="1" name="env_var_update" /><input type="submit" value="Save Global Override Settings" /></p>';
				$main .= '</form>';
			}

			$main .= '<hr />
			<h2>Cache Settings</h2>
			<p>Proceed to the <a href="?caches">download cache page</a> for information about the Phoromatic Server\'s download caches.</p>';

			$main .= '<hr />
			<h2>User Password</h2>
			<p>Proceed to the <a href="?password">password page</a> if you wish to update your account\'s password.</p>';

			if(!PHOROMATIC_USER_IS_VIEWER)
			{
				$main .= '<hr />
				<h2>Build A Suite</h2>
				<p><a href="?build_suite">Create a custom test suite</a>.</p>';


				$update_script_path = phoromatic_server::phoromatic_account_path($_SESSION['AccountID']) . 'client-update-script.sh';
				if(isset($_POST['client_update_script']))
				{
					file_put_contents($update_script_path, str_replace("\r\n", PHP_EOL, $_POST['client_update_script']));
				}

				if(!is_file($update_script_path))
				{
					$script_contents = pts_file_io::file_get_contents(PTS_CORE_STATIC_PATH . 'sample-pts-client-update-script.sh');
				}
				else
				{
					$script_contents = pts_file_io::file_get_contents($update_script_path);
				}

				$main .= '<form name="update_client_script_form" id="update_client_script_form" action="?settings" method="post">
<hr /><h2>Auto-Updating Clients</h2><p>If desired, you can paste a script in the below field if you wish to have Phoronix Test Suite / Phoromatic clients attempt to auto-update themselves. Any commands copied below are automatically executed by the client upon completing a test / beginning a new idle process / prior to attempting a system shutdown. If your script determines the client is to be updated, it should <em>reboot</em> the system afterwards to ensure no issues in the upgrade of the Phoronix Test Suite installation. A reference/example script is provided by default. This update script feature does not attempt to update the Phoromatic Server software.</p>
				<p><textarea style="width: 80%; height: 400px;" name="client_update_script" id="client_update_script">' . $script_contents . '</textarea></p>
				<p><input type="submit" value="Save Client Auto-Update Script" /></p>
				</form>';
			}

			echo '<div id="pts_phoromatic_main_area">' . $main . '</div>';
			echo phoromatic_webui_footer();
	}
}

?>
