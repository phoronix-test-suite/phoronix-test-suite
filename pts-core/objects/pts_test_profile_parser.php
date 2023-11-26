<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2021, Phoronix Media
	Copyright (C) 2008 - 2021, Michael Larabel

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

class pts_test_profile_parser
{
	protected $identifier;
	private $xml;
	private $raw_xml;
	protected $overrides;
	private $tp_extends;
	protected $block_test_extension_support = false;
	private $file_location = false;
	public $no_fallbacks_on_null = false;
	protected static $xml_file_cache;

	public function __construct($read = null, $normal_init = true)
	{
		$original_read = $read;
		$this->overrides = array();
		$this->tp_extends = null;

		if($normal_init == false || $read == null)
		{
			$this->identifier = $read;
			return;
		}
		if(isset(self::$xml_file_cache[$read]))
		{
			// Found in cache so can avoid extra work below...
			$this->identifier = $read;
			$this->file_location = $read;
			$this->xml = &self::$xml_file_cache[$this->file_location];
		}

		if(!isset($read[200]) && strpos($read, '<?xml version="1.0"?>') === false)
		{
			if(PTS_IS_CLIENT && (!defined('PTS_TEST_PROFILE_PATH') || !is_file(PTS_TEST_PROFILE_PATH . $read . '/test-definition.xml')))
			{
				$read = pts_openbenchmarking::evaluate_string_to_qualifier($read, true, 'test');

				if($read == false && pts_openbenchmarking::openbenchmarking_has_refreshed() == false)
				{
					// Test profile might be brand new, so refresh repository and then check
					// pts_openbenchmarking::refresh_repository_lists(null, true);
					$read = pts_openbenchmarking::evaluate_string_to_qualifier($read, true, 'test');
				}
			}
		}

		if(!isset($read[64]))
		{
			// Passed is not an identifier since it's too long
			$this->identifier = $read;
		}

		if(!isset($read[512]) && !is_file($read))
		{
			if(defined('PTS_TEST_PROFILE_PATH') && is_file(PTS_TEST_PROFILE_PATH . $read . '/test-definition.xml'))
			{
				$read = PTS_TEST_PROFILE_PATH . $read . '/test-definition.xml';
			}
		}
		else if(substr($read, -4) == '.zip' && is_file($read))
		{
			$zip = new ZipArchive();

			if($zip->open($read) === true)
			{
				$read = $zip->getFromName('test-definition.xml');
				$zip->close();
			}
		}

		//$xml_options = 0;
		//if(defined('LIBXML_COMPACT'))
		//{
			$xml_options = LIBXML_COMPACT | LIBXML_PARSEHUGE;
		//}

		if(isset(self::$xml_file_cache[$read]))
		{
			$this->file_location = $read;
			$this->xml = &self::$xml_file_cache[$this->file_location];
		}
		else if($read && is_file($read))
		{
			$this->file_location = $read;
			self::$xml_file_cache[$this->file_location] = simplexml_load_file($read, 'SimpleXMLElement', $xml_options);
			if($read != $original_read && !isset(self::$xml_file_cache[$original_read]))
			{
				self::$xml_file_cache[$original_read] = &self::$xml_file_cache[$this->file_location];
			}
			$this->xml = &self::$xml_file_cache[$this->file_location];
		}
		else
		{
			$this->raw_xml = $read;
			if(strpos($read, '<') !== false)
			{
				$this->xml = simplexml_load_string($read, 'SimpleXMLElement', $xml_options);
			}
		}
	}
	public function get_xml()
	{
		if($this->file_location)
		{
			return file_get_contents($this->file_location);
		}

		return $this->raw_xml;
	}
	public function get_file_location()
	{
		return $this->file_location;
	}
	public function __toString()
	{
		return $this->get_identifier();
	}
	public function block_test_extension_support()
	{
		$this->block_test_extension_support = true;
	}
	public function xs($xpath, &$value)
	{
		$this->overrides[$xpath] = $value;
	}
	public function get_dependency_names()
	{
		$dependency_names = array();
		$exdep_generic_parser = new pts_exdep_generic_parser();

		foreach($this->get_external_dependencies() as $dependency)
		{
			if($exdep_generic_parser->is_package($dependency))
			{
				$package_data = $exdep_generic_parser->get_package_data($dependency);
				$dependency_names[] = $package_data['title'];
			}
		}

		return $dependency_names;
	}
	public function xg($xpath, $default_on_null = null)
	{
		if(isset($this->overrides[$xpath]))
		{
			return $this->overrides[$xpath];
		}

		$r = $this->xml ? $this->xml->xpath($xpath) : null;

		if(empty($r))
		{
			$r = null;
		}
		else if(isset($r[0]))
		{
			if(!isset($r[1]))
			{
				// Single
				$r = $r[0]->__toString();
			}
		}

		if($r == null && $this->block_test_extension_support == false)
		{
			if($this->tp_extends === null)
			{
				$this->tp_extends = false;
				$tp_identifier = $this->get_test_extension();
				if($tp_identifier != null && PTS_IS_CLIENT)
				{
					$this->tp_extends = new pts_test_profile_parser($tp_identifier);
				}
				else
				{
					$this->block_test_extension_support = true;
				}
			}
			if($this->tp_extends)
			{
				$r = $this->tp_extends->xg($xpath);
			}
		}

		if($r == null && (!$this->no_fallbacks_on_null || $default_on_null == 'TRUE'))
		{
			$r = $default_on_null;
		}

		return $r;
	}
	public function get_identifier($bind_version = true)
	{
		$identifier = $this->identifier;

		if($bind_version == false && $identifier != null && ($c = strrpos($identifier, '-')))
		{
			if(pts_strings::is_version(substr($identifier, ($c + 1))))
			{
				$identifier = substr($identifier, 0, $c);
			}
		}

		return "$identifier";
	}
	public function get_maintainer()
	{
		return $this->xg('TestProfile/Maintainer');
	}
	public function get_test_hardware_type()
	{
		return $this->xg('TestProfile/TestType');
	}
	public function get_test_software_type()
	{
		return $this->xg('TestProfile/SoftwareType');
	}
	public function get_status()
	{
		return $this->xg('TestProfile/Status');
	}
	public function get_license()
	{
		return $this->xg('TestProfile/License');
	}
	public function get_test_profile_version()
	{
		return $this->xg('TestProfile/Version');
	}
	public function get_app_version()
	{
		if(isset($_GET['merge_mismatched_test_versions']) && $_GET['merge_mismatched_test_versions'] == 'i-understand-the-risks')
		{
			return '';
		}
		return $this->xg('TestInformation/AppVersion');
	}
	public function get_project_url()
	{
		return $this->xg('TestProfile/ProjectURL');
	}
	public function get_repo_url()
	{
		return $this->xg('TestProfile/RepositoryURL');
	}
	public function get_description()
	{
		return $this->xg('TestInformation/Description');
	}
	public function get_title()
	{
		return $this->xg('TestInformation/Title');
	}
	public function get_external_dependencies()
	{
		return pts_strings::comma_explode($this->xg('TestProfile/ExternalDependencies'));
	}
	public function get_system_dependencies()
	{
		return pts_strings::comma_explode($this->xg('TestProfile/SystemDependencies'));
	}
	public function get_pre_install_message()
	{
		return $this->xg('TestInformation/PreInstallMessage');
	}
	public function get_post_install_message()
	{
		return $this->xg('TestInformation/PostInstallMessage');
	}
	public function get_installation_agreement_message()
	{
		return $this->xg('TestInformation/InstallationAgreement');
	}
	public function get_internal_tags_raw()
	{
		return $this->xg('TestProfile/InternalTags');
	}
	public function get_internal_tags()
	{
		return pts_strings::comma_explode($this->get_internal_tags_raw());
	}
	public function get_default_arguments()
	{
		return $this->xg('TestSettings/Default/Arguments');
	}
	public function get_default_post_arguments()
	{
		return $this->xg('TestSettings/Default/PostArguments');
	}
	public function get_identifier_simplified()
	{
		return pts_strings::simplify_string_for_file_handling($this->identifier);
	}
	public function get_identifier_base_name()
	{
		$identifier = basename($this->identifier);

		if(($s = strrpos($identifier, '-')) !== false)
		{
			$post_dash = substr($identifier, ($s + 1));

			// If the version is attached, remove it
			if(pts_strings::is_version($post_dash))
			{
				$identifier = substr($identifier, 0, $s);
			}
		}

		return $identifier;
	}
	public function get_test_executable()
	{
		return $this->xg('TestInformation/Executable', $this->get_identifier_base_name());
	}
	public function get_times_to_run()
	{
		return $this->get_default_times_to_run();
	}
	public function get_default_times_to_run()
	{
		return $this->xg('TestInformation/TimesToRun', 3);
	}
	public function get_runs_to_ignore()
	{
		return pts_strings::comma_explode($this->xg('TestInformation/IgnoreRuns'));
	}
	public function get_pre_run_message()
	{
		return $this->xg('TestInformation/PreRunMessage');
	}
	public function get_post_run_message()
	{
		return $this->xg('TestInformation/PostRunMessage');
	}
	public function get_result_scale()
	{
		return $this->xg('TestInformation/ResultScale');
	}
	public function get_result_scale_formatted()
	{
		$fmt = pts_strings::first_in_string($this->get_result_scale(), '|');
		return empty($fmt) ? '' : trim($fmt);
	}
	public function get_result_scale_shortened()
	{
		$scale = $this->get_result_scale();
		$shorten = array(
			'Frames Per Second' => 'FPS',
			' Per Second' => '/sec',
			' Per Minute' => '/min',
			'Nanoseconds/Operation' => 'ns/op',
			'Nodes/second' => 'Nodes/s',
			'Nodes/sec' => 'Nodes/s',
			'Mbits/sec' => 'Mbits/s',
			'MiB/second' => 'MiB/s',
			'Milli-Seconds' => 'ms',
			'Microseconds' => 'us',
			'Request' => 'Req',
			'Seconds' => 'sec',
			' Per ' => '/',
			'Total ' => '',
			'Average ' => '',
			);
		foreach($shorten as $orig => $new)
		{
			$scale = str_replace($orig, $new, $scale);
		}

		return $scale;
	}
	public function get_result_proportion()
	{
		return $this->xg('TestInformation/Proportion');
	}
	public function get_display_format()
	{
		return $this->xg('TestInformation/DisplayFormat', 'BAR_GRAPH');
	}
	public function do_auto_save_results()
	{
		return pts_strings::string_bool($this->xg('TestProfile/AutoSaveResults', 'FALSE'));
	}
	public function do_remove_test_install_directory_on_reinstall()
	{
		return pts_strings::string_bool($this->xg('TestProfile/RemoveInstallDirectoryOnReinstall', 'TRUE'));
	}
	public function get_result_quantifier()
	{
		return $this->xg('TestInformation/ResultQuantifier');
	}
	public function is_root_required()
	{
		return pts_strings::string_bool($this->xg('TestProfile/RequiresRoot', 'FALSE'));
	}
	public function is_root_install_required()
	{
		return pts_strings::string_bool($this->xg('TestProfile/RequiresRootInstall', 'FALSE'));
	}
	public function is_display_required()
	{
		return pts_strings::string_bool($this->xg('TestProfile/RequiresDisplay', 'FALSE')) || ($this->xg('TestProfile/RequiresDisplay') == null && $this->get_test_hardware_type() == 'Graphics' && !$this->is_gpu_compute_test());
	}
	protected function is_gpu_compute_test()
	{
		$internal_tags = $this->get_internal_tags();
		$external_dependencies = $this->get_external_dependencies();

		return in_array('OpenCL', $internal_tags) || in_array('CUDA', $internal_tags) || in_array('opencl', $external_dependencies);
	}
	public function is_network_required()
	{
		return pts_strings::string_bool($this->xg('TestProfile/RequiresNetwork', 'FALSE')) || $this->get_test_hardware_type() == 'Network';
	}
	public function is_internet_required()
	{
		return pts_strings::string_bool($this->xg('TestProfile/RequiresInternet', 'FALSE'));
	}
	public function is_internet_required_for_install()
	{
		return pts_strings::string_bool($this->xg('TestProfile/InstallRequiresInternet', 'FALSE'));
	}
	public function allow_cache_share()
	{
		return pts_strings::string_bool($this->xg('TestSettings/Default/AllowCacheShare'));
	}
	public function allow_results_sharing()
	{
		return pts_strings::string_bool($this->xg('TestProfile/AllowResultsSharing', 'TRUE'));
	}
	public function get_min_length()
	{
		return $this->xg('TestSettings/Default/MinimumLength');
	}
	public function get_max_length()
	{
		return $this->xg('TestSettings/Default/MaximumLength');
	}
	public function get_test_subtitle()
	{
		return $this->xg('TestInformation/SubTitle');
	}
	public function get_supported_platforms_raw()
	{
		return $this->xg('TestProfile/SupportedPlatforms');
	}
	public function get_supported_platforms()
	{
		return pts_strings::comma_explode($this->get_supported_platforms_raw());
	}
	public function get_supported_architectures()
	{
		return pts_strings::comma_explode($this->xg('TestProfile/SupportedArchitectures'));
	}
	public function get_environment_size()
	{
		return $this->xg('TestProfile/EnvironmentSize', 0);
	}
	public function get_test_extension()
	{
		return $this->xg('TestProfile/Extends');
	}
	public function get_environment_testing_size()
	{
		return $this->xg('TestProfile/EnvironmentTestingSize', 0);
	}
	public function get_estimated_run_time()
	{
		return $this->xg('TestProfile/EstimatedTimePerRun', 0) * $this->get_default_times_to_run();
	}
	public function requires_core_version_min()
	{
		return $this->xg('TestProfile/RequiresCoreVersionMin', 2950);
	}
	public function requires_core_version_max()
	{
		return $this->xg('TestProfile/RequiresCoreVersionMax', 19990);
	}
	public function get_test_option_objects_array()
	{
		return $this->get_test_option_objects(false);
	}
	public function has_test_options()
	{
		return $this->xml && $this->xml->TestSettings && $this->xml->TestSettings->Option;
	}
	public function get_test_option_objects($auto_process = true, &$error = null, $validate_options_now = true)
	{
		$test_options = array();

		if($this->xml && $this->xml->TestSettings && $this->xml->TestSettings->Option)
		{
			foreach($this->xml->TestSettings->Option as $option)
			{
				$names = array();
				$messages = array();
				$values = array();

				if(isset($option->Menu->Entry))
				{
					foreach($option->Menu->Entry as $entry)
					{
						$names[] = $entry->Name->__toString();
						$messages[] = $entry->Message->__toString();
						$values[] = $entry->Value->__toString();
					}
				}

				if($auto_process)
				{
					$auto_process_error_msg = null;
					$auto_process_error = pts_test_run_options::auto_process_test_option($this, $option->Identifier, $names, $values, $messages, $auto_process_error_msg, $validate_options_now);
					if($auto_process_error == -1)
					{
						$error = $auto_process_error_msg;
					}
				}

				$user_option = new pts_test_option($option->Identifier->__toString(), $option->DisplayName->__toString(), $option->Message->__toString());
				$user_option->set_option_prefix($option->ArgumentPrefix->__toString());
				$user_option->set_option_postfix($option->ArgumentPostfix->__toString());

				for($i = 0; $i < count($names); $i++)
				{
					$user_option->add_option($names[$i], (isset($values[$i]) ? $values[$i] : null), (isset($messages[$i]) ? $messages[$i] : null));
				}

				$user_option->set_option_default($option->DefaultEntry->__toString());
				$test_options[] = $user_option;
			}
		}

		return $test_options;
	}
	public function get_reference_id()
	{
		// This isn't needed for test profiles, but keep this here for compatibility when passing a test_profile to pts result file writer
		return null;
	}

	//
	// Set Functions
	//

	public function set_times_to_run($times)
	{
		$this->xs('TestInformation/TimesToRun', $times);
	}
	public function set_result_scale($scale)
	{
		$this->xs('TestInformation/ResultScale', $scale);
	}
	public function set_result_proportion($proportion)
	{
		$this->xs('TestInformation/Proportion', $proportion);
	}
	public function set_display_format($format)
	{
		$this->xs('TestInformation/DisplayFormat', $format);
	}
	public function set_result_quantifier($quantifier)
	{
		$this->xs('TestInformation/ResultQuantifier', $quantifier);
	}
	public function set_version($version)
	{
		$this->xs('TestInformation/AppVersion', $version);
	}
	public function set_test_title($title)
	{
		$this->xs('TestInformation/Title', $title);
	}
	public function set_identifier($identifier)
	{
		$this->identifier = $identifier;
	}
}

?>
