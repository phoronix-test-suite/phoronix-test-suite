<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2021, Phoronix Media
	Copyright (C) 2010 - 2021, Michael Larabel

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

class pts_openbenchmarking_upload extends pts_openbenchmarking
{
	public static function upload_test_result(&$object, $return_json_data = false, $prompts = true)
	{
		if($object instanceof pts_test_run_manager)
		{
			$result_file = new pts_result_file($object->get_file_name());
			$local_file_name = $object->get_file_name();
			$results_identifier = $object->get_results_identifier();
		}
		else if($object instanceof pts_result_file)
		{
			$result_file = &$object;
			$local_file_name = $result_file->get_identifier();
			$results_identifier = null;
		}

		// Ensure the results can be shared
		if(self::result_upload_supported($result_file) == false)
		{
			return false;
		}

		if(pts_network::internet_support_available() == false)
		{
			echo PHP_EOL . 'No network support available.' . PHP_EOL;
			return false;
		}

		$composite_xml = $result_file->get_xml();
		$system_log_dir = $result_file->get_system_log_dir();
		$upload_system_logs = false;

		if(is_dir($system_log_dir))
		{
			if(pts_config::read_bool_config('PhoronixTestSuite/Options/OpenBenchmarking/AlwaysUploadSystemLogs', 'FALSE'))
			{
				$upload_system_logs = true;
			}
			else if(PTS_IS_CLIENT && isset(pts_openbenchmarking_client::$client_settings['UploadSystemLogsByDefault']))
			{
				$upload_system_logs = pts_openbenchmarking_client::$client_settings['UploadSystemLogsByDefault'];
			}
			else if(is_dir($system_log_dir))
			{
				if($prompts == false)
				{
					$upload_system_logs = true;
				}
				else
				{
					$upload_system_logs = pts_user_io::prompt_bool_input('Would you like to attach the system logs (lspci, dmesg, lsusb, etc) to the test result', -1, 'UPLOAD_SYSTEM_LOGS');
				}
			}
		}

		$system_logs = null;
		$system_logs_hash = null;
		if($upload_system_logs)
		{
			// Ensure only text log files are uploaded to OpenBenchmarking.org
			$is_valid_log = pts_file_io::directory_only_contains_text_files($system_log_dir);

			if($is_valid_log)
			{
				$system_logs_zip = pts_client::create_temporary_file('.zip');
				pts_compression::zip_archive_create($system_logs_zip, $system_log_dir);

				if(filesize($system_logs_zip) < 3097152)
				{
					// Don't upload if too big
					$system_logs = base64_encode(file_get_contents($system_logs_zip));
					$system_logs_hash = sha1($system_logs);
				}
				else
				{
					trigger_error('The systems log attachment is too large to upload to OpenBenchmarking.org.', E_USER_WARNING);
				}

				unlink($system_logs_zip);
			}
		}

		$composite_xml_hash = sha1($composite_xml);
		$composite_xml_type = 'composite_xml';

		// Compress the result file XML if it's big
		if(isset($composite_xml[40000]) && function_exists('bzcompress'))
		{
			$composite_xml_bz = bzcompress($composite_xml, 8);

			if($composite_xml_bz != false)
			{
				$composite_xml = $composite_xml_bz;
				$composite_xml_type = 'composite_xml_bz';
			}
		}
		else if(isset($composite_xml[10000]) && function_exists('gzdeflate'))
		{
			$composite_xml_gz = gzdeflate($composite_xml, 9);

			if($composite_xml_gz != false)
			{
				$composite_xml = $composite_xml_gz;
				$composite_xml_type = 'composite_xml_gz';
			}
		}
		$to_post = array(
			$composite_xml_type => base64_encode($composite_xml),
			'composite_xml_hash' => $composite_xml_hash,
			'local_file_name' => $local_file_name,
			'this_results_identifier' => $results_identifier,
			'system_logs_zip' => $system_logs,
			'system_logs_hash' => $system_logs_hash
			);

		if(PTS_IS_CLIENT && isset(pts_openbenchmarking_client::$client_settings['ResultUploadsDefaultDisplayStatus']) && is_numeric(pts_openbenchmarking_client::$client_settings['ResultUploadsDefaultDisplayStatus']))
		{

			$to_post['display_status'] = pts_openbenchmarking_client::$client_settings['ResultUploadsDefaultDisplayStatus'];
		}

		$result_upload_timeout = 55;
		$json_response = pts_openbenchmarking::make_openbenchmarking_request('upload_test_result', $to_post, $result_upload_timeout);
		$json_response = json_decode($json_response, true);
		if(!is_array($json_response) && !empty($system_logs))
		{
			// Sometimes OpenBenchmarking has issues with large result files, so for now try uploading again with no logs
			$to_post['system_logs_zip'] = null;
			$to_post['system_logs_hash'] = null;
			$json_response = pts_openbenchmarking::make_openbenchmarking_request('upload_test_result', $to_post, $result_upload_timeout);
			$json_response = json_decode($json_response, true);
		}

		if(!is_array($json_response))
		{
			trigger_error('Unhandled Exception', E_USER_ERROR);
			return false;
		}

		if(isset($json_response['openbenchmarking']['upload']['error']))
		{
			trigger_error($json_response['openbenchmarking']['upload']['error'], E_USER_ERROR);
		}
		if(isset($json_response['openbenchmarking']['upload']['url']))
		{
			echo PHP_EOL . '    ' . pts_client::cli_just_bold('Results Uploaded To: ') . $json_response['openbenchmarking']['upload']['url'] . PHP_EOL;
			pts_module_manager::module_process('__event_openbenchmarking_upload', $json_response);
		}
		//$json['openbenchmarking']['upload']['id']

		if(PTS_IS_CLIENT && isset(pts_openbenchmarking_client::$client_settings['RemoveLocalResultsOnUpload']) && pts_openbenchmarking_client::$client_settings['RemoveLocalResultsOnUpload'] && $local_file_name != null)
		{
			pts_results::remove_saved_result_file($local_file_name);
		}

		if($return_json_data)
		{
			return isset($json_response['openbenchmarking']['upload']) ? $json_response['openbenchmarking']['upload'] : false;
		}

		return isset($json_response['openbenchmarking']['upload']['url']) ? $json_response['openbenchmarking']['upload']['url'] : false;
	}
	public static function upload_usage_data($task, $data)
	{
		if(!pts_network::internet_support_available())
		{
			return false;
		}

		switch($task)
		{
			case 'test_install':
				list($test_install, $time_elapsed) = $data;
				$upload_data = array('test_identifier' => $test_install->test_profile->get_identifier(), 'test_version' => $test_install->test_profile->get_test_profile_version(), 'elapsed_time' => $time_elapsed);
				pts_network::http_upload_via_post(pts_openbenchmarking::openbenchmarking_host() . 'extern/statistics/report-test-install.php', $upload_data);
				break;
			case 'test_complete':
				list($test_result, $time_elapsed) = $data;
				$upload_data = array('test_identifier' => $test_result->test_profile->get_identifier(), 'test_version' => $test_result->test_profile->get_test_profile_version(), 'elapsed_time' => $time_elapsed);
				pts_network::http_upload_via_post(pts_openbenchmarking::openbenchmarking_host() . 'extern/statistics/report-test-completion.php', $upload_data);
				break;
			case 'test_install_failure':
				list($test_install, $error) = $data;
				$upload_data = array('test_identifier' => $test_install->test_profile->get_identifier(), 'error' => $error, 'os' => phodevi::read_property('system', 'vendor-identifier'));
				pts_network::http_upload_via_post(pts_openbenchmarking::openbenchmarking_host() . 'extern/statistics/report-test-install-failure.php', $upload_data);
				break;
			case 'download_failure':
				list($test_install, $broken_url) = $data;
				$upload_data = array('test_identifier' => $test_install->test_profile->get_identifier(), 'broken_url' => $broken_url);
				pts_network::http_upload_via_post(pts_openbenchmarking::openbenchmarking_host() . 'extern/statistics/report-download-failure.php', $upload_data);
				break;
		}
	}
	protected static function result_upload_supported(&$result_file)
	{
		foreach($result_file->get_result_objects() as $result_object)
		{
			$test_profile = new pts_test_profile($result_object->test_profile->get_identifier());

			if($test_profile->allow_results_sharing() == false)
			{
				echo PHP_EOL . $result_object->test_profile->get_identifier() . ' does not allow test results to be uploaded.' . PHP_EOL . PHP_EOL;
				return false;
			}
		}

		return true;
	}
}

?>
