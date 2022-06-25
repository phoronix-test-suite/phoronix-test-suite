<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2022, Phoronix Media
	Copyright (C) 2008 - 2022, Michael Larabel

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

class pts_test_profile extends pts_test_profile_parser
{
	public $test_installation = false;
	protected $overview = false;
	protected static $test_installation_cache;

	public function __construct($identifier = null, $override_values = null, $normal_init = true)
	{
		parent::__construct($identifier, $normal_init);

		if($override_values != null && is_array($override_values))
		{
			$this->set_override_values($override_values);
		}

		if($normal_init && PTS_IS_CLIENT && $this->identifier != null)
		{
			if(!isset(self::$test_installation_cache[$identifier]))
			{
				self::$test_installation_cache[$identifier] = new pts_installed_test($this);
			}

			$this->test_installation = &self::$test_installation_cache[$identifier];
		}
	}
	public function validate()
	{
		$dom = new DOMDocument();
		$dom->loadXML($this->get_xml());
		return $dom->schemaValidate(pts_openbenchmarking::openbenchmarking_standards_path() . 'schemas/test-profile.xsd');
	}
	public static function is_test_profile($identifier)
	{
		if(!is_file(PTS_TEST_PROFILE_PATH . $identifier . '/test-definition.xml'))
		{
			$identifier = pts_openbenchmarking::evaluate_string_to_qualifier($identifier, true, 'test');
		}
		return $identifier != false && is_file(PTS_TEST_PROFILE_PATH . $identifier . '/test-definition.xml') ? $identifier : false;
	}
	public function get_resource_dir()
	{
		return PTS_TEST_PROFILE_PATH . $this->identifier . '/';
	}
	public function get_changelog()
	{
		$change_log = array();
		if(is_file($this->get_resource_dir() . 'changelog.json'))
		{
			// Archived locally
			$json_file = pts_file_io::file_get_contents($this->get_resource_dir() . 'changelog.json');
			$change_log = json_decode($json_file, true);
		}
		else if(PTS_IS_CLIENT && stripos($this->get_identifier(), 'local/') === false)
		{
			// Query from OB
			$changelog_query = pts_openbenchmarking_client::fetch_repository_test_profile_changelog($this->get_identifier(false));

			if(is_array($changelog_query) && isset($changelog_query['tests'][$this->get_identifier_base_name()]['changes']))
			{
				$change_log = $changelog_query['tests'][$this->get_identifier_base_name()]['changes'];
			}
		}

		return $change_log;
	}
	public function get_generated_data($ch = false)
	{
		if($this->overview === false)
		{
			// Cache the parsed JSON if available
			$this->overview = array();
			if(is_file($this->get_resource_dir() . 'generated.json'))
			{
				$this->overview = json_decode(pts_file_io::file_get_contents($this->get_resource_dir() . 'generated.json'), true);
			}
		}

		if($ch != false)
		{
			return isset($this->overview['overview'][$ch]) ? $this->overview['overview'][$ch] : false;
		}

		return $this->overview;
	}
	public function get_override_values($as_string = false)
	{
		if($as_string)
		{
			$o = $this->overrides;
			foreach($o as $x => &$y)
			{
				$y = $x . '=' . $y;
			}

			return implode(';', $o);

		}
		else
		{
			return $this->overrides;
		}
	}
	public function set_override_values($override_values)
	{
		if(is_array($override_values))
		{
			foreach($override_values as $xml_tag => $value)
			{
				$this->xs($xml_tag, $value);
			}
		}
	}
	public function get_download_size($include_extensions = true, $divider = 1048576)
	{
		$estimated_size = 0;

		if(PTS_IS_CLIENT)
		{
			$downloads = pts_test_install_request::read_download_object_list($this->identifier);
		}
		else
		{
			$downloads = $this->get_downloads();
		}

		foreach($downloads as $download_object)
		{
			$estimated_size += $download_object->get_filesize();
		}

		if($include_extensions)
		{
			$extends = $this->get_test_extension();

			if(!empty($extends))
			{
				$test_profile = new pts_test_profile($extends);
				$estimated_size += $test_profile->get_download_size(true, 1);
			}
		}

		$estimated_size = $estimated_size > 0 && $divider > 1 ? round($estimated_size / $divider, 2) : 0;

		return $estimated_size;
	}
	public function get_environment_size($include_extensions = true)
	{
		$estimated_size = parent::get_environment_size();

		if($include_extensions)
		{
			$extends = $this->get_test_extension();

			if(!empty($extends))
			{
				$test_profile = new pts_test_profile($extends);
				$estimated_size += $test_profile->get_environment_size(true);
			}
		}

		return $estimated_size;
	}
	public function get_test_extensions_recursive()
	{
		// Process Extensions / Cascading Test Profiles
		$extensions = array();
		$extended_test = $this->get_test_extension();

		if(!empty($extended_test))
		{
			do
			{
				if(!in_array($extended_test, $extensions))
				{
					$extensions[] = $extended_test;
				}

				$extended_test = new pts_test_profile_parser($extended_test);
				$extended_test = $extended_test->get_test_extension();
			}
			while(!empty($extended_test));
		}

		return $extensions;
	}
	public function get_times_to_run()
	{
		$times_to_run = parent::get_times_to_run();

		if(!PTS_IS_CLIENT)
		{
			return $times_to_run;
		}

		if(($force_runs_multiple = pts_env::read('FORCE_TIMES_TO_RUN_MULTIPLE')) && is_numeric($force_runs_multiple) && $force_runs_multiple > 1 && $this->get_estimated_run_time() < (60 * 60 * 2))
		{
			$times_to_run *= $force_runs_multiple;
		}

		if(($force_runs = pts_env::read('FORCE_TIMES_TO_RUN')) && is_numeric($force_runs) && $force_runs > 0)
		{
			$times_to_run = $force_runs;
		}

		if(($force_min_cutoff = pts_env::read('FORCE_MIN_TIMES_TO_RUN_CUTOFF')) == false || ($this->get_estimated_run_time() > 0 && ($this->get_estimated_run_time() / 60) < $force_min_cutoff))
		{
			if(($force_runs = pts_env::read('FORCE_MIN_TIMES_TO_RUN')) && is_numeric($force_runs) && $force_runs > $times_to_run)
			{
				$times_to_run = $force_runs;
			}
		}

		if(($force_runs = pts_env::read('FORCE_ABSOLUTE_MIN_TIMES_TO_RUN')) && is_numeric($force_runs) && $force_runs > $times_to_run)
		{
			$times_to_run = $force_runs;
		}

		$display_format = $this->get_display_format();
		if($times_to_run < 1 || ($display_format != null && strlen($display_format) > 6 && (substr($display_format, 0, 6) == 'MULTI_' || substr($display_format, 0, 6) == 'IMAGE_')))
		{
			// Currently tests that output multiple results in one run can only be run once
			$times_to_run = 1;
		}

		return $times_to_run;
	}
	public function get_estimated_run_time()
	{
		// get estimated run-time (in seconds)
		if($this->no_fallbacks_on_null)
		{
			return parent::get_estimated_run_time();
		}

		if($this->test_installation != false && is_numeric($this->test_installation->get_average_run_time()) && $this->test_installation->get_average_run_time() > 0)
		{
			$estimated_run_time = $this->test_installation->get_average_run_time();
		}
		else
		{
			$estimated_run_time = parent::get_estimated_run_time();
		}

		if($estimated_run_time < 2 && PTS_IS_CLIENT)
		{
			$identifier = explode('/', $this->get_identifier(false));
			$repo_index = pts_openbenchmarking::read_repository_index($identifier[0]);
			$estimated_run_time = isset($identifier[1]) && isset($repo_index['tests'][$identifier[1]]) && isset($repo_index['tests'][$identifier[1]]['average_run_time']) ? $repo_index['tests'][$identifier[1]]['average_run_time'] : 0;
		}

		return $estimated_run_time;
	}
	public function get_estimated_install_time()
	{
		// get estimated install-time (in seconds)
		$est_install_time = 0;
		if($this->test_installation != false && is_numeric($this->test_installation->get_latest_install_time()) && $this->test_installation->get_latest_install_time() > 0)
		{
			$est_install_time = $this->test_installation->get_latest_install_time();
		}

		if($est_install_time == 0 && PTS_IS_CLIENT)
		{
			$identifier = explode('/', $this->get_identifier(false));
			$repo_index = pts_openbenchmarking::read_repository_index($identifier[0]);
			$est_install_time = isset($identifier[1]) && isset($repo_index['tests'][$identifier[1]]) && isset($repo_index['tests'][$identifier[1]]['average_install_time']) ? $repo_index['tests'][$identifier[1]]['average_install_time'] : 1;
		}

		return ceil($est_install_time);
	}
	public function is_supported($print_warnings = true, &$error = null)
	{
		$test_supported = true;

		if(PTS_IS_CLIENT && pts_env::read('SKIP_TEST_SUPPORT_CHECKS'))
		{
			// set SKIP_TEST_SUPPORT_CHECKS=1 environment variable for debugging purposes to run tests on unsupported platforms
			return true;
		}
		else if($this->is_test_architecture_supported() == false)
		{
			$error = $this->get_identifier() . ' is not supported on this architecture: ' . phodevi::read_property('system', 'kernel-architecture');
			$test_supported = false;
		}
		else if($this->is_test_platform_supported() == false)
		{
			$error = $this->get_identifier() . ' is not supported by this operating system: ' . phodevi::os_under_test();
			$test_supported = false;
		}
		else if($this->is_core_version_supported() == false)
		{
			$error = $this->get_identifier() . ' is not supported by this version of the Phoronix Test Suite: ' . PTS_VERSION;
			$test_supported = false;
		}
		else if(PTS_IS_CLIENT && ($custom_support_check = $this->custom_test_support_check()) !== true)
		{
			// A custom-self-generated error occurred, see code comments in custom_test_support_check()
			$error = $this->get_identifier() . ': ' . $custom_support_check;
			$test_supported = false;
		}
		else if(PTS_IS_CLIENT)
		{
			foreach($this->extended_test_profiles() as $extension)
			{
				if($extension->is_supported($print_warnings, $error) == false)
				{
					$test_supported = false;
					break;
				}
			}
		}

		if($print_warnings && !empty($error) && PTS_IS_CLIENT)
		{
			pts_client::$display->test_run_error($error);
		}

		return $test_supported;
	}
	public function custom_test_support_check()
	{
		/*
		As of Phoronix Test Suite 4.4, the software will check for the presence of a 'support-check' file.
		Any test profile can optionally include a support-check.sh file to check for arbitrary commands not covered by
		the rest of the PTS testing architecture, e.g. to check for the presence of systemd on the target system. If
		the script finds that the system is incompatible with the test, it can write a custom error message to the file
		specified by the $TEST_CUSTOM_ERROR environment variable. If the $TEST_CUSTOM_ERROR target is written to, the PTS
		client will abort the test installation with the specified error message.
		*/

		$support_check_file = $this->get_resource_dir() . 'support-check.sh';

		if(PTS_IS_CLIENT && is_file($support_check_file))
		{
			$environment['TEST_CUSTOM_ERROR'] = pts_client::temporary_directory() . '/PTS-' . $this->get_identifier_base_name() . '-' . rand(1000, 9999);
			$support_check = pts_tests::call_test_script($this, 'support-check', null, null, $environment, false);

			if(is_file($environment['TEST_CUSTOM_ERROR']))
			{
				$support_result = pts_file_io::file_get_contents($environment['TEST_CUSTOM_ERROR']);
				pts_file_io::delete($environment['TEST_CUSTOM_ERROR']);
				return $support_result;
			}
		}

		return true;
	}
	public function is_test_architecture_supported()
	{
		// Check if the system's architecture is supported by a test
		$archs = $this->get_supported_architectures();
		return !empty($archs) ? phodevi::cpu_arch_compatible($archs) : true;
	}
	public function is_core_version_supported()
	{
		// Check if the test profile's version is compatible with pts-core
		$core_version_min = parent::requires_core_version_min();
		$core_version_max = parent::requires_core_version_max();

		return $core_version_min <= PTS_CORE_VERSION && $core_version_max > PTS_CORE_VERSION;
	}
	public function is_test_platform_supported()
	{
		// Check if the system's OS is supported by a test
		$supported = true;

		$platforms = $this->get_supported_platforms();

		if(!empty($platforms) && !in_array(phodevi::os_under_test(), $platforms))
		{
			if(phodevi::is_bsd() && in_array('Linux', $platforms) && (pts_client::executable_in_path('kldstat') && strpos(shell_exec('kldstat -n linux 2>&1'), 'linux.ko') != false))
			{
				// The OS is BSD but there is Linux API/ABI compatibility support loaded
				$supported = true;
			}
			else if(phodevi::is_hurd() && in_array('Linux', $platforms) && in_array('BSD', $platforms))
			{
				// For now until test profiles explicity express Hurd support, just list as supported the tests that work on both BSD and Linux
				// TODO: fill in Hurd support for test profiles / see what works
				$supported = true;
			}
			else
			{
				$supported = false;
			}
		}

		return $supported;
	}
	public static function generate_comparison_hash($test_identifier, $arguments, $attributes = null, $version = '', $result_scale = '', $raw_output = true)
	{
		$hash_table = array(
		$test_identifier,
		($arguments != null ? trim($arguments) : ''),
		($attributes != null ? trim($attributes) : ''),
		($version != null ? trim($version) : ''),
		($result_scale != null ? trim($result_scale) : '')
		);

		return sha1(implode(',', $hash_table), $raw_output);
	}
	public function get_test_executable_dir()
	{
		$to_execute = null;
		$test_dir = $this->get_install_dir();
		$execute_binary = $this->get_test_executable();

		if(is_file($test_dir . $execute_binary)) // previously was: (is_executable($test_dir . $execute_binary) || (phodevi::is_windows() && is_file($test_dir . $execute_binary))
		{
			$to_execute = $test_dir;
		}

		return $to_execute;
	}
	public function get_test_executable()
	{
		$exe = parent::get_test_executable();
		if(!is_file($this->get_install_dir() . $exe) && phodevi::is_windows() && is_file($this->get_install_dir() . $exe . '.bat'))
		{
			$exe .= '.bat';
		}

		return $exe;
	}
	public function get_install_dir()
	{
		return pts_client::test_install_root_path() . $this->identifier . DIRECTORY_SEPARATOR;
	}
	public function get_installer_checksum()
	{
		return $this->get_file_installer() != false ? md5_file($this->get_file_installer()) : false;
	}
	public function get_file_installer()
	{
		$test_resources_location = $this->get_resource_dir();
		$os_postfix = '_' . strtolower(phodevi::os_under_test());

		if(is_file($test_resources_location . 'install' . $os_postfix . '.sh'))
		{
			$installer = $test_resources_location . 'install' . $os_postfix . '.sh';
		}
		else if(is_file($test_resources_location . 'install.sh'))
		{
			$installer = $test_resources_location . 'install.sh';
		}
		else
		{
			$installer = null;
		}

		return $installer;
	}
	public function get_file_download_spec()
	{
		return is_file($this->get_resource_dir() . 'downloads.xml') ? $this->get_resource_dir() . 'downloads.xml' : false;
	}
	public function get_file_parser_spec()
	{
		$spec = is_file($this->get_resource_dir() . 'results-definition.xml') ? $this->get_resource_dir() . 'results-definition.xml' : false;

		if(!$spec)
		{
			$extends = $this->get_test_extension();

			if(!empty($extends))
			{
				$test_profile = new pts_test_profile($extends);
				$spec = $test_profile->get_file_parser_spec();
			}
		}

		return $spec;
	}
	public function extended_test_profiles()
	{
		// Provide an array containing the location(s) of all test(s) for the supplied object name
		$test_profiles = array();

		foreach(array_unique(array_reverse($this->get_test_extensions_recursive())) as $extended_test)
		{
			$test_profile = new pts_test_profile($extended_test);
			$test_profiles[] = $test_profile;
		}

		return $test_profiles;
	}
	public function needs_updated_install()
	{
		// Checks if test needs updating
		return ($this->test_installation == false || $this->test_installation->is_installed() == false) || $this->get_test_profile_version() != $this->test_installation->get_installed_version() || $this->get_installer_checksum() != $this->test_installation->get_installed_checksum() || $this->test_installation->get_system_hash() != phodevi::system_id_string();
	}
	public function to_json()
	{
		$file = $this->get_xml();
		$file = str_replace(array("\n", "\r", "\t"), '', $file);
		$file = trim(str_replace('"', "'", $file));
		$simple_xml = simplexml_load_string($file);
		return json_encode($simple_xml);
	}
	public function get_downloads()
	{
		$download_xml_file = $this->get_file_download_spec();
		$downloads = array();
		if($download_xml_file != null)
		{
			$xml = simplexml_load_file($download_xml_file, 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_PARSEHUGE);

			if($xml->Downloads && $xml->Downloads->Package)
			{
				foreach($xml->Downloads->Package as $pkg)
				{
					$pkg_url = isset($pkg->URL) ? $pkg->URL->__toString() : null;
					$pkg_md5 = isset($pkg->MD5) ? $pkg->MD5->__toString() : null;
					$pkg_sha256 = isset($pkg->SHA256) ? $pkg->SHA256->__toString() : null;
					$pkg_filename = isset($pkg->FileName) ? $pkg->FileName->__toString() : null;
					$pkg_filesize = isset($pkg->FileSize) ? $pkg->FileSize->__toString() : null;
					$pkg_architecture = isset($pkg->ArchitectureSpecific) ? $pkg->ArchitectureSpecific->__toString() : null;
					$pkg_platforms = isset($pkg->PlatformSpecific) ? $pkg->PlatformSpecific->__toString() : null;
					$is_optional = isset($pkg->Optional) ? $pkg->Optional->__toString() : null;
					$downloads[] = new pts_test_file_download($pkg_url, $pkg_filename, $pkg_filesize, $pkg_md5, $pkg_sha256, $pkg_platforms, $pkg_architecture, $is_optional);
				}
			}
		}
		return $downloads;
	}
	public function get_results_definition($limit = null)
	{
		$results_definition_file = $this->get_file_parser_spec();
		$results_definition = new pts_test_profile_results_definition();
		if($results_definition_file != null)
		{
			$xml = simplexml_load_file($results_definition_file, 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_PARSEHUGE);

			if($xml->SystemMonitor && ($limit == null || $limit == 'SystemMonitor'))
			{
				foreach($xml->SystemMonitor as $i)
				{
					$s = isset($i->Sensor) ? $i->Sensor->__toString() : null;
					$p = isset($i->PollingFrequency) ? $i->PollingFrequency->__toString() : null;
					$r = isset($i->Report) ? $i->Report->__toString() : null;
					$results_definition->add_system_monitor_definition($s, $p, $r);
				}
			}
			if($xml->ExtraData && ($limit == null || $limit == 'ExtraData'))
			{
				foreach($xml->ExtraData as $i)
				{
					$results_definition->add_extra_data_definition((isset($i->Identifier) ? $i->Identifier->__toString() : null));
				}
			}
			if($xml->ImageParser && ($limit == null || $limit == 'ImageParser'))
			{
				foreach($xml->ImageParser as $i)
				{
					$s = isset($i->SourceImage) ? $i->SourceImage->__toString() : null;
					$m = isset($i->MatchToTestArguments) ? $i->MatchToTestArguments->__toString() : null;
					$x = isset($i->ImageX) ? $i->ImageX->__toString() : null;
					$y = isset($i->ImageY) ? $i->ImageY->__toString() : null;
					$w = isset($i->ImageWidth) ? $i->ImageWidth->__toString() : null;
					$h = isset($i->ImageHeight) ? $i->ImageHeight->__toString() : null;
					$results_definition->add_image_parser_definition($s, $m, $x, $y, $w, $h);
				}
			}
			if($xml->ResultsParser && ($limit == null || $limit == 'ResultsParser'))
			{
				foreach($xml->ResultsParser as $i)
				{
					$ot = isset($i->OutputTemplate) ? $i->OutputTemplate->__toString() : null;
					$mtta = isset($i->MatchToTestArguments) ? $i->MatchToTestArguments->__toString() : null;
					$rk = isset($i->ResultKey) ? $i->ResultKey->__toString() : null;
					$lh = isset($i->LineHint) ? $i->LineHint->__toString() : null;
					$lbh = isset($i->LineBeforeHint) ? $i->LineBeforeHint->__toString() : null;
					$lah = isset($i->LineAfterHint) ? $i->LineAfterHint->__toString() : null;
					$rbs = isset($i->ResultBeforeString) ? $i->ResultBeforeString->__toString() : null;
					$ras = isset($i->ResultAfterString) ? $i->ResultAfterString->__toString() : null;
					$sfr = isset($i->StripFromResult) ? $i->StripFromResult->__toString() : null;
					$srp = isset($i->StripResultPostfix) ? $i->StripResultPostfix->__toString() : null;
					$mm = isset($i->MultiMatch) ? $i->MultiMatch->__toString() : null;
					$drb = isset($i->DivideResultBy) ? $i->DivideResultBy->__toString() : null;
					$drd = isset($i->DivideResultDivisor) ? $i->DivideResultDivisor->__toString() : null;
					$mrb = isset($i->MultiplyResultBy) ? $i->MultiplyResultBy->__toString() : null;
					$rs = isset($i->ResultScale) ? $i->ResultScale->__toString() : null;
					$rpro = isset($i->ResultProportion) ? $i->ResultProportion->__toString() : null;
					$rpre = isset($i->ResultPrecision) ? $i->ResultPrecision->__toString() : null;
					$ad = isset($i->ArgumentsDescription) ? $i->ArgumentsDescription->__toString() : null;
					$atad = isset($i->AppendToArgumentsDescription) ? $i->AppendToArgumentsDescription->__toString() : null;
					$ff = isset($i->FileFormat) ? $i->FileFormat->__toString() : null;
					$tcts = isset($i->TurnCharsToSpace) ? $i->TurnCharsToSpace->__toString() : null;
					$dob = isset($i->DeleteOutputBefore) ? $i->DeleteOutputBefore->__toString() : null;
					$doa = isset($i->DeleteOutputAfter) ? $i->DeleteOutputAfter->__toString() : null;
					$df = isset($i->DisplayFormat) ? $i->DisplayFormat->__toString() : null;
					$ri = isset($i->Importance) ? $i->Importance->__toString() : null;
					$results_definition->add_result_parser_definition($ot, $mtta, $rk, $lh, $lbh, $lah, $rbs, $ras, $sfr, $srp, $mm, $drb, $mrb, $rs, $rpro, $rpre, $ad, $atad, $ff, $tcts, $dob, $doa, $df, $drd, $ri);
				}
			}
		}

		return $results_definition;
	}
}

?>
