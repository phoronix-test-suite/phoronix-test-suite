<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel

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

class pts_test_profile_details
{
	var $identifier;
	var $name;
	var $maintainer;
	var $project_url;
	var $description;
	var $version;
	var $profile_version;
	var $license;
	var $status;
	var $test_version;
	var $hardware_type;
	var $software_type;
	var $test_download_size;
	var $test_environment_size;
	var $test_maintainer;
	var $dependencies;

	public function __construct($identifier)
	{
		$xml_parser = new pts_test_tandem_XmlReader($identifier);
		$this->identifier = $identifier;
		$this->name = $xml_parser->getXMLValue(P_TEST_TITLE);
		$this->license = $xml_parser->getXMLValue(P_TEST_LICENSE);
		$this->description = $xml_parser->getXMLValue(P_TEST_DESCRIPTION);
		$this->maintainer = $xml_parser->getXMLValue(P_TEST_MAINTAINER);
		$this->status = $xml_parser->getXMLValue(P_TEST_STATUS);
		$this->test_version = $xml_parser->getXMLValue(P_TEST_VERSION);
		$this->version = $xml_parser->getXMLValue(P_TEST_PTSVERSION);
		$this->test_maintainer = $xml_parser->getXMLValue(P_TEST_MAINTAINER);
		$this->hardware_type = $xml_parser->getXMLValue(P_TEST_HARDWARE_TYPE);
		$this->software_type = $xml_parser->getXMLValue(P_TEST_SOFTWARE_TYPE);
		$this->dependencies = $xml_parser->getXMLValue(P_TEST_EXDEP);
		$this->project_url = $xml_parser->getXMLValue(P_TEST_PROJECTURL);

		$this->test_download_size = pts_estimated_download_size($identifier);
		$this->test_environment_size = pts_estimated_environment_size($identifier);
	}
	public function get_maintainer()
	{
		$test_maintainer = array_map("trim", explode("|", $this->maintainer));
		$test_maintainer = $test_maintainer[0] . (count($test_maintainer) == 2 ? " <" . $test_maintainer[1] . ">" : null);

		return $test_maintainer;
	}
	public function get_test_hardware_type()
	{
		return $this->hardware_type;
	}
	public function get_test_software_type()
	{
		return $this->software_type;
	}
	public function get_license()
	{
		return $this->license;
	}
	public function get_project_url()
	{
		return $this->project_url;
	}
	public function get_download_size()
	{
		return $this->test_download_size;
	}
	public function get_environment_size()
	{
		return $this->test_environment_size;
	}
	public function get_description()
	{
		return $this->description;
	}
	public function get_name()
	{
		return $this->name;
	}
	public function get_dependencies()
	{
		return array_map("trim", explode(",", $this->dependencies));
	}
	public function suites_using_this_test()
	{
		$associated_suites = array();

		foreach(pts_available_suites_array() as $identifier)
		{
		 	$xml_parser = new pts_suite_tandem_XmlReader($identifier);
			$name = $xml_parser->getXMLValue(P_SUITE_TITLE);
			$tests = pts_contained_tests($identifier);

			if(in_array($this->identifier, $tests))
			{
				array_push($associated_suites, $identifier);
			}
		}

		return $associated_suites;
	}
	public function info_string()
	{
		$str = "";

		$test_title = $this->name;
		if(!empty($this->test_version))
		{
			$test_title .= " " . $this->test_version;
		}
		$str .= pts_string_header($test_title);

		$str .= "Test Version: " . $this->version . "\n";
		$str .= "Maintainer: " . $this->get_maintainer() . "\n";
		$str .= "Test Type: " . $this->get_test_hardware_type() . "\n";
		$str .= "Software Type: " . $this->get_test_software_type() . "\n";
		$str .= "License Type: " . $this->get_license() . "\n";
		$str .= "Test Status: " . $this->status . "\n";
		$str .= "Project Web-Site: " . $this->get_project_url() . "\n";

		if(!empty($this->test_download_size))
		{
			$str .= "Download Size: " . $this->test_download_size . " MB\n";
		}
		if(!empty($this->test_environment_size))
		{
			$str .= "Environment Size: " . $this->test_environment_size . " MB\n";
		}
		if(($el = pts_estimated_run_time($this->identifier)) > 0)
		{
			echo "Estimated Length: " . pts_format_time_string($el, "SECONDS", true, 60) . "\n";
		}

		$str .= "\nDescription: " . $this->get_description() . "\n";

		if(pts_test_installed($this->identifier))
		{
			$xml_parser = new pts_installed_test_tandem_XmlReader($this->identifier, false);
			$last_run = $xml_parser->getXMLValue(P_INSTALL_TEST_LASTRUNTIME);
			$avg_time = $xml_parser->getXMLValue(P_INSTALL_TEST_AVG_RUNTIME);

			if($last_run == "0000-00-00 00:00:00")
			{
				$last_run = "Never";
			}

			$str .= "\nTest Installed: Yes\n";
			$str .= "Last Run: " . $last_run . "\n";

			if($avg_time > 0)
			{
				$str .= "Average Run-Time: " . $avg_time . " Seconds\n";
			}
			if($last_run != "Never")
			{
				$times_run = $xml_parser->getXMLValue(P_INSTALL_TEST_TIMESRUN);

				if($times_run == null)
				{
					$times_run = 0;
				}

				$str .= "Times Run: " . $times_run . "\n";
			}
		}
		else
		{
			$str .= "\nTest Installed: No\n";
		}

		if(!empty($this->dependencies))
		{
			$str .= "\nSoftware Dependencies:\n";
			foreach($this->dependency_names() as $dependency)
			{
					$str .= "- " . $dependency . "\n";
			}
		}

		$associated_suites = $this->suites_using_this_test();
		if(count($associated_suites) > 0)
		{
			asort($associated_suites);
			$str .= "\nSuites Using This Test:\n";
			foreach($associated_suites as $suite)
			{
				$str .= "- " . $suite . "\n";
			}
		}

		return $str;
	}
	public function verified_state()
	{
		return !in_array($this->status, array("PRIVATE", "BROKEN", "EXPERIMENTAL", "UNVERIFIED"));
	}
	public function __toString()
	{
		$str = "";

		if(IS_DEBUG_MODE)
		{
			$str = sprintf("%-18ls %-6ls %-6ls %-12ls %-12ls %-4ls %-4ls %-22ls\n", $this->identifier, $this->test_version, $this->version, $this->status, $this->license, $this->test_download_size, $this->test_environment_size, $this->test_maintainer);
		}
		else if(!empty($this->name) && (pts_is_assignment("LIST_ALL_TESTS") || $this->verified_state()))
		{
			$str = sprintf("%-18ls - %-36ls [%s, %10ls]\n", $this->identifier, $this->name, $this->status, $this->license);
		}

		return $str;
	}
	public function dependency_names()
	{
		$dependency_names = array();

		$xml_parser = new tandem_XmlReader(XML_DISTRO_DIR . "generic-packages.xml");
		$package_name = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_GENERIC);
		$title = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_TITLE);

		foreach($this->get_dependencies() as $dependency)
		{
			for($i = 0; $i < count($title); $i++)
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
}

?>
