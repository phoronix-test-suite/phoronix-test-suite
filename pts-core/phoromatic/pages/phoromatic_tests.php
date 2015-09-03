<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2015, Phoronix Media
	Copyright (C) 2015, Michael Larabel

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


class phoromatic_tests implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Tests';
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
		$main = null;
		$identifier_item = isset($PATH[1]) ? $PATH[0] . '/' . $PATH[1] : false;

		if($identifier_item && pts_test_profile::is_test_profile($identifier_item))
		{
			$tp = new pts_test_profile($identifier_item);
			$main .= '<h1>' . $tp->get_title() . '</h1><p>' . $tp->get_description() . '</p>';
			$main .= '<p style="font-size: 90%;"><strong>' . $tp->get_test_hardware_type() . ' - ' . phoromatic_server::test_result_count_for_test_profile($_SESSION['AccountID'], $tp->get_identifier(false)) . ' Results On This Account - ' . $tp->get_test_software_type() . ' - Maintained By: ' . $tp->get_maintainer() . ' - Supported Platforms: ' . implode(', ', $tp->get_supported_platforms()) . '</strong></p>';

			$main .= '<h2>Recent Results With This Test</h2>';
			$stmt = phoromatic_server::$db->prepare('SELECT Title, PPRID FROM phoromatic_results WHERE AccountID = :account_id AND UploadID IN (SELECT DISTINCT UploadID FROM phoromatic_results_results WHERE AccountID = :account_id AND TestProfile LIKE :tp) ORDER BY UploadTime DESC LIMIT 30');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$stmt->bindValue(':tp', $tp->get_identifier(false) . '%');
			$result = $stmt->execute();
			while($result && $row = $result->fetchArray())
			{
				$main .= '<h2><a href="/?result/' . $row['PPRID'] . '">' . $row['Title'] . '</h2>';
			}
		}
		else
		{
			$dc = pts_strings::add_trailing_slash(pts_client::parse_home_directory(pts_config::read_user_config('PhoronixTestSuite/Options/Installation/CacheDirectory', PTS_DOWNLOAD_CACHE_PATH)));
			$dc_exists = is_file($dc . 'pts-download-cache.json');
			foreach(pts_openbenchmarking::available_tests() as $test)
			{
				$cache_checked = false;
				if($dc_exists)
				{
					$cache_json = file_get_contents($dc . 'pts-download-cache.json');
					$cache_json = json_decode($cache_json, true);
					if($cache_json && isset($cache_json['phoronix-test-suite']['cached-tests']))
					{
						$cache_checked = true;
						if(!in_array($test, $cache_json['phoronix-test-suite']['cached-tests']))
						{
							//continue;
						}
					}
				}
				if(!$cache_checked && phoromatic_server::read_setting('show_local_tests_only') && pts_test_install_request::test_files_in_cache($test, true, true) == false)
				{
					continue;
				}
				$tp = new pts_test_profile($test);
				$main .= '<h1 style="margin-bottom: 0;"><a href="/?tests/' . $tp->get_identifier(false) . '">' . $tp->get_title() . '</a></h1>';
				$main .= '<p style="font-size: 90%;"><strong>' . $tp->get_test_hardware_type() . '</strong> <em>-</em> ' . phoromatic_server::test_result_count_for_test_profile($_SESSION['AccountID'], $tp->get_identifier(false)) . ' Results On This Account' . ' </p>';
			}
		}

		echo phoromatic_webui_header_logged_in();
		echo '<div id="pts_phoromatic_main_area">' . $main . '</div>';
		echo phoromatic_webui_footer();
	}
}

?>
