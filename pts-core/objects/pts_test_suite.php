<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2019, Phoronix Media
	Copyright (C) 2008 - 2019, Michael Larabel

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
	protected $raw_xml;
	static $temp_suite;
	protected $xml_file_location = false;

	public function __construct($identifier = null)
	{
		$this->test_objects = array();
		$this->test_names = array();
		if($identifier == null)
			return;

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
		$this->raw_xml = $read;
		if(is_file($read))
		{
			$this->xml_file_location = $read;
			$xml = simplexml_load_file($this->xml_file_location, 'SimpleXMLElement', $xml_options);
		}
		else
		{
			$xml = $read;
			if(strpos($read, '<') !== false)
			{
				$xml = simplexml_load_string($read, 'SimpleXMLElement', $xml_options);
			}
		}

		if($xml == null)
		{
			return;
		}
		// XInclude support
		if(function_exists('dom_import_simplexml'))
		{
			$dom = dom_import_simplexml($xml);
			$dom->ownerDocument->xinclude();
		}

		if(isset($xml->SuiteInformation))
		{
			$this->title = self::clean_input($xml->SuiteInformation->Title);
			$this->description = self::clean_input($xml->SuiteInformation->Description);
			$this->maintainer = self::clean_input($xml->SuiteInformation->Maintainer);
			$this->version = self::clean_input($xml->SuiteInformation->Version);
			$this->test_type = self::clean_input($xml->SuiteInformation->TestType);
			$this->run_mode = self::clean_input($xml->SuiteInformation->RunMode);
			$this->requires_minimum_core_version = self::clean_input($xml->SuiteInformation->RequiresCoreVersionMin);
			$this->requires_maximum_core_version = self::clean_input($xml->SuiteInformation->RequiresCoreVersionMax);
			$this->pre_run_message = self::clean_input($xml->SuiteInformation->PreRunMessage);
			$this->post_run_message = self::clean_input($xml->SuiteInformation->PostRunMessage);
		}

		if(isset($xml->Execute))
		{
			foreach($xml->Execute as $to_execute)
			{
				$test_name = self::clean_input($to_execute->Test);
				$this->test_names[] = $test_name;
				$obj = pts_types::identifier_to_object($test_name);

				if($obj instanceof pts_test_profile)
				{
					// Check for test profile values to override
					$override_options = array();
					if(isset($to_execute->OverrideTestOptions) && !empty($to_execute->OverrideTestOptions))
					{
						foreach(explode(';', self::clean_input($to_execute->OverrideTestOptions)) as $override_string)
						{
							$override_segments = pts_strings::trim_explode('=', $override_string);

							if(count($override_segments) == 2 && !empty($override_segments[0]) && !empty($override_segments[1]))
							{
								$override_options[$override_segments[0]] = $override_segments[1];
							}
						}
					}

					switch((isset($to_execute->Mode) ? self::clean_input($to_execute->Mode) : null))
					{
						case 'BATCH':
							$option_output = pts_test_run_options::batch_user_options($obj);
							break;
						case 'DEFAULTS':
							$option_output = pts_test_run_options::default_user_options($obj);
							break;
						default:
							$option_output = array(array((isset($to_execute->Arguments) ? self::clean_input($to_execute->Arguments) : null)), array((isset($to_execute->Description) ? self::clean_input($to_execute->Description) : null)));
							break;
					}

					foreach(array_keys($option_output[0]) as $x)
					{
						if($override_options != null)
						{
							$obj->set_override_values($override_options);
						}

						$this->add_to_suite($obj, $option_output[0][$x], $option_output[1][$x]);
					}
				}
				else if($obj instanceof pts_test_suite)
				{
					$this->add_suite_tests_to_suite($obj);
				}
			}
		}
	}
	public function add_suite_tests_to_suite($suite)
	{
		foreach($suite->get_contained_test_result_objects() as $test_result)
		{
			$this->test_objects[] = $test_result;
		}
	}
	public function add_to_suite($test, $arguments = null, $arguments_description = null)
	{
		if(!($test instanceof pts_test_profile))
		{
			$test = new pts_test_profile($test);
		}

		$test_result = new pts_test_result($test);
		$test_result->set_used_arguments($arguments);
		$test_result->set_used_arguments_description($arguments_description);
		$this->test_objects[] = $test_result;
	}
	public static function set_temporary_suite($name, $suite_xml)
	{
		self::$temp_suite[$name] = $suite_xml;
	}
	public static function is_temporary_suite($name)
	{
		return isset(self::$temp_suite[$name]);
	}
	public function get_file_location()
	{
		return $this->xml_file_location;
	}
	public function validate()
	{
		$dom = new DOMDocument();
		if(is_file($this->raw_xml))
		{
			$dom->load($this->raw_xml);
		}
		else
		{
			$dom->loadXML($this->raw_xml);
		}
		return $dom->schemaValidate(pts_openbenchmarking::openbenchmarking_standards_path() . 'schemas/test-suite.xsd');
	}
	protected static function clean_input($value)
	{
		if(is_array($value))
		{
			return array_map(array($this, 'clean_input'), $value);
		}
		else
		{
			return strip_tags($value);
		}
	}
	public static function is_suite($identifier)
	{
		if(is_file(PTS_TEST_SUITE_PATH . $identifier . '/suite-definition.xml'))
		{
			return $identifier;
		}
		else
		{
			$identifier = pts_openbenchmarking::evaluate_string_to_qualifier($identifier, true, 'suite');
		}
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
		$core_version_min = $this->requires_core_version_min();
		$core_version_max = $this->requires_core_version_max();

		return $core_version_min <= PTS_CORE_VERSION && $core_version_max > PTS_CORE_VERSION;
	}
	public function __toString()
	{
		return $this->get_identifier() . ' [v' . $this->get_version() . ']';
	}
	public function set_identifier($s)
	{
		$this->identifier = $s;
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
	public function set_core_version_min($s)
	{
		$this->requires_minimum_core_version = $s;
	}
	public function set_core_version_max($s)
	{
		$this->requires_maximum_core_version = $s;
	}
	public function requires_core_version_min()
	{
		return $this->requires_minimum_core_version != null ? $this->requires_minimum_core_version : 2950;
	}
	public function requires_core_version_max()
	{
		return $this->requires_maximum_core_version != null ? $this->requires_maximum_core_version : 9990;
	}
	public function set_description($s)
	{
		$this->description = $s;
	}
	public function get_description()
	{
		return $this->description;
	}
	public function set_title($s)
	{
		$this->title = $s;
	}
	public function get_title()
	{
		return $this->title;
	}
	public function set_version($s)
	{
		$this->version = $s;
	}
	public function get_version()
	{
		return $this->version;
	}
	public function set_maintainer($s)
	{
		$this->maintainer = $s;
	}
	public function get_maintainer()
	{
		return $this->maintainer;
	}
	public function set_suite_type($s)
	{
		$this->test_type = $s;
	}
	public function get_suite_type()
	{
		return $this->test_type;
	}
	public function set_pre_run_message($s)
	{
		$this->pre_run_message = $s;
	}
	public function get_pre_run_message()
	{
		return $this->pre_run_message;
	}
	public function set_post_run_message($s)
	{
		$this->post_run_message = $s;
	}
	public function get_post_run_message()
	{
		return $this->post_run_message;
	}
	public function set_run_mode($s)
	{
		$this->run_mode = $s;
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
	public function sort_contained_tests()
	{
		usort($this->test_objects, array($this, 'cmp_result_object_sort_title'));
	}
	public function cmp_result_object_sort_title($a, $b)
	{
		$a_comp = $a->test_profile->get_title();
		$b_comp = $b->test_profile->get_title();
		return strcmp($a_comp, $b_comp);
	}
	public function get_xml($to = null, $force_nice_formatting = false, $bind_versions = true)
	{
		$xml_writer = new nye_XmlWriter(null, $force_nice_formatting);
		$xml_writer->addXmlNode('PhoronixTestSuite/SuiteInformation/Title', $this->get_title());
		$xml_writer->addXmlNode('PhoronixTestSuite/SuiteInformation/Version', $this->get_version());
		$xml_writer->addXmlNode('PhoronixTestSuite/SuiteInformation/TestType', $this->get_suite_type());
		$xml_writer->addXmlNode('PhoronixTestSuite/SuiteInformation/Description', $this->get_description());
		$xml_writer->addXmlNodeWNE('PhoronixTestSuite/SuiteInformation/Maintainer', $this->get_maintainer());
		$xml_writer->addXmlNodeWNE('PhoronixTestSuite/SuiteInformation/PreRunMessage', $this->get_pre_run_message());
		$xml_writer->addXmlNodeWNE('PhoronixTestSuite/SuiteInformation/PostRunMessage', $this->get_post_run_message());
		$xml_writer->addXmlNodeWNE('PhoronixTestSuite/SuiteInformation/RunMode', $this->get_run_mode());
		$xml_writer->addXmlNodeWNE('PhoronixTestSuite/SuiteInformation/RequiresCoreVersionMin', $this->requires_minimum_core_version);
		$xml_writer->addXmlNodeWNE('PhoronixTestSuite/SuiteInformation/RequiresCoreVersionMax', $this->requires_maximum_core_version);

		foreach($this->test_objects as $i => &$test)
		{
			if($test->test_profile->get_title() == null)
			{
				continue;
			}

			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/Execute/Test', $test->test_profile->get_identifier($bind_versions));
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/Execute/Arguments', $test->get_arguments());
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/Execute/Description', $test->get_arguments_description());
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/Execute/Mode', null); // XXX wire this up!
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/Execute/OverrideTestOptions', $test->test_profile->get_override_values(true));
		}
		return $xml_writer->getXML();
	}
	public function save_xml($suite_identifier = null, $save_to = null, $bind_versions = true)
	{
		$xml = $this->get_xml(null, null, $bind_versions);
		if($suite_identifier != null)
		{
			$this->set_identifier($this->clean_save_name_string($suite_identifier));
			$save_to = PTS_TEST_SUITE_PATH . 'local/' . $this->get_identifier() . '/suite-definition.xml';
			pts_file_io::mkdir(dirname($save_to));
		}

		return file_put_contents($save_to, $xml) != false;
	}
	public function clean_save_name_string($input)
	{
		$input = strtolower($input);
		$input = pts_strings::remove_redundant(pts_strings::keep_in_string(str_replace(' ', '-', trim($input)), pts_strings::CHAR_LETTER | pts_strings::CHAR_NUMERIC | pts_strings::CHAR_DASH), '-');

		if(strlen($input) > 126)
		{
			$input = substr($input, 0, 126);
		}

		return $input;
	}
}

?>
