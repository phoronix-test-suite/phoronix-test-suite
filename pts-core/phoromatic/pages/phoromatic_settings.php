<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2015, Phoronix Media
	Copyright (C) 2008 - 2015, Michael Larabel

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

				$account_settings = array(
					'Global Settings' => array(
						'ArchiveResultsLocally' => 'Archive test results on local test systems after the results have been uploaded.',
						'UploadSystemLogs' => 'Upload system logs when uploading test results.',
						'RunInstallCommand' => 'For all test schedules, always run the install command for test(s) prior to running them on the system.',
						'ForceInstallTests' => 'For all test schedules, force the test installation/re-installation of tests each time prior to running the test.',
						'SystemSensorMonitoring' => 'Enable the system sensor monitoring while tests are taking place.',
						'UploadResultsToOpenBenchmarking' => 'For all test schedules, also upload test results to OpenBenchmarking.org.',
						'PowerOffWhenDone' => 'Power off system(s) when scheduled tests are completed for the day.',
						'NetworkPowerUpWhenNeeded' => 'Use network Wake-On-LAN to power on systems when needed.',
						'LetOtherGroupsViewResults' => 'Let other accounts/groups on this Phoromatic Server view (read-only) this account\'s results.',
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

				$main .= '<p><input type="hidden" value="1" name="account_settings_update" /><input type="submit" value="Save Account Settings" /></p>';
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
			}

			echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in());
			echo phoromatic_webui_footer();
	}
}

?>
