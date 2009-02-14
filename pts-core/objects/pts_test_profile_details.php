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
	var $estimated_length;
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
		$this->estimated_length = $xml_parser->getXMLValue(P_TEST_ESTIMATEDTIME);
		$this->dependencies = $xml_parser->getXMLValue(P_TEST_EXDEP);
		$this->project_url = $xml_parser->getXMLValue(P_TEST_PROJECTURL);

		$this->test_download_size = pts_estimated_download_size($identifier);
		$this->test_environment_size = pts_test_estimated_environment_size($identifier);
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

		$test_maintainer = explode("|", $this->maintainer);
		if(count($test_maintainer) == 2)
		{
			$test_maintainer = trim($test_maintainer[0]) . " <" . trim($test_maintainer[1]) . ">";
		}
		else
		{
			$test_maintainer = $test_maintainer[0];
		}

		$str .= "Test Version: " . $this->version . "\n";
		$str .= "Maintainer: " . $test_maintainer . "\n";
		$str .= "Test Type: " . $this->hardware_type . "\n";
		$str .= "Software Type: " . $this->software_type . "\n";
		$str .= "License Type: " . $this->license . "\n";
		$str .= "Test Status: " . $this->status . "\n";
		$str .= "Project Web-Site: " . $this->project_url . "\n";

		if(!empty($this->test_download_size))
		{
			$str .= "Download Size: " . $this->test_download_size . " MB\n";
		}
		if(!empty($this->test_environment_size))
		{
			$str .= "Environment Size: " . $this->test_environment_size . " MB\n";
		}
		if(!empty($this->estimated_length))
		{
			echo "Estimated Length: " . pts_estimated_time_string($this->estimated_length) . "\n";
		}

		$str .= "\nDescription: " . $this->description . "\n";

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
			foreach(explode(",", $this->dependencies) as $dependency)
			{
				if(($title = pts_dependency_name(trim($dependency)) )!= "")
				{
					$str .= "- " . $title . "\n";
				}
			}
		}

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
	public function __toString()
	{
		$str = "";

		if(IS_DEBUG_MODE)
		{
			$str = sprintf("%-18ls %-6ls %-6ls %-12ls %-12ls %-4ls %-4ls %-22ls\n", $this->identifier, $this->test_version, $this->version, $this->status, $this->license, $this->test_download_size, $this->test_environment_size, $this->test_maintainer);
		}
		else if(!empty($this->name) && (pts_is_assignment("LIST_ALL_TESTS") || !in_array($this->status, array("PRIVATE", "BROKEN", "EXPERIMENTAL", "UNVERIFIED"))))
		{
			$str = sprintf("%-18ls - %-36ls [%s, %10ls]\n", $this->identifier, $this->name, $this->status, $this->license);
		}

		return $str;
	}
}

?>
