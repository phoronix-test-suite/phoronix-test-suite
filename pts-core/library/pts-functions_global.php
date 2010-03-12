<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
	pts-functions_global.php: Functions needed for Phoronix Global.

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

function pts_is_global_id($global_id)
{
	// Checks if a string is a valid Phoronix Global ID
	return pts_global_valid_id_string($global_id) && pts_http_get_contents("http://www.phoronix-test-suite.com/global/profile-check.php?id=" . $global_id) == "REMOTE_FILE";
}
function pts_global_download_xml($global_id)
{
	// Download a saved test result from Phoronix Global
	return pts_http_get_contents(pts_global_download_base_url() . $global_id);
}
function pts_global_download_base_url()
{
	return "http://www.phoronix-test-suite.com/global/pts-results-viewer.php?id=";
}
function pts_clone_from_global($global_id, $render_graphs = true)
{
	return pts_save_result($global_id . "/composite.xml", pts_global_download_xml($global_id), $render_graphs);
}
function pts_global_public_url($global_id)
{
	return "http://global.phoronix-test-suite.com/index.php?k=profile&u=" . $global_id;
}
function pts_global_valid_id_string($global_id)
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
function pts_global_setup_account($username, $password)
{
	$uploadkey = pts_http_get_contents("http://www.phoronix-test-suite.com/global/account-verify.php?user_name=" . $username . "&user_md5_pass=" . $password);

	if(!empty($uploadkey))
	{
		pts_config::user_config_generate(array(P_OPTION_GLOBAL_USERNAME => $username, P_OPTION_GLOBAL_UPLOADKEY => $uploadkey));
	}

	return !empty($uploadkey);
}
function pts_global_request_gsid()
{
	$gsid = pts_http_get_contents("http://www.phoronix-test-suite.com/global/request-gs-id.php?pts=" . PTS_VERSION . "&os=" . phodevi::read_property("system", "vendor-identifier"));

	return pts_global_gsid_valid($gsid) ? $gsid : false;
}
function pts_global_gsid_valid($gsid)
{
	$gsid_valid = false;

	if(strlen($gsid) == 9)
	{
		if(strlen(pts_remove_chars(substr($gsid, 0, 6), false, false, true, false, false, false)) == 6 &&
		strlen(pts_remove_chars(substr($gsid, 6, 3), true, false, false, false, false, false)) == 3)
		{
			$gsid_valid = true;
		}
	}

	return $gsid_valid;
}
function pts_global_upload_usage_data($task, $data)
{
	switch($task)
	{
		case "test_complete":
			list($test_result, $time_elapsed) = $data;
			$upload_data = array("test_identifier" => $test_result->get_test_profile()->get_identifier(), "test_version" => $test_result->get_test_profile()->get_version(), "elapsed_time" => $time_elapsed);
			pts_http_upload_via_post("http://www.phoronix-test-suite.com/global/usage-stats/test-completion.php", $upload_data);
			break;
	}
}
function pts_global_upload_result($result_file, $tags = "")
{
	if(!pts_global_allow_upload($result_file))
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
	$GlobalUser = pts_current_user();
	$GlobalKey = pts_config::read_user_config(P_OPTION_GLOBAL_UPLOADKEY, null);
	$tags = base64_encode($tags);
	$return_stream = "";

	$upload_data = array("result_xml" => $ToUpload, "global_user" => $GlobalUser, "global_key" => $GlobalKey, "tags" => $tags);

	return pts_http_upload_via_post("http://www.phoronix-test-suite.com/global/user-upload.php", $upload_data);
}
function pts_global_allow_upload($result_file)
{
	$result_file = new pts_result_file($result_file);

	foreach($result_file->get_result_objects() as $result_object)
	{
		$test_profile = new pts_test_profile($result_object->get_test_name());

		if(!$test_profile->allow_global_uploads())
		{
			echo "\n" . $result_object->get_test_name() . " does not allow test results to be uploaded to Phoronix Global.\n\n";
			return false;
		}
	}

	return true;
}

?>
