<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel

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

class pts_global
{
	private static $result_xml_download_base_url = "http://www.phoronix-test-suite.com/global/pts-results-viewer.php?id=";
	private static $result_xml_public_base_url = "http://global.phoronix-test-suite.com/index.php?k=profile&u=";

	public static function create_account($username, $password)
	{
		$uploadkey = pts_network::http_get_contents("http://www.phoronix-test-suite.com/global/account-verify.php?user_name=" . $username . "&user_md5_pass=" . $password);

		if(!empty($uploadkey))
		{
			pts_config::user_config_generate(array(P_OPTION_GLOBAL_USERNAME => $username, P_OPTION_GLOBAL_UPLOADKEY => $uploadkey));
		}

		return !empty($uploadkey);
	}
	public static function account_user_name()
	{
		$username = pts_config::read_user_config(P_OPTION_GLOBAL_USERNAME, null);
		return !empty($username) && $username != "Default User" ? $username : false;
	}
	public static function upload_usage_data($task, $data)
	{
		switch($task)
		{
			case "test_complete":
				list($test_result, $time_elapsed) = $data;
				$upload_data = array("test_identifier" => $test_result->test_profile->get_identifier(), "test_version" => $test_result->test_profile->get_test_profile_version(), "elapsed_time" => $time_elapsed);
				pts_network::http_upload_via_post(pts_openbenchmarking::openbenchmarking_host() . "extern/statistics/report-test-completion.php", $upload_data);
				break;
		}
	}
	public static function upload_hwsw_data($to_report)
	{
		foreach($to_report as $component => &$value)
		{
			if(empty($value))
			{
				unset($to_report[$component]);
				continue;
			}

			$value = $component . '=' . $value;
		}

		$upload_data = array("report_hwsw" => implode(';', $to_report), "gsid" => PTS_GSID);
		pts_network::http_upload_via_post(pts_openbenchmarking::openbenchmarking_host() . "extern/statistics/report-installed-hardware-software.php", $upload_data);
	}
}

?>
