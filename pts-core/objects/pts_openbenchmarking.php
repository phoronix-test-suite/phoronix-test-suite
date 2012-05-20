<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2012, Phoronix Media
	Copyright (C) 2010 - 2012, Michael Larabel

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

class pts_openbenchmarking
{
	public static function stats_hardware_list()
	{
		return array(
			'cpu' => array('cpu', 'model'),
			'cpu_count' => array('cpu', 'core-count'),
			'cpu_speed' => array('cpu', 'mhz-default-frequency'),
			'chipset' => array('chipset'),
			'motherboard' => array('motherboard'),
			'gpu' => array('gpu', 'model'),
			'disk' => array('disk', 'identifier'),
			'audio' => array('audio', 'identifier'),
			'monitor' => array('monitor', 'identifier')
			);
	}
	public static function valid_user_name()
	{
		$invalid_users = array('pts', 'phoronix', 'local');
		// TODO: finish function
	}
	public static function stats_software_list()
	{
		return array(
			'os' => array('system', 'operating-system'),
			'os_architecture' => array('system', 'kernel-architecture'),
			'kernel' => array('system', 'kernel'),
			'display_server' => array('system', 'display-server'),
			'display_driver' => array('system', 'display-driver-string'),
			'opengl' => array('system', 'opengl-driver'),
			'desktop' => array('system', 'desktop-environment'),
			'compiler' => array('system', 'compiler'),
			'file_system' => array('system', 'filesystem'),
			'screen_resolution' => array('gpu', 'screen-resolution-string')
			);
	}
	public static function is_valid_gsid_format($gsid)
	{
		$gsid_valid = false;

		if(strlen($gsid) == 9)
		{
			if(ctype_upper(substr($gsid, 0, 6)) && ctype_digit(substr($gsid, 6, 3)))
			{
				$gsid_valid = true;
			}
		}

		return $gsid_valid;
	}
	public static function is_valid_gsid_e_format($gside)
	{
		$gside_valid = false;

		if(strlen($gside) == 12)
		{
			if(ctype_upper(substr($gside, 0, 10)) && ctype_digit(substr($gside, 10, 2)))
			{
				$gside_valid = true;
			}
		}

		return $gside_valid;
	}
	public static function is_valid_gsid_p_format($gsidp)
	{
		$gsidp_valid = false;

		if(strlen($gsidp) == 10)
		{
			if(ctype_upper(substr($gsidp, 0, 9)) &&	ctype_digit(substr($gsidp, 9, 1)))
			{
				$gsidp_valid = true;
			}
		}

		return $gsidp_valid;
	}
	public static function is_openbenchmarking_result_id($id)
	{
		$is_id = false;

		if(self::is_string_openbenchmarking_result_id_compliant($id))
		{
			$json_response = pts_openbenchmarking::make_openbenchmarking_request('is_openbenchmarking_result', array('i' => $id));
			$json_response = json_decode($json_response, true);

			if(is_array($json_response) && isset($json_response['openbenchmarking']['result']['valid']) && $json_response['openbenchmarking']['result']['valid'] == 'TRUE')
			{
				$is_id = true;
			}
		}

		return $is_id;
	}
	public static function clone_openbenchmarking_result(&$id, $return_xml = false)
	{
		$json_response = pts_openbenchmarking::make_openbenchmarking_request('clone_openbenchmarking_result', array('i' => $id));
		$json_response = json_decode($json_response, true);
		$valid = false;

		if(is_array($json_response) && isset($json_response['openbenchmarking']['result']['composite_xml']))
		{
			$composite_xml = $json_response['openbenchmarking']['result']['composite_xml'];

			$result_file = new pts_result_file($composite_xml);

			if($result_file->xml_parser->validate())
			{
				$result_file_writer = new pts_result_file_writer();
				$result_file_writer->add_result_file_meta_data($result_file, $id);
				$result_file_writer->add_system_information_from_result_file($result_file);
				$result_file_writer->add_results_from_result_file($result_file);
				//$id = strtolower($id);

				$valid = $return_xml ? $result_file_writer->get_xml() : pts_client::save_test_result($id . '/composite.xml', $result_file_writer->get_xml(), true);

				if(PTS_IS_CLIENT && $json_response['openbenchmarking']['result']['system_logs_available'])
				{
					// Fetch the system logs and toss them into the results directory system-logs/
					pts_openbenchmarking::clone_openbenchmarking_result_system_logs($id, pts_client::setup_test_result_directory($id), $json_response['openbenchmarking']['result']['system_logs_available']);
				}
			}
			else
			{
				trigger_error('Validating the result file schema failed.', E_USER_ERROR);
			}
		}
		else if(PTS_IS_CLIENT && isset($json_response['openbenchmarking']['result']['error']))
		{
			trigger_error($json_response['openbenchmarking']['result']['error'], E_USER_ERROR);
		}

		return $valid;
	}
	public static function clone_openbenchmarking_result_system_logs(&$id, $extract_to, $sha1_compare = null)
	{
		$system_log_response = pts_openbenchmarking::make_openbenchmarking_request('clone_openbenchmarking_system_logs', array('i' => $id));
		$extracted = false;

		if($system_log_response != null)
		{
			$zip_temp = pts_client::create_temporary_file();
			file_put_contents($zip_temp, $system_log_response);

			if($sha1_compare == null || sha1_file($zip_temp) == $sha1_compare)
			{
				// hash check of file passed or was null
				$extracted = pts_compression::zip_archive_extract($zip_temp, $extract_to);
			}

			unlink($zip_temp);
		}

		return $extracted;
	}
	public static function is_string_openbenchmarking_result_id_compliant($id)
	{
		$valid = false;

		if(strlen($id) == 22)
		{
			$segments = explode('-', $id);

			if(count($segments) == 3)
			{
				if(strlen($segments[0]) == 7 && is_numeric($segments[0]))
				{
					$us = strlen($segments[1]);

					if($us > 1 && $us < 9 && ctype_alnum($segments[1]))
					{
						if(ctype_alnum($segments[2]))
						{
							$valid = true;
						}
					}
				}
			}
		}

		return $valid;
	}
	public static function is_abstract_id($id)
	{
		$valid = false;

		if(strlen($id) == 4)
		{
			if(ctype_alpha($id))
			{
				$valid = true;
			}
		}

		return $valid;
	}
	public static function openbenchmarking_host()
	{
		static $host = null;

		if($host == null)
		{
			// Use HTTPS if OpenSSL is available as a check to see if HTTPS can be handled
			// OpenSSL seems to have problems on OpenIndiana at least, TODO: investigate
			$host = ((extension_loaded('openssl') && getenv('NO_OPENSSL') == false && php_uname('s') == 'Linux') ? 'https://' : 'http://') . 'openbenchmarking.org/';
		}

		return $host;
	}
	public static function make_openbenchmarking_request($request, $post = array())
	{
		return pts_openbenchmarking_client::make_openbenchmarking_request($request, $post);
	}
	public static function read_repository_index($repo_name)
	{
		$index_file = PTS_OPENBENCHMARKING_SCRATCH_PATH . $repo_name . '.index';

		if(is_file($index_file))
		{
			$index_file = file_get_contents($index_file);
			$index_file = json_decode($index_file, true);
		}

		return $index_file;
	}
	public static function evaluate_string_to_qualifier($supplied, $bind_version = true, $check_only_type = false)
	{
		return pts_openbenchmarking_client::evaluate_string_to_qualifier($supplied, $bind_version, $check_only_type);
	}
	public static function upload_test_result(&$object)
	{
		return pts_openbenchmarking_client::upload_test_result($object);
	}
	public static function refresh_repository_lists($repos = null)
	{
		return pts_openbenchmarking_client::refresh_repository_lists($repos);
	}
}

?>
