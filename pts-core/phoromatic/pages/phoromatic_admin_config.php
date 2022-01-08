<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2014 - 2022, Phoronix Media
	Copyright (C) 2014 - 2022, Michael Larabel

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

class phoromatic_admin_config implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Phoromatic Server Configuration';
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
		if($_SESSION['AdminLevel'] != -40)
		{
			header('Location: /?main');
		}
		$main = null;

		if(isset($_POST['new_phoromatic_path']) && !empty($_POST['new_phoromatic_path']))
		{
			$new_dir = dirname($_POST['new_phoromatic_path']);

			if(!is_dir($new_dir))
			{
				$main .= '<h2 style="color: red;"><em>' . $new_dir . '</em> must be a valid directory.</h2>';
			}
			else if(!is_writable($new_dir))
			{
				$main .= '<h2 style="color: red;"><em>' . $new_dir . '</em> is not a writable location.</h2>';
			}
			else
			{
				if(!is_dir($_POST['new_phoromatic_path']))
				{
					if(mkdir($_POST['new_phoromatic_path']) == false)
					{
						$main .= '<h2 style="color: red;">Failed to make directory <em>' . $_POST['new_phoromatic_path'] . '</em>.</h2>';
					}
				}

				if(is_dir($_POST['new_phoromatic_path']))
				{
					$new_phoromatic_dir = pts_strings::add_trailing_slash($_POST['new_phoromatic_path']);

					$d = glob($new_phoromatic_dir . '*');
					if(!empty($d))
					{
						$new_phoromatic_dir .= 'phoromatic/';
						pts_file_io::mkdir($new_phoromatic_dir);
					}

					$d = glob($new_phoromatic_dir . '*');
					if(!empty($d))
					{
						$main .= '<h2 style="color: red;"><em>' . $new_phoromatic_dir . '</em> must be an empty directory.</h2>';
					}
					else
					{
						if(pts_file_io::copy(phoromatic_server::phoromatic_path(), $new_phoromatic_dir))
						{
							pts_config::user_config_generate(array('PhoromaticStorage' => $new_phoromatic_dir));
							header('Location: /?admin');
						}
						else
						{
							$main .= '<h2 style="color: red;"><em>Failed to copy old Phoromatic data to new location.</h2>';
						}
					}
				}
			}
		}
		if(isset($_POST['new_dc_path']) && !empty($_POST['new_dc_path']))
		{
			$new_dir = dirname($_POST['new_dc_path']);

			if(!is_dir($new_dir))
			{
				$main .= '<h2 style="color: red;"><em>' . $new_dir . '</em> must be a valid directory.</h2>';
			}
			else if(!is_writable($new_dir))
			{
				$main .= '<h2 style="color: red;"><em>' . $new_dir . '</em> is not a writable location.</h2>';
			}
			else
			{
				if(!is_dir($_POST['new_dc_path']))
				{
					if(mkdir($_POST['new_dc_path']) == false)
					{
						$main .= '<h2 style="color: red;">Failed to make directory <em>' . $_POST['new_dc_path'] . '</em>.</h2>';
					}
				}

				if(is_dir($_POST['new_dc_path']))
				{
					$new_dc_dir = pts_strings::add_trailing_slash($_POST['new_dc_path']);

					if(pts_file_io::copy(pts_client::download_cache_path(), $new_dc_dir))
					{
						pts_config::user_config_generate(array('CacheDirectory' => $new_dc_dir));
						header('Location: /?admin');
					}
					else
					{
						$main .= '<h2 style="color: red;"><em>Failed to copy old Phoromatic data to new location.</h2>';
					}
				}
			}
		}
		if(isset($_POST['new_proxy_address']) && isset($_POST['new_proxy_port']))
		{
			if(pts_network::http_get_contents('http://www.phoronix-test-suite.com/PTS', $_POST['new_proxy_address'], $_POST['new_proxy_port']) == 'PTS')
			{
				pts_config::user_config_generate(array(
					'PhoronixTestSuite/Options/Networking/ProxyAddress' => $_POST['new_proxy_address'],
					'PhoronixTestSuite/Options/Networking/ProxyPort' => $_POST['new_proxy_port']
					));
			}
			else
			{
				$main .= '<h2 style="color: red;">Failed to connect via proxy server.</h2>';
			}
		}
		if(isset($_POST['new_http_port']) && isset($_POST['new_ws_port']))
		{
			if(empty($_POST['new_http_port']) || (!is_numeric($_POST['new_http_port']) && $_POST['new_http_port'] != 'RANDOM'))
			{
				$main .= '<h2 style="color: red;">The HTTP port must be a valid port number or <em>RANDOM</em>.</h2>';
			}
			if(empty($_POST['new_ws_port']) || (!is_numeric($_POST['new_ws_port']) && $_POST['new_ws_port'] != 'RANDOM'))
			{
				$main .= '<h2 style="color: red;">The WebSocket port must be a valid port number or <em>RANDOM</em>.</h2>';
			}
			pts_config::user_config_generate(array(
				'PhoronixTestSuite/Options/Server/RemoteAccessPort' => $_POST['new_http_port'],
				'PhoronixTestSuite/Options/Server/WebSocketPort' => $_POST['new_ws_port']
				));
		}
		if(isset($_POST['add_new_users_to_account']))
		{
			if(empty($_POST['add_new_users_to_account']))
			{
				phoromatic_server::save_setting('add_new_users_to_account', null);
			}
			else
			{
				$stmt = phoromatic_server::$db->prepare('SELECT COUNT(AccountID) AS AccountHitCount FROM phoromatic_accounts WHERE AccountID = :account_id');
				$stmt->bindValue(':account_id', $_POST['add_new_users_to_account']);
				$result = $stmt->execute();
				$row = $result->fetchArray();
				if(empty($row['AccountHitCount']))
				{
					$main .= '<h2 style="color: red;"><em>' . $_POST['add_new_users_to_account'] . '</em> is not a valid account ID.</h2>';
				}
				else
				{
					phoromatic_server::save_setting('add_new_users_to_account', $_POST['add_new_users_to_account']);
				}
			}
		}
		if(isset($_POST['account_creation_alt']))
		{
			phoromatic_server::save_setting('account_creation_alt', $_POST['account_creation_alt']);
		}
		if(isset($_POST['main_page_message']))
		{
			phoromatic_server::save_setting('main_page_message', $_POST['main_page_message']);
		}
		if(isset($_POST['force_result_sharing']))
		{
			phoromatic_server::save_setting('force_result_sharing', $_POST['force_result_sharing']);
		}
		if(isset($_POST['show_local_tests_only']))
		{
			phoromatic_server::save_setting('show_local_tests_only', $_POST['show_local_tests_only']);
		}
		if(isset($_POST['allow_test_profile_creation']))
		{
			phoromatic_server::save_setting('allow_test_profile_creation', $_POST['allow_test_profile_creation']);
		}
		if(isset($_POST['new_admin_support_email']))
		{
			phoromatic_server::save_setting('admin_support_email', $_POST['new_admin_support_email']);
		}
		if(isset($_POST['rebuild_results_db']))
		{
			foreach(pts_file_io::glob(phoromatic_server::phoromatic_path() . 'accounts/*/results/*/composite.xml') as $composite_xml)
			{
				$account_id = basename(dirname(dirname(dirname($composite_xml))));
				$upload_id = basename(dirname($composite_xml));

				$result_file = new pts_result_file($composite_xml);

				// Validate the XML
				$relative_id = 0;
				foreach($result_file->get_result_objects() as $result_object)
				{
					$relative_id++;
					$stmt = phoromatic_server::$db->prepare('INSERT INTO phoromatic_results_results (AccountID, UploadID, AbstractID, TestProfile, ComparisonHash) VALUES (:account_id, :upload_id, :abstract_id, :test_profile, :comparison_hash)');
					$stmt->bindValue(':account_id', $account_id);
					$stmt->bindValue(':upload_id', $upload_id);
					$stmt->bindValue(':abstract_id', $relative_id);
					$stmt->bindValue(':test_profile', $result_object->test_profile->get_identifier());
					$stmt->bindValue(':comparison_hash', $result_object->get_comparison_hash(true, false));
					$result = $stmt->execute();
				}

				if($relative_id > 0)
				{
					foreach($result_file->get_systems() as $s)
					{
						$stmt = phoromatic_server::$db->prepare('INSERT INTO phoromatic_results_systems (AccountID, UploadID, SystemIdentifier, Hardware, Software) VALUES (:account_id, :upload_id, :system_identifier, :hardware, :software)');
						$stmt->bindValue(':account_id', $account_id);
						$stmt->bindValue(':upload_id', $upload_id);
						$stmt->bindValue(':system_identifier', $s->get_identifier());
						$stmt->bindValue(':hardware', $s->get_hardware());
						$stmt->bindValue(':software', $s->get_software());
						$result = $stmt->execute();
					}
				}
			}
		}

		$main .= '<h1>Phoromatic Server Configuration</h1>';

		$main .= '<h2>Phoromatic Storage Location</h2>';
		$main .= '<p>The Phoromatic Storage location is where all Phoromatic-specific test results, account data, and other information is archived. This path is controlled via the <em>' . pts_config::get_config_file_location() . '</em> configuration file with the <em>PhoromaticStorage</em> element. Adjusting the directory from the user configuration XML file is the recommended way to adjust the Phoromatic storage path when the Phoromatic Server is not running, while using the below form is an alternative method to attempt to live migrate the storage path.</p>';
		$main .= '<p><strong>Current Storage Path:</strong> ' . phoromatic_server::phoromatic_path() . '</p>';
		$main .= '<form action="' . $_SERVER['REQUEST_URI'] . '" name="update_phoromatic_path" method="post">';
		$main .= '<p><input type="text" name="new_phoromatic_path" value="' . (isset($_POST['new_phoromatic_path']) ? $_POST['new_phoromatic_path'] : null) . '" /></p>';
		$main .= '<p><input name="submit" value="Update Phoromatic Storage Location" type="submit" /></p>';
		$main .= '</form>';

		$main .= '<hr /><h2>Download Cache Location</h2>';
		$main .= '<p>The download cache is where the Phoronix Test Suite is able to make an archive of files needed by test profiles. The Phoromatic Server is then able to allow Phoronix Test Suite client systems on the intranet. To add test files to this cache on the Phoromatic Server, run <strong>phoronix-test-suite make-download-cache <em>&lt;the test identifers you wish to download and cache&gt;</em></strong>.</p>';
		$main .= '<p><strong>Current Download Cache Path:</strong> ' . pts_client::download_cache_path() . '</p>';
		$main .= '<form action="' . $_SERVER['REQUEST_URI'] . '" name="update_dc_path" method="post">';
		$main .= '<p><input type="text" name="new_dc_path" value="' . (isset($_POST['new_dc_path']) ? $_POST['new_dc_path'] : null) . '" /></p>';
		$main .= '<p><input name="submit" value="Update Download Cache Location" type="submit" /></p>';
		$main .= '</form>';

		$main .= '<hr /><h2>Network Proxy</h2>';
		$main .= '<p>If a network proxy is needed for the Phoromatic Server to access the open Internet, please provide the IP address and HTTP port address below.</p>';
		$main .= '<form action="' . $_SERVER['REQUEST_URI'] . '" name="update_proxy" method="post">';
		$main .= '<p><strong>Proxy HTTP Port:</strong> <input type="text" name="new_proxy_port" size="4" value="' . (isset($_POST['new_proxy_port']) ? $_POST['new_proxy_port'] : pts_config::read_user_config('PhoronixTestSuite/Options/Networking/ProxyPort')) . '" /></p>';
		$main .= '<p><strong>Proxy IP Address:</strong> <input type="text" name="new_proxy_address" value="' . (isset($_POST['new_proxy_address']) ? $_POST['new_proxy_address'] : pts_config::read_user_config('PhoronixTestSuite/Options/Networking/ProxyAddress')) . '" /></p>';
		$main .= '<p><input name="submit" value="Update Network Proxy" type="submit" /></p>';
		$main .= '</form>';

		$main .= '<hr /><h2>Phoromatic Server Ports</h2>';
		$main .= '<p>The HTTP and WebSocket ports for the Phoromatic Server can be adjusted via this form or the user configuration XML file. The new ports will not go into effect until the Phoromatic Server instance has been restarted.</p>';
		$main .= '<form action="' . $_SERVER['REQUEST_URI'] . '" name="update_ports" method="post">';
		$main .= '<p><strong>HTTP Port:</strong> <input type="text" name="new_http_port" size="4" value="' . (isset($_POST['new_http_port']) ? $_POST['new_http_port'] : pts_config::read_user_config('PhoronixTestSuite/Options/Server/RemoteAccessPort')) . '" /></p>';
		$main .= '<p><strong>WebSocket Port:</strong> <input type="text" name="new_ws_port" size="4" value="' . (isset($_POST['new_ws_port']) ? $_POST['new_ws_port'] : pts_config::read_user_config('PhoronixTestSuite/Options/Server/WebSocketPort')) . '" /></p>';
		$main .= '<p><input name="submit" value="Update Web Ports" type="submit" /></p>';
		$main .= '</form>';

		$main .= '<hr /><h2>Support Email Address</h2>';
		$main .= '<p>This email address will be shown as the sender of emails regarding new account registration and other non-group-related messages. This email address may also be shown as a support email address in case of user problems.</p>';
		$main .= '<form action="' . $_SERVER['REQUEST_URI'] . '" name="support_email" method="post">';
		$main .= '<p><strong>E-Mail:</strong> <input type="text" name="new_admin_support_email" value="' . phoromatic_server::read_setting('admin_support_email') . '" /></p>';
		$main .= '<p><input name="submit" value="Update E-Mail Address" type="submit" /></p>';
		$main .= '</form>';

		$main .= '<hr /><h1>Account Creation</h1>';
		$main .= '<h2>Add To Existing Account</h2><p>Whenever a new account is created via the main log-in page, rather than creating a new group account, you can opt to have the account added as a viewer to an existing group of accounts. To do so, enter the account ID in the field below. The user is added to that account ID with viewer privileges while the main administrator for that account can elevate the privileges from their account\'s Users page. You can find the list of account IDs via the main rootadmin page account listing. Leave this field blank to disable the feature. This option only affects the creation of new accounts.</p>';
		$main .= '<form action="' . $_SERVER['REQUEST_URI'] . '" name="add_accounts_to_one" method="post">';
		$main .= '<p><strong>Main Account ID:</strong> <input type="text" name="add_new_users_to_account" size="6" value="' . phoromatic_server::read_setting('add_new_users_to_account') . '" /></p>';
		$main .= '<p><input name="submit" value="Update Account Handling" type="submit" /></p>';
		$main .= '</form>';

		$main .= '<hr /><h1>Account Creation</h1>';
		$main .= '<p>By default, new accounts can be created at-will from the main page of the Phoromatic Server web interface. <strong>To disable the ability to create new accounts from the main welcome page</strong>, enter a message in the field below -- e.g. account creation disabled, contact XYZ department via email to request a new account, or other string to present to the user in place of the account creation box. Leave this box empty to allow new accounts to be created. HTML input is allowed.</p>';
		$main .= '<form action="' . $_SERVER['REQUEST_URI'] . '" name="account_creation_text" method="post">';
		$main .= '<p><strong>Account Creation String:</strong> <textarea name="account_creation_alt" cols="50" rows="4">' . phoromatic_server::read_setting('account_creation_alt') . '</textarea></p>';
		$main .= '<p><input name="submit" value="Update Account Handling" type="submit" /></p>';
		$main .= '</form>';

		$main .= '<hr /><h1>Main Page Message</h1>';
		$main .= '<p>If you wish to present users with a custom message once logging into their Phoromatic account, set the HTML-allowed string below and it will be shown on the main page once logging in.</p>';
		$main .= '<form action="' . $_SERVER['REQUEST_URI'] . '" name="main_page_message" method="post">';
		$main .= '<p><strong>Main Page Message String:</strong> <textarea name="main_page_message" cols="50" rows="4">' . phoromatic_server::read_setting('main_page_message') . '</textarea></p>';
		$main .= '<p><input name="submit" value="Update Main Page Message" type="submit" /></p>';
		$main .= '</form>';

		$main .= '<hr /><h1>Force Results To Be Shared</h1>';
		$main .= '<p>If you wish to force that all accounts/groups on this Phoromatic Server instance are shared/viewable amongst other groups on this server, set this value to True. Otherwise the result sharing is limited to each group\'s selected option on the account settings page.</p>';
		$main .= '<form action="' . $_SERVER['REQUEST_URI'] . '" name="force_result_share" method="post">';
		$main .= '<p><strong>Force Result Sharing:</strong> <select name="force_result_sharing"><option value="0">False</option><option value="1" ' . (phoromatic_server::read_setting('force_result_sharing') ? 'selected="selected"' : null) . '>True</option></select></p>';
		$main .= '<p><input name="submit" value="Update" type="submit" /></p>';
		$main .= '</form>';

		$main .= '<hr /><h1>Only Advertise Tests With Files Locally Cached</h1>';
		$main .= '<p>Enabling this option will only advertise test profiles on the Phoromatic Server web interface if the needed files for that test are present within the Phoromatic Server\'s PTS download cache. This feature is particularly useful for environments where the client test system lacks direct Internet access.</p>';
		$main .= '<form action="' . $_SERVER['REQUEST_URI'] . '" name="show_local_tests_only" method="post">';
		$main .= '<p><strong>Only Advertise Cached Tests:</strong> <select name="show_local_tests_only"><option value="0">False</option><option value="1" ' . (phoromatic_server::read_setting('show_local_tests_only') ? 'selected="selected"' : null) . '>True</option></select></p>';
		$main .= '<p><input name="submit" value="Update" type="submit" /></p>';
		$main .= '</form>';

		$main .= '<hr /><h1>Allow Test Profiles To Be Created From Phoromatic Server</h1>';
		$main .= '<p>Enabling this option will allow a basic test profile creation wizard page to appear within the Phoromatic Server. This can be used for creating new test profiles but caution should be taken to ensure only trusted users have access to the Phoromatic Server on a private intranet to avoid any rogue tests being created, etc.</p>';
		$main .= '<form action="' . $_SERVER['REQUEST_URI'] . '" name="allow_test_profile_creation" method="post">';
		$main .= '<p><strong>Allow Test Profile Creation:</strong> <select name="allow_test_profile_creation"><option value="0">False</option><option value="1" ' . (phoromatic_server::read_setting('allow_test_profile_creation') ? 'selected="selected"' : null) . '>True</option></select></p>';
		$main .= '<p><input name="submit" value="Update" type="submit" /></p>';
		$main .= '</form>';

		$main .= '<hr /><h1>Rebuild Results/Systems SQLite Tables</h1>';
		$main .= '<p>If you somehow damaged some of your SQLite tables, this option will attempt to rebuild the phoromatic_results_results and phoromatic_results_systems tables.</p>';
		$main .= '<form action="' . $_SERVER['REQUEST_URI'] . '" name="rebuild_results_db" method="post">';
		$main .= '<p><strong>Force Results Table Rebuild:</strong> <select name="rebuild_results_db"><option value="0">False</option><option value="1" ' . (phoromatic_server::read_setting('rebuild_results_db') ? 'selected="selected"' : null) . '>True</option></select></p>';
		$main .= '<p><input name="submit" value="Rebuild Results Table" type="submit" /></p>';
		$main .= '</form>';

		echo phoromatic_webui_header_logged_in();
		echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in());
		echo phoromatic_webui_footer();
	}
}

?>
