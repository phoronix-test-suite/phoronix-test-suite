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


class phoromatic_admin implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Phoromatic Root Administrator';
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

					if(!empty(glob($new_phoromatic_dir . '*')))
					{
						$new_phoromatic_dir .= 'phoromatic/';
						pts_file_io::mkdir($new_phoromatic_dir);
					}

					if(!empty(glob($new_phoromatic_dir . '*')))
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

					if(pts_file_io::copy(pts_strings::add_trailing_slash(pts_client::parse_home_directory(pts_config::read_user_config('PhoronixTestSuite/Options/Installation/CacheDirectory', PTS_DOWNLOAD_CACHE_PATH))), $new_dc_dir))
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

		$main .= '<h1>Phoromatic Server Administration</h1>';

		$main .= '<hr /><h2>Server Information</h2>';
		$main .= '<p><strong>HTTP Server Port:</strong> ' . getenv('PTS_WEB_PORT') . '<br /><strong>WebSocket Server Port:</strong> ' . getenv('PTS_WEBSOCKET_PORT') . '<br /><strong>Phoromatic Server Path:</strong> ' . phoromatic_server::phoromatic_path() . '</p>';

		$main .= '<hr /><h2>Statistics</h2>';
		$stmt = phoromatic_server::$db->prepare('SELECT COUNT(Title) AS SystemCount FROM phoromatic_systems WHERE State >= 0');
		$result = $stmt->execute();
		$row = $result->fetchArray();
		$stats['Total System Count'] = $row['SystemCount'];
		$stmt = phoromatic_server::$db->prepare('SELECT COUNT(Title) AS ScheduleCount FROM phoromatic_schedules WHERE State >= 1');
		$result = $stmt->execute();
		$row = $result->fetchArray();
		$stats['Total Schedule Count'] = $row['ScheduleCount'];
		$stmt = phoromatic_server::$db->prepare('SELECT COUNT(UploadID) AS ResultCount FROM phoromatic_results');
		$result = $stmt->execute();
		$row = $result->fetchArray();
		$stats['Total Result Count'] = $row['ResultCount'];
		$stmt = phoromatic_server::$db->prepare('SELECT COUNT(ActivityTime) AS ActivityCount FROM phoromatic_activity_stream');
		$stmt->bindValue(':today_date', date('Y-m-d') . '%');
		$result = $stmt->execute();
		$row = $result->fetchArray();
		$stats['Total Activity Count'] = $row['ActivityCount'];

		$main .= '<p>';
		foreach($stats as $what => $c)
			$main .= '<strong>' . $what . ':</strong> ' . $c . '<br />';


		$main .= '<hr /><h2>Accounts</h2>';
		$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_users ORDER BY AccountID,AdminLevel ASC');
		$result = $stmt->execute();
		$row = $result->fetchArray();

		$plevel = -1;
		while($row = $result->fetchArray())
		{
			switch($row['AdminLevel'])
			{
				case 0:
					$level = 'Disabled';
					$offset = null;
					break;
				case 1:
					$level = 'Main Administrator';
					$offset = null;
					break;
				case 2:
					$level = 'Administrator';
					$offset = str_repeat('-', 10);
					break;
				case 3:
					$level = 'Power User';
					$offset = str_repeat('-', 20);
					break;
				case 10:
					$level = 'Viewer';
					$offset = str_repeat('-', 30);
					break;
			}

			if($row['AdminLevel'] == 1)
			{
				if($plevel != -1)
					$main .= '</p>';
				$main .= '<p>';
			}

			$main .= $offset . ' <strong>' . $row['UserName'] . '</strong> (<em>' . $level . '</em>) <strong>Created On:</strong> ' . phoromatic_user_friendly_timedate($row['CreatedOn']) . ' <strong>Last Log-In:</strong> ' . ($row['LastLogin'] != null ? phoromatic_user_friendly_timedate($row['LastLogin']) : 'N/A') . '<br />';
			$plevel = $row['AdminLevel'];
		}
		if($plevel != -1)
			$main .= '</p>';

		$main .= '<hr /><h2>Phoromatic Storage Location</h2>';
		$main .= '<p>The Phoromatic Storage location is where all Phoromatic-specific test results, account data, and other information is archived. This path is controlled via the <em>' . pts_config::get_config_file_location() . '</em> configuration file with the <em>PhoromaticStorage</em> element. Adjusting the directory from the user configuration XML file is the recommended way to adjust the Phoromatic storage path when the Phoromatic Server is not running, while using the below form is an alternative method to attempt to live migrate the storage path.</p>';
		$main .= '<p><strong>Current Storage Path:</strong> ' . phoromatic_server::phoromatic_path() . '</p>';
		$main .= '<form action="' . $_SERVER['REQUEST_URI'] . '" name="update_phoromatic_path" method="post">';
		$main .= '<p><input type="text" name="new_phoromatic_path" value="' . (isset($_POST['new_phoromatic_path']) ? $_POST['new_phoromatic_path'] : null) . '" /></p>';
		$main .= '<p><input name="submit" value="Update Phoromatic Storage Location" type="submit" /></p>';
		$main .= '</form>';

		$main .= '<hr /><h2>Download Cache Location</h2>';
		$main .= '<p>The download cache is where the Phoronix Test Suite is able to make an archive of files needed by test profiles. The Phoromatic Server is then able to allow Phoronix Test Suite client systems on the intranet. To add test files to this cache on the Phoromatic Server, run <strong>phoronix-test-suite make-download-cache <em>&lt;the test identifers you wish to download and cache&gt;</em></strong>.</p>';
		$main .= '<p><strong>Current Download Cache Path:</strong> ' . pts_strings::add_trailing_slash(pts_client::parse_home_directory(pts_config::read_user_config('PhoronixTestSuite/Options/Installation/CacheDirectory', PTS_DOWNLOAD_CACHE_PATH))) . '</p>';
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


		$server_log = explode(PHP_EOL, file_get_contents(getenv('PTS_PHOROMATIC_LOG_LOCATION')));
		foreach($server_log as $i => $line_item)
		{
			if(strpos($line_item, '[200]') !== false || strpos($line_item, '[302]') !== false)
			{
				unset($server_log[$i]);
			}
		}
		$server_log = implode(PHP_EOL, $server_log);

		$main .= '<hr /><h2>Phoromatic Server Log</h2>';
		$main .= '<p><textarea style="width: 80%; height: 400px;">' . $server_log  . '</textarea></p>';

		echo phoromatic_webui_header_logged_in();
		echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in());
		echo phoromatic_webui_footer();
	}
}

?>
