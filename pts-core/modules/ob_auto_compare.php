<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2017 - 2018, Phoronix Media
	Copyright (C) 2017 - 2018, Michael Larabel

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

class ob_auto_compare extends pts_module_interface
{
	const module_name = 'OpenBenchmarking.org Auto Comparison';
	const module_version = '1.2.0';
	const module_description = 'This module prints comparable OpenBenchmarking.org results in the command-line for reference purposes as tests are being run. OpenBenchmarking.org is automatically queried for results to show based on the test comparison hash and the system type (mobile, desktop, server, cloud, workstation, etc). No other system information or result data is transmitted..';
	const module_author = 'Michael Larabel';

	private static $response_time = 0;
	protected static $current_result_file = null;

	public static function user_commands()
	{
		return array('debug' => 'debug_result_file');
	}
	public static function debug_result_file($r)
	{
		if(empty($r) || !pts_types::is_result_file($r[0]))
		{
			echo 'No result file supplied.';
			return;
		}
		$result_file = new pts_result_file($r[0]);
		self::$current_result_file = $r[0];
		foreach($result_file->get_result_objects() as $result_object)
		{
			echo trim($result_object->test_profile->get_title() . ' ' . $result_object->test_profile->get_app_version() . PHP_EOL . $result_object->get_arguments_description()) . PHP_EOL;
			echo 'COMPARISON HASH:' .  $result_object->get_comparison_hash(true, false) . PHP_EOL;
			echo 'SYSTEM TYPE: ' . phodevi_base::determine_system_type(phodevi::system_hardware(), phodevi::system_software()) . PHP_EOL;
			$auto_comparison_result_file = self::request_compare($result_object, phodevi_base::determine_system_type(phodevi::system_hardware(), phodevi::system_software()));

			if($auto_comparison_result_file instanceof pts_result_file)
			{
				$merge_ch = $auto_comparison_result_file->add_result($result_object);
				$ro =  $auto_comparison_result_file->get_result($merge_ch);
				$ro->sort_results_by_performance();
				$ro->test_result_buffer->buffer_values_reverse();
				echo pts_result_file_output::test_result_to_text($ro, 80, true, $result_file->get_system_identifiers());
				echo PHP_EOL . '     REFERENCE: ' . $auto_comparison_result_file->get_reference_id() . PHP_EOL;
			}
			else
			{
				echo 'NO MATCHES';
			}

			echo PHP_EOL . PHP_EOL;
		}
	}
	protected static function request_compare(&$result_object, $system_type)
	{
		$result_file = null;
		if(true) // default to see if local comparison first
		{
			$comparison_hash = $result_object->get_comparison_hash(true, false);
			$result_file = self::request_compare_from_local_results($comparison_hash);
		}

		if(empty($result_file) && pts_network::internet_support_available())
		{
			$comparison_hash = $result_object->get_comparison_hash();
			$result_file = self::request_compare_from_ob($comparison_hash, $system_type);
		}

		return $result_file;
	}
	protected static function request_compare_from_local_results($comparison_hash)
	{
		$saved_results = pts_client::saved_test_results();
		shuffle($saved_results);

		foreach($saved_results as $tr)
		{
			$result_file = new pts_result_file($tr);

			if(self::$current_result_file != null && self::$current_result_file == $result_file->get_identifier())
			{
				continue;
			}

			if($result_file->get_result($comparison_hash) != false)
			{
				$result_file->set_reference_id($result_file->get_identifier());
				return $result_file;
			}
		}

		return null;
	}
	protected static function request_compare_from_ob($comparison_hash, $system_type)
	{
		if(!pts_network::internet_support_available() || self::$response_time > 8)
		{
			// If no network or OB requests are being slow...
			return false;
		}

		$ob_request_time = time();
		$json_response = pts_openbenchmarking::make_openbenchmarking_request('auto_compare_via_hash', array('comparison_hash' => $comparison_hash, 'system_type' => $system_type));
		self::$response_time = time() - $ob_request_time;


		$json_response = json_decode($json_response, true);

		if(is_array($json_response) && isset($json_response['openbenchmarking']['result']['composite_xml']))
		{
			$composite_xml = $json_response['openbenchmarking']['result']['composite_xml'];
			if(!empty($composite_xml))
			{
				$result_file = new pts_result_file($composite_xml);
				$result_file->set_reference_id($json_response['openbenchmarking']['result']['public_id']);
				return $result_file;
			}
		}

		return null;
	}
	public static function __pre_run_process($test_run_manager)
	{
		if(!$test_run_manager->is_interactive_mode())
		{
			return pts_module::MODULE_UNLOAD;
		}
		self::$current_result_file = $test_run_manager->get_file_name();
	}
	public static function __test_run_success_inline_result($result_object)
	{
		// Passed is a copy of the successful pts_test_result after showing other inline metrics
		if($result_object->test_result_buffer->get_count() < 3)
		{
			$auto_comparison_result_file = self::request_compare($result_object, phodevi_base::determine_system_type(phodevi::system_hardware(), phodevi::system_software()));

			if($auto_comparison_result_file instanceof pts_result_file)
			{
				$merge_ch = $auto_comparison_result_file->add_result($result_object);
				$ro =  $auto_comparison_result_file->get_result($merge_ch);
				$ro->sort_results_by_performance();
				$ro->test_result_buffer->buffer_values_reverse();
				echo PHP_EOL.pts_client::cli_just_bold('    OpenBenchmarking.org Dynamic Comparison: ');
				echo pts_result_file_output::test_result_to_text($ro, pts_client::terminal_width(), true, $result_object->test_result_buffer->get_identifiers());
				echo PHP_EOL . pts_client::cli_just_bold('    Result Perspective:') . ' ' . (pts_openbenchmarking::is_string_openbenchmarking_result_id_compliant($auto_comparison_result_file->get_reference_id()) ? 'https://openbenchmarking.org/result/' : ) . $auto_comparison_result_file->get_reference_id() . PHP_EOL;
			}
		}
	}

}
?>
