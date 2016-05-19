<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2016, Phoronix Media
	Copyright (C) 2008 - 2016, Michael Larabel

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

class pts_test_suite
{
	protected $identifier;
	private $title;
	private $description;
	private $version;
	private $maintainer;
	private $test_type;
	private $run_mode;
	private $requires_minimum_core_version;
	private $requires_maximum_core_version;
	private $pre_run_message;
	private $post_run_message;
	protected $test_objects;
	protected $test_names;

	public function __construct($identifier)
	{
	/*
		if(PTS_IS_CLIENT)
		{
			$ob_identifier = pts_openbenchmarking::evaluate_string_to_qualifier($identifier, true, 'suite');

			if($ob_identifier != false)
			{
				$identifier = $ob_identifier;
			}
		}
		$this->identifier = $identifier;

		if(!isset($xml_file[512]) && defined('PTS_TEST_SUITE_PATH') && is_file(PTS_TEST_SUITE_PATH . $identifier . '/suite-definition.xml'))
		{
			$read = PTS_TEST_SUITE_PATH . $identifier . '/suite-definition.xml';
		}
		else if(substr($identifier, -4) == '.zip' && is_file($identifier))
		{
			$zip = new ZipArchive();

			if($zip->open($identifier) === true)
			{
				$read = $zip->getFromName('suite-definition.xml');
				$zip->close();
			}
		}
		else if(isset(self::$temp_suite[$identifier]))
		{
			$read = self::$temp_suite[$identifier];
		}
		else
		{
			$read = $identifier;
		}

		$xml_options = LIBXML_COMPACT | LIBXML_PARSEHUGE;
		if(is_file($read))
		{
			$xml = simplexml_load_file($read, 'SimpleXMLElement', $xml_options);
		}
		else
		{
			$this->raw_xml = $read;
			if(strpos($read, '<') !== false)
			{
				$xml = simplexml_load_string($read, 'SimpleXMLElement', $xml_options);
			}
		}
*/
		if(PTS_IS_CLIENT)
		{
			$ob_identifier = pts_openbenchmarking::evaluate_string_to_qualifier($identifier, true, 'suite');

			if($ob_identifier != false)
			{
				$identifier = $ob_identifier;
			}
		}

		$this->identifier = $identifier;
		$xml_parser = new pts_suite_nye_XmlReader($identifier);
		$this->title = $xml_parser->getXMLValue('PhoronixTestSuite/SuiteInformation/Title');
		$this->description = $xml_parser->getXMLValue('PhoronixTestSuite/SuiteInformation/Description');
		$this->maintainer = $xml_parser->getXMLValue('PhoronixTestSuite/SuiteInformation/Maintainer');
		$this->version = $xml_parser->getXMLValue('PhoronixTestSuite/SuiteInformation/Version');
		$this->test_type = $xml_parser->getXMLValue('PhoronixTestSuite/SuiteInformation/TestType');
		$this->run_mode = $xml_parser->getXMLValue('PhoronixTestSuite/SuiteInformation/RunMode');
		$this->requires_minimum_core_version = $xml_parser->getXMLValue('PhoronixTestSuite/SuiteInformation/RequiresCoreVersionMin', null);
		$this->requires_maximum_core_version = $xml_parser->getXMLValue('PhoronixTestSuite/SuiteInformation/RequiresCoreVersionMax', null);
		$this->pre_run_message = $xml_parser->getXMLValue('PhoronixTestSuite/SuiteInformation/PreRunMessage');
		$this->post_run_message = $xml_parser->getXMLValue('PhoronixTestSuite/SuiteInformation/PostRunMessage');

		$this->test_objects = array();
		$test_names = $xml_parser->getXMLArrayValues('PhoronixTestSuite/Execute/Test');
		$sub_modes = $xml_parser->getXMLArrayValues('PhoronixTestSuite/Execute/Mode');
		$sub_arguments = $xml_parser->getXMLArrayValues('PhoronixTestSuite/Execute/Arguments');
		$sub_arguments_description = $xml_parser->getXMLArrayValues('PhoronixTestSuite/Execute/Description');
		$override_test_options = $xml_parser->getXMLArrayValues('PhoronixTestSuite/Execute/OverrideTestOptions');

		for($i = 0; $i < count($test_names); $i++)
		{
			$obj = pts_types::identifier_to_object($test_names[$i]);

			if($obj instanceof pts_test_profile)
			{
				// Check for test profile values to override
				$override_options = array();
				if(!empty($override_test_options[$i]))
				{
					foreach(explode(';', $override_test_options[$i]) as $override_string)
					{
						$override_segments = pts_strings::trim_explode('=', $override_string);

						if(count($override_segments) == 2 && !empty($override_segments[0]) && !empty($override_segments[1]))
						{
							$override_options[$override_segments[0]] = $override_segments[1];
						}
					}
				}

				switch($sub_modes[$i])
				{
					case 'BATCH':
						$option_output = pts_test_run_options::batch_user_options($obj);
						break;
					case 'DEFAULTS':
						$option_output = pts_test_run_options::default_user_options($obj);
						break;
					default:
						$option_output = array(array($sub_arguments[$i]), array($sub_arguments_description[$i]));
						break;
				}

				foreach(array_keys($option_output[0]) as $x)
				{
					if($override_options != null)
					{
						$test_profile->set_override_values($override_options);
					}

					$test_result = new pts_test_result($obj);
					$test_result->set_used_arguments($option_output[0][$x]);
					$test_result->set_used_arguments_description($option_output[1][$x]);

					$this->test_objects[] = $test_result;
				}
			}
			else if($obj instanceof pts_test_suite)
			{
				foreach($obj->get_contained_test_result_objects() as $test_result)
				{
					$this->test_objects[] = $test_result;
				}
			}
		}
		$this->test_names = $test_names;
	}
	public static function is_suite($identifier)
	{
		$identifier = pts_openbenchmarking::evaluate_string_to_qualifier($identifier, true, 'suite');
		return is_file(PTS_TEST_SUITE_PATH . $identifier . '/suite-definition.xml');
	}
	public function needs_updated_install()
	{
		foreach(pts_types::identifiers_to_test_profile_objects($this->get_identifier(), false, true) as $test_profile)
		{
			if($test_profile->test_installation == false || $test_profile->test_installation->get_installed_system_identifier() != phodevi::system_id_string())
			{
				return true;
			}
		}

		return false;
	}
	public function is_supported($report_warnings = false)
	{
		$supported_size = $original_size = count($this->test_objects);

		foreach($this->test_objects as &$obj)
		{
			if($obj->test_profile->is_supported($report_warnings) == false)
			{
				$supported_size--;
			}
		}

		if($supported_size == 0)
		{
			$return_code = 0;
		}
		else if($supported_size != $original_size)
		{
			$return_code = 1;
		}
		else
		{
			$return_code = 2;
		}

		return $return_code;
	}
	public function get_unique_test_count()
	{
		$unique_tests = array();
		foreach($this->test_objects as &$obj)
		{
			pts_arrays::unique_push($unique_tests, $obj->test_profile->get_identifier());
		}
		return count($unique_tests);
	}
	public function get_contained_test_result_objects()
	{
		return $this->test_objects;
	}
	public function is_core_version_supported()
	{
		// Check if the test suite's version is compatible with pts-core
		$core_version_min = parent::requires_core_version_min();
		$core_version_max = parent::requires_core_version_max();

		return $core_version_min <= PTS_CORE_VERSION && $core_version_max > PTS_CORE_VERSION;
	}
	public function __toString()
	{
		return $this->get_identifier() . ' [v' . $this->get_version() . ']';
	}
	public function get_identifier($bind_version = true)
	{
		$identifier = $this->identifier;

		if($bind_version == false && ($c = strrpos($identifier, '-')))
		{
			if(pts_strings::is_version(substr($identifier, ($c + 1))))
			{
				$identifier = substr($identifier, 0, $c);
			}
		}

		return $identifier;
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
	public function requires_core_version_min()
	{
		return $this->requires_minimum_core_version != null ? $this->requires_minimum_core_version : 2950;
	}
	public function requires_core_version_max()
	{
		return $this->requires_maximum_core_version != null ? $this->requires_maximum_core_version : 9990;
	}
	public function get_description()
	{
		return $this->description;
	}
	public function get_title()
	{
		return $this->title;
	}
	public function get_version()
	{
		return $this->version;
	}
	public function get_maintainer()
	{
		return $this->maintainer;
	}
	public function get_suite_type()
	{
		return $this->test_type;
	}
	public function get_pre_run_message()
	{
		return $this->pre_run_message;
	}
	public function get_post_run_message()
	{
		return $this->post_run_message;
	}
	public function get_run_mode()
	{
		return $this->run_mode;
	}
	public function get_test_names()
	{
		return $this->test_names;
	}
	public function get_unique_test_names()
	{
		return array_unique($this->get_test_names());
	}
	public function get_contained_test_profiles()
	{
		$test_profiles = array();

		foreach($this->test_objects as $result_objects)
		{
			$test_profiles[] = $result_objects->test_profile;
		}

		return $test_profiles;
	}
}

?>
