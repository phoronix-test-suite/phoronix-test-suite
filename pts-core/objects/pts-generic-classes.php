<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-generic-classes.php: Some generic classes for the Phoronix Test Suite

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

class pts_test_file_download
{
	var $url;
	var $filename;
	var $filesize;
	var $md5;

	public function __construct($url = null, $filename = null, $filesize = 0, $md5 = null)
	{
		if(empty($filename))
		{
			$filename = basename($url);
		}
		if($filename == $url)
		{
			$url = "";
		}	
		if(!is_numeric($filesize))
		{
			$filesize = 0;
		}

		$this->url = $url;
		$this->filename = $filename;
		$this->filesize = $filesize;
		$this->md5 = $md5;
	}
	public function get_download_url_array()
	{
		return array_map("trim", explode(",", $this->url));
	}
	public function get_filename()
	{
		return $this->filename;
	}
	public function get_filesize()
	{
		return $this->filesize;
	}
	public function get_md5()
	{
		return $this->md5;
	}
}
class pts_test_suite_details
{
	var $identifier;
	var $identifier_show_prefix;
	var $name;
	var $version;
	var $type;
	var $test_type;
	var $only_partially_supported = false;

	public function __construct($identifier)
	{
		$xml_parser = new tandem_XmlReader(pts_location_suite($identifier));
		$this->identifier = $identifier;
		$this->name = $xml_parser->getXMLValue(P_SUITE_TITLE);
		$this->test_type = $xml_parser->getXMLValue(P_SUITE_TYPE);
		$this->version = $xml_parser->getXMLValue(P_SUITE_VERSION);
		$this->type = $xml_parser->getXMLValue(P_SUITE_TYPE);

		$suite_support_code = pts_suite_supported($identifier);

		$this->identifier_show_prefix = " ";
		if($suite_support_code > 0)
		{
			if($suite_support_code == 1)
			{
				$this->identifier_show_prefix = "*";
				$this->only_partially_supported = true;
			}
		}
	}
	public function partially_supported()
	{
		return $this->only_partially_supported;
	}
	public function __toString()
	{
		$str = "";

		if(IS_DEBUG_MODE)
		{
			$str = sprintf("%-26ls - %-32ls %-4ls  %-12ls\n", $this->identifier_show_prefix . " " . $this->identifier, $this->name, $this->version, $this->type);
		}
		else if(!empty($this->name))
		{
			$str = sprintf("%-24ls - %-32ls [Type: %s]\n", $this->identifier_show_prefix . " " . $this->identifier, $this->name, $this->test_type);
		}

		return $str;
	}
}
class pts_user_module_details
{
	var $identifier;
	var $name;
	var $module;
	var $version;
	var $author;

	public function __construct($module_file_path)
	{
		$module = basename(substr($module_file_path, 0, strrpos($module_file_path, ".")));
		$this->module = $module;

		if(!in_array($module, pts_attached_modules()) && substr($module_file_path, -3) == "php")
		{
			include_once($module_file_path);
		}

		$this->name = pts_module_call($module, "module_name");
		$this->version = pts_module_call($module, "module_version");
		$this->author = pts_module_call($module, "module_author");
	}
	public function __toString()
	{
		return sprintf("%-22ls - %-30ls [%s]\n", $this->module, $this->name . " v" . $this->version, $this->author);
	}

}
class pts_test_profile_details
{
	var $identifier;
	var $name;
	var $version;
	var $license;
	var $status;
	var $test_version;
	var $test_download_size = false;
	var $test_environment_size = false;
	var $test_maintainer = false;

	public function __construct($identifier)
	{
		$xml_parser = new pts_test_tandem_XmlReader(pts_location_test($identifier));
		$this->identifier = $identifier;
		$this->name = $xml_parser->getXMLValue(P_TEST_TITLE);
		$this->license = $xml_parser->getXMLValue(P_TEST_LICENSE);
		$this->status = $xml_parser->getXMLValue(P_TEST_STATUS);
		$this->test_version = $xml_parser->getXMLValue(P_TEST_VERSION);
		$this->version = $xml_parser->getXMLValue(P_TEST_PTSVERSION);

		if(IS_DEBUG_MODE)
		{
			$this->test_download_size = pts_estimated_download_size($identifier);
			$this->test_environment_size = pts_test_estimated_environment_size($identifier);
			$this->test_maintainer = $xml_parser->getXMLValue(P_TEST_MAINTAINER);
		}
	}
	public function __toString()
	{
		$str = "";

		if(IS_DEBUG_MODE)
		{
			$str = sprintf("%-18ls %-6ls %-6ls %-12ls %-12ls %-4ls %-4ls %-22ls\n", $this->identifier, $this->test_version, $this->version, $this->status, $this->license, $this->test_download_size, $this->test_environment_size, $this->test_maintainer);
		}
		else if(!empty($this->name) && (pts_read_assignment("COMMAND") == "LIST_ALL_TESTS" || !in_array($this->status, array("PRIVATE", "BROKEN", "EXPERIMENTAL", "UNVERIFIED", "STANDALONE", "SCTP"))))
		{
			$str = sprintf("%-18ls - %-36ls [%s, %10ls]\n", $this->identifier, $this->name, $this->status, $this->license);
		}

		return $str;
	}
}
class pts_installed_test_details
{
	var $identifier;
	var $name;

	public function __construct($identifier)
	{
		$xml_parser = new pts_test_tandem_XmlReader(pts_location_test($identifier));
		$this->identifier = $identifier;
		$this->name = $xml_parser->getXMLValue(P_TEST_TITLE);
	}
	public function __toString()
	{
		$str = "";

		if(!empty($this->name))
		{
			$str = sprintf("%-18ls - %-30ls\n", $this->identifier, $this->name);
		}

		return $str;
	}
}
class pts_test_usage_details
{
	var $identifier;
	var $install_time;
	var $last_run_time;
	var $installed_version;
	var $average_run_time;
	var $times_run;

	public function __construct($identifier)
	{
		$xml_parser = new tandem_XmlReader(TEST_ENV_DIR . $identifier . "/pts-install.xml");
		$this->identifier = $identifier;
		$this->install_time = substr($xml_parser->getXMLValue(P_INSTALL_TEST_INSTALLTIME), 0, 10);
		$this->last_run_time = substr($xml_parser->getXMLValue(P_INSTALL_TEST_LASTRUNTIME), 0, 10);
		$this->installed_version = $xml_parser->getXMLValue(P_INSTALL_TEST_VERSION);
		$this->average_run_time = pts_format_time_string($xml_parser->getXMLValue(P_INSTALL_TEST_AVG_RUNTIME), "SECONDS", false);
		$this->times_run = $xml_parser->getXMLValue(P_INSTALL_TEST_TIMESRUN);

		if($this->last_run_time == "0000-00-00" || $this->install_time == $this->last_run_time)
		{
			$this->last_run_time = "NEVER";
			$this->times_run = "";
		}

		if(empty($this->times_run))
		{
			$this->times_run = 0;
		}
		if(empty($this->average_run_time))
		{
			$this->average_run_time = "N/A";
		}
	}
	public function __toString()
	{
		$str = "";

		if(!empty($this->installed_version))
		{
			$str = sprintf("%-18ls - %-8ls %-13ls %-11ls %-13ls %-10ls\n", $this->identifier, $this->installed_version, $this->install_time, $this->last_run_time, $this->average_run_time, $this->times_run);
		}

		return $str;
	}
}
class pts_test_results_details
{
	var $saved_identifier;
	var $title;
	var $suite;
	var $identifiers_r;

	public function __construct($saved_results_file)
	{
		$this->saved_identifier = array_pop(explode("/", dirname($saved_results_file)));

		$xml_parser = new tandem_XmlReader($saved_results_file);
		$this->title = $xml_parser->getXMLValue(P_RESULTS_SUITE_TITLE);
		$this->suite = $xml_parser->getXMLValue(P_RESULTS_SUITE_NAME);

		$raw_results = $xml_parser->getXMLArrayValues(P_RESULTS_RESULTS_GROUP);
		$results_xml = new tandem_XmlReader($raw_results[0]);
		$this->identifiers_r = $results_xml->getXMLArrayValues(S_RESULTS_RESULTS_GROUP_IDENTIFIER);
	}
	public function __toString()
	{
		$str = "";

		if(!empty($this->title))
		{
			$str .= $title . "\n";
			$str .= sprintf("Saved Name: %-18ls Test: %-18ls \n", $this->saved_identifier, $this->suite);

			foreach($this->identifiers_r as $id)
			{
				$str .= "\t- " . $id . "\n";
			}
		}

		return $str;
	}
}
class pts_test_result
{
	var $result;
	var $result_scale;
	var $result_format;
	var $result_proportion;
	var $result_quantifier;
	var $trial_results;
	var $attributes;

	public function __construct($result = 0, $result_scale = "", $result_format = "")
	{
		$this->result = $result;
		$this->result_scale = $result_scale;
		$this->result_format = $result_format;

		$this->trial_results = array();
		$this->attributes = array();
		$this->result_quantifier = null;
		$this->result_proportion = null;
	}
	public function set_result($result)
	{
		$this->result = $result;
	}
	public function set_result_scale($result_scale)
	{
		$this->result_scale = $result_scale;
	}
	public function set_result_format($result_format)
	{
		$this->result_format = $result_format;
	}
	public function set_result_proportion($result_proportion)
	{
		$this->result_proportion = $result_proportion;
	}
	public function set_result_quantifier($result_quantifier)
	{
		$this->result_quantifier = $result_quantifier;
	}
	public function set_attribute($name, $value)
	{
		$this->attributes[$name] = $value;
	}
	public function get_result()
	{
		return $this->result;
	}
	public function get_result_scale()
	{
		return $this->result_scale;
	}
	public function get_result_format()
	{
		return $this->result_format;
	}
	public function get_result_proportion()
	{
		return $this->result_proportion;
	}
	public function get_attribute($name)
	{
		if(isset($this->attributes[$name]) && !empty($this->attributes[$name]))
		{
			return $this->attributes[$name];
		}
	}
	public function add_trial_run_result($result)
	{
		if(!empty($result))
		{
			array_push($this->trial_results, $result);
		}
	}
	public function calculate_end_result(&$return_string)
	{
		$END_RESULT = 0;
		if($this->result_format == "NO_RESULT")
		{
			// Nothing to do
			$return_string = null;
		}
		else if($this->result_format == "PASS_FAIL" || $this->result_format == "MULTI_PASS_FAIL")
		{
			// Calculate pass/fail type
			$return_string .= "(" . $this->result_scale . ")\n";
			$END_RESULT = -1;
			$i = 1;

			if(count($this->trial_results) == 1)
			{
				$END_RESULT = $this->trial_results[0];
			}
			else
			{
				foreach($this->trial_results as $result)
				{
					if($result == "FALSE" || $result == "0" || $result == "FAIL")
					{
						$this_result = "FAIL";

						if($END_RESULT == -1 || $END_RESULT == "PASS")
						{
							$END_RESULT = "FAIL";
						}
					}
					else
					{
						$this_result = "PASS";

						if($END_RESULT == -1)
						{
							$END_RESULT = "PASS";
						}
					}

					$return_string .= "Trial $i: " . $this_result . "\n";
					$i++;
				}
			}

			$return_string .= "\nFinal: " . $END_RESULT . "\n";
		}
		else
		{
			// Result is of a normal numerical type

			if($this->result_quantifier == "MAX")
			{
				$max_value = $this->trial_results[0];
				foreach($this->trial_results as $result)
				{
					if($result > $max_value)
					{
						$max_value = $result;
					}

					$return_string .= $result . " " . $this->result_scale . "\n";
				}
				$return_string .= "\nMaximum: " . $max_value . " " . $this->result_scale;
				$END_RESULT = $max_value;
			}
			else if($this->result_quantifier == "MIN")
			{
				$min_value = $this->trial_results[0];
				foreach($this->trial_results as $result)
				{
					if($result < $min_value)
					{
						$min_value = $result;
					}

					$return_string .= $result . " " . $this->result_scale . "\n";
				}
				$return_string .= "\nMinimum: " . $min_value . " " . $this->result_scale;
				$END_RESULT = $min_value;
			}
			else
			{
				// assume AVG (average)
				$TOTAL_RESULT = 0;
				foreach($this->trial_results as $result)
				{
					$TOTAL_RESULT += trim($result);
					$return_string .= $result . " " . $this->result_scale . "\n";
				}

				if(count($this->trial_results) > 0)
				{
					$END_RESULT = pts_trim_double($TOTAL_RESULT / count($this->trial_results), 2);
				}
				else
				{
					$END_RESULT = pts_trim_double($TOTAL_RESULT, 2);
				}

				$return_string .= "\nAverage: " . $END_RESULT . " " . $this->result_scale;
			}
		}
		$this->set_result($END_RESULT);
	}
}

?>
