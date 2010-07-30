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

class pts_test_profile
{
	private $identifier;
	private $xml_parser;

	public function __construct($identifier, $override_values = null)
	{
		$this->xml_parser = new pts_test_tandem_XmlReader($identifier);
		$this->identifier = $identifier;

		if($override_values != null && is_array($override_values))
		{
			$this->xml_parser->overrideXMLValues($override_values);
		}
	}
	public function get_identifier()
	{
		return $this->identifier;
	}
	public function get_maintainer()
	{
		return $this->xml_parser->getXMLValue(P_TEST_MAINTAINER);
	}
	public function get_test_hardware_type()
	{
		return $this->xml_parser->getXMLValue(P_TEST_HARDWARE_TYPE);
	}
	public function get_test_software_type()
	{
		return $this->xml_parser->getXMLValue(P_TEST_SOFTWARE_TYPE);
	}
	public function get_status()
	{
		return $this->xml_parser->getXMLValue(P_TEST_STATUS);
	}
	public function get_license()
	{
		return $this->xml_parser->getXMLValue(P_TEST_LICENSE);
	}
	public function get_test_profile_version()
	{
		return $this->xml_parser->getXMLValue(P_TEST_PTSVERSION);
	}
	public function get_version()
	{
		return $this->xml_parser->getXMLValue(P_TEST_VERSION);
	}
	public function get_project_url()
	{
		return $this->xml_parser->getXMLValue(P_TEST_PROJECTURL);
	}
	public function get_download_size()
	{
		return ($s = pts_estimated_download_size($this->identifier)) > 10 ? round($s) : $s;
	}
	public function get_environment_size()
	{
		return ($s = pts_estimated_environment_size($this->identifier)) > 10 ? round($s) : $s;
	}
	public function get_description()
	{
		return $this->xml_parser->getXMLValue(P_TEST_DESCRIPTION);
	}
	public function get_test_title()
	{
		return $this->xml_parser->getXMLValue(P_TEST_TITLE);
	}
	public function get_dependencies()
	{
		return pts_strings::trim_explode(",", $this->xml_parser->getXMLValue(P_TEST_EXDEP));
	}
	public function is_verified_state()
	{
		return !in_array($this->get_status(), array("PRIVATE", "BROKEN", "EXPERIMENTAL", "UNVERIFIED"));
	}
	public function get_dependency_names()
	{
		$dependency_names = array();

		$xml_parser = new pts_external_dependencies_tandem_XmlReader(STATIC_DIR . "distro-xml/generic-packages.xml");
		$package_name = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_GENERIC);
		$title = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_TITLE);

		foreach($this->get_dependencies() as $dependency)
		{
			foreach(array_keys($title) as $i)
			{
				if($dependency == $package_name[$i])
				{
					array_push($dependency_names, $title[$i]);
					break;
				}
			}
		}

		return $dependency_names;
	}
	public function get_reference_systems()
	{
		return pts_strings::trim_explode(',', $this->xml_parser->getXMLValue(P_TEST_REFERENCE_SYSTEMS));
	}
	public function get_default_arguments()
	{
		return $this->xml_parser->getXMLValue(P_TEST_DEFAULTARGUMENTS);
	}
	public function get_default_post_arguments()
	{
		return $this->xml_parser->getXMLValue(P_TEST_DEFAULT_POST_ARGUMENTS);
	}
	public function get_test_executable()
	{
		return $this->xml_parser->getXMLValue(P_TEST_EXECUTABLE, $this->identifier);
	}
	public function get_times_to_run()
	{
		return intval($this->xml_parser->getXMLValue(P_TEST_RUNCOUNT, 3));
	}
	public function get_runs_to_ignore()
	{
		return pts_strings::trim_explode(",", $this->xml_parser->getXMLValue(P_TEST_IGNORERUNS));
	}
	public function get_pre_run_message()
	{
		return $this->xml_parser->getXMLValue(P_TEST_PRERUNMSG);
	}
	public function get_post_run_message()
	{
		return $this->xml_parser->getXMLValue(P_TEST_POSTRUNMSG);
	}
	public function get_result_scale()
	{
		return $this->xml_parser->getXMLValue(P_TEST_SCALE);
	}
	public function get_result_proportion()
	{
		return $this->xml_parser->getXMLValue(P_TEST_PROPORTION);
	}
	public function get_result_format()
	{
		return $this->xml_parser->getXMLValue(P_TEST_RESULTFORMAT, "BAR_GRAPH");
	}
	public function do_auto_save_results()
	{
		return pts_strings::string_bool($this->xml_parser->getXMLValue(P_TEST_AUTO_SAVE_RESULTS, "FALSE"));
	}
	public function get_result_quantifier()
	{
		return $this->xml_parser->getXMLValue(P_TEST_QUANTIFIER);
	}
	public function is_root_required()
	{
		return $this->xml_parser->getXMLValue(P_TEST_ROOTNEEDED) == "TRUE";
	}
	public function allow_cache_share()
	{
		return $this->xml_parser->getXMLValue(P_TEST_ALLOW_CACHE_SHARE) == "TRUE";
	}
	public function allow_global_uploads()
	{
		return $this->xml_parser->getXMLValue(P_TEST_ALLOW_GLOBAL_UPLOADS) != "FALSE";
	}
	public function get_min_length()
	{
		return $this->xml_parser->getXMLValue(P_TEST_MIN_LENGTH);
	}
	public function get_max_length()
	{
		return $this->xml_parser->getXMLValue(P_TEST_MAX_LENGTH);
	}
	public function get_environment_testing_size()
	{
		return $this->xml_parser->getXMLValue(P_TEST_ENVIRONMENT_TESTING_SIZE, -1);
	}
	public function get_test_subtitle()
	{
		return $this->xml_parser->getXMLValue(P_TEST_SUBTITLE);
	}

	//
	// Set Functions
	//

	public function set_times_to_run($times)
	{
		$this->xml_parser->overrideXMLValue(P_TEST_RUNCOUNT, $times);
	}
	public function set_result_scale($scale)
	{
		$this->xml_parser->overrideXMLValue(P_TEST_SCALE, $scale);
	}
	public function set_result_proportion($proportion)
	{
		$this->xml_parser->overrideXMLValue(P_TEST_PROPORTION, $proportion);
	}
	public function set_result_format($format)
	{
		$this->xml_parser->overrideXMLValue(P_TEST_RESULTFORMAT, $format);
	}
	public function set_result_quantifier($quantifier)
	{
		$this->xml_parser->overrideXMLValue(P_TEST_QUANTIFIER, $quantifier);
	}
	public function set_version($version)
	{
		$this->xml_parser->overrideXMLValue(P_TEST_VERSION, $version);
	}
	public function set_test_title($title)
	{
		$this->xml_parser->overrideXMLValue(P_TEST_TITLE, $title);
	}
	public function set_test_profile_version($version)
	{
		$this->xml_parser->overrideXMLValue(P_TEST_PTSVERSION, $version);
	}
}

?>
