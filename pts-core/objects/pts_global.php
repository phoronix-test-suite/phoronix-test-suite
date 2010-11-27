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

	public static function is_global_id($global_id)
	{
		// Checks if a string is a valid Phoronix Global ID
		return pts_global::is_valid_global_id_format($global_id) && pts_network::http_get_contents("http://www.phoronix-test-suite.com/global/profile-check.php?id=" . $global_id) == "REMOTE_FILE";
	}
	public static function openbenchmarking_host()
	{
		static $host = null;

		if($host == null)
		{
			// Use HTTPS if OpenSSL is available as a check to see if HTTPS can be handled
			$host = (extension_loaded("openssl") ? "https://" : "http://") . "www.openbenchmarking.org/";
		}

		return $host;
	}
	public static function is_valid_global_id_format($global_id)
	{
		// Basic checking to see if the string is possibly a Global ID
		$is_valid = true;

		if(!isset($global_id[12])) // Shortest Possible ID would be X-000-000-000, needs to be at least 13 chars
		{
			$is_valid = false;
		}

		if($is_valid && count(explode("-", $global_id)) < 3) // Global IDs should have three (or more) dashes
		{
			$is_valid = false;
		}

		return $is_valid;
	}
	public static function is_valid_gsid_format($gsid)
	{
		$gsid_valid = false;

		if(strlen($gsid) == 9)
		{
			if(strlen(pts_strings::keep_in_string(substr($gsid, 0, 6), pts_strings::CHAR_LETTER)) == 6 &&
			strlen(pts_strings::keep_in_string(substr($gsid, 6, 3), pts_strings::CHAR_NUMERIC)) == 3)
			{
				$gsid_valid = true;
			}
		}

		return $gsid_valid;
	}
	public static function download_result_xml($global_id)
	{
		// Download a saved test result from Phoronix Global
		return pts_network::http_get_contents((strpos($global_id, self::$result_xml_download_base_url) === 0 ? null : self::$result_xml_download_base_url) . $global_id);
	}
	public static function clone_global_result($global_id, $render_graphs = true)
	{
		return pts_client::save_test_result($global_id . "/composite.xml", pts_global::download_result_xml($global_id), $render_graphs);
	}
	public static function get_public_result_url($global_id)
	{
		return self::$result_xml_public_base_url . $global_id;
	}
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
	public static function request_gsid()
	{
		$upload_data = array(
			"client_version" => PTS_VERSION,
			"client_os" => phodevi::read_property("system", "vendor-identifier")
			);
		$gsid = pts_network::http_upload_via_post(self::openbenchmarking_host() . "extern/request-gsid.php", $upload_data);

		return pts_global::is_valid_gsid_format($gsid) ? $gsid : false;
	}
	public static function result_upload_supported($result_file)
	{
		$result_file = new pts_result_file($result_file);

		foreach($result_file->get_result_objects() as $result_object)
		{
			$test_profile = new pts_test_profile($result_object->test_profile->get_identifier());

			if($test_profile->allow_results_sharing() == false)
			{
				echo "\n" . $result_object->test_profile->get_identifier() . " does not allow test results to be uploaded to Phoronix Global.\n\n";
				return false;
			}
		}

		return true;
	}
	public static function upload_test_result($result_file, $tags = "")
	{
		return false; // TODO: block Iveland result uploads

		// TODO: use the pts_results_nye_XmlReader->validate() to ensure it fits the XML Schema

		if(pts_global::result_upload_supported($result_file) == false)
		{
			return false;
		}

		// Upload a test result to the Phoronix Global database
		$test_results = file_get_contents($result_file);
		$test_results = str_replace(array("\n", "\t"), "", $test_results);
		$switch_tags = array("Benchmark>" => "B>", "Results>" => "R>", "Group>" => "G>", "Entry>" => "E>", "Identifier>" => "I>", "Value>" => "V>", "System>" => "S>", "Attributes>" => "A>");

		foreach($switch_tags as $f => $t)
		{
			$test_results = str_replace($f, $t, $test_results);
		}

		$ToUpload = base64_encode($test_results);
		$GlobalUser = pts_global::account_user_name();
		$GlobalKey = pts_config::read_user_config(P_OPTION_GLOBAL_UPLOADKEY, null);
		$tags = base64_encode($tags);
		$return_stream = "";

		$upload_data = array("result_xml" => $ToUpload, "global_user" => $GlobalUser, "global_key" => $GlobalKey, "tags" => $tags, "gsid" => PTS_GSID);

		return pts_network::http_upload_via_post("http://www.phoronix-test-suite.com/global/user-upload.php", $upload_data);
	}
	public static function upload_usage_data($task, $data)
	{
		switch($task)
		{
			case "test_complete":
				list($test_result, $time_elapsed) = $data;
				$upload_data = array("test_identifier" => $test_result->test_profile->get_identifier(), "test_version" => $test_result->test_profile->get_test_profile_version(), "elapsed_time" => $time_elapsed);
				pts_network::http_upload_via_post(self::openbenchmarking_host() . "extern/statistics/report-test-completion.php", $upload_data);
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
		pts_network::http_upload_via_post(self::openbenchmarking_host() . "extern/statistics/report-installed-hardware-software.php", $upload_data);
	}
	public static function prompt_user_result_tags($default_tags = null)
	{
		$tags_input = null;

		if((pts_c::$test_flags ^ pts_c::batch_mode) && (pts_c::$test_flags ^ pts_c::auto_mode))
		{
			$tags_input .= pts_user_io::prompt_user_input("Tags are optional and used on Phoronix Global for making it easy to share, search, and organize test results. Example tags could be the type of test performed (i.e. WINE tests) or the hardware used (i.e. Dual Core SMP).\n\nEnter the tags you wish to provide (separated by commas)", true);

			$tags_input = pts_strings::keep_in_string($tags_input, pts_strings::CHAR_LETTER | pts_strings::CHAR_NUMERIC | pts_strings::CHAR_DECIMAL | pts_strings::CHAR_DASH | pts_strings::CHAR_UNDERSCORE | pts_strings::CHAR_COLON | pts_strings::CHAR_SPACE | pts_strings::CHAR_COMMA);
			$tags_input = trim($tags_input);
		}

		if($tags_input == null)
		{
			$tags_array = array_merge(pts_arrays::to_array($default_tags), self::auto_generate_user_result_tags());
			$tags_input = implode(", ", $tags_array);
		}

		return $tags_input;
	}
	private static function auto_generate_user_result_tags()
	{
		// Generate automatic tags for the system, used for Phoronix Global
		$tags_array = array();

		switch(phodevi::read_property("cpu", "core-count"))
		{
			case 1:
				array_push($tags_array, "Single Core");
				break;
			case 2:
				array_push($tags_array, "Dual Core");
				break;
			case 3:
				array_push($tags_array, "Triple Core");
				break;
			case 4:
				array_push($tags_array, "Quad Core");
				break;
			case 8:
				array_push($tags_array, "Octal Core");
				break;
			default:
				array_push($tags_array, phodevi::read_property("cpu", "core-count") . " Core");
				break;
		}

		$cpu_type = phodevi::read_property("cpu", "model");
		if(strpos($cpu_type, "Intel") !== false)
		{
			array_push($tags_array, "Intel");
		}
		else if(strpos($cpu_type, "AMD") !== false)
		{
			array_push($tags_array, "AMD");
		}
		else if(strpos($cpu_type, "VIA") !== false)
		{
			array_push($tags_array, "VIA");
		}

		if(IS_ATI_GRAPHICS)
		{
			array_push($tags_array, "ATI");
		}
		else if(IS_NVIDIA_GRAPHICS)
		{
			array_push($tags_array, "NVIDIA");
		}

		if(phodevi::read_property("system", "kernel-architecture") == "x86_64")
		{
			array_push($tags_array, "64-bit");
		}

		array_push($tags_array, phodevi::read_property("system", "operating-system"));

		return $tags_array;
	}
}

?>
