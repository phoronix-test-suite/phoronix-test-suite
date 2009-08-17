<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2004 - 2009, Michael Larabel
	tandem_XmlReader.php: The XML writing object for the Phoronix Test Suite.

	Additional Notes: A very simple XML writer with a few extras... Does not support attributes on tags, etc.
	A work in progress. This was originally designed for just some select needs in the past. It does support linking to an XSL as 
	well as whether to format the XML or not, etc. Also provides a MD5 checksum of the XML body.

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

class tandem_XmlWriter
{
	var $xml_objects = array();
	var $xml_string_paths = array();
	var $xml_statements = array();

	var $xml_checksum = false;
	var $format_xml = true;
	var $xsl_binding = null;

	function __construct($nice_formatting = true)
	{
		$this->format_xml = ($nice_formatting == true);
	}
	function setXslBinding($url)
	{
		$this->xsl_binding = $url;
	}
	function writeXmlCheckSum()
	{
		$this->xml_checksum = true;
	}
	function addXmlObject($xml_location, $unique_identifier = 0, $xml_value = "", $std_step = null, $step_id = null)
	{
		$xml_array = array();
		$alt_step = -1;
		$steps = 0;
		
		if($std_step == null)
		{
			$std_step = 2;
		}
		if($step_id == null)
		{
			$step_id = $unique_identifier;
		}

		if(array_search($unique_identifier . "," . $xml_location, $this->xml_string_paths) !== false)
		{
			$alt_step = 2;
		}
		else
		{
			array_push($this->xml_string_paths, $unique_identifier . "," . $xml_location);
		}

		$xml_steps = explode('/', $xml_location);
		foreach(array_reverse($xml_steps) as $current_tag)
		{
			$steps++;

			if(empty($xml_array))
			{
				$xml_array = $xml_value;
			}
			if(!empty($current_tag))
			{
				$xml_array = array("$current_tag" => $xml_array);
			}

			if($steps == $std_step)
			{
				$xml_array = array("id_" . $unique_identifier => $xml_array);
			}
			if($steps == $alt_step)
			{
				$xml_array = array("id_" . $step_id => $xml_array);
			}
		}

		$this->xml_objects = array_merge_recursive($this->xml_objects, $xml_array);
	}
	function addStatement($name, $value)
	{
		$this->xml_statements[$name] = trim($name . ": " . $value));
	}
	function getXMLStatements()
	{
		$return_string = "";
		$statements_to_print = array_reverse($this->xml_statements);

		foreach($statements_to_print as $statement)
		{
			$return_string .= "<!-- " . $statement . " -->\n";
		}

		return $return_string;
	}
	function getXML()
	{
		$formatted_xml = $this->getXMLBelow($this->xml_objects, 0);

		$this->addStatement("Generated", date("Y-m-d H:i:s"));

		if($this->xml_checksum)
		{
			$this->addStatement("Checksum", md5($formatted_xml));
		}

		return "<?xml version=\"1.0\"?>\n" . $this->getXSL() . $this->getXMLStatements() . $formatted_xml;
	}
	function getXSL()
	{
		return ($this->xsl_binding != null ? "<?xml-stylesheet type=\"text/xsl\" href=\"" . $this->xsl_binding . "\" ?>\n" : "");
	}
	function getJustXML()
	{
		return $this->getXMLBelow($this->xml_objects, 0);
	}
	function getXMLBelow($statement_name, $times_deep)
	{
		$formatted_xml = "";

		foreach($statement_name as $key => $value)
		{
			if(!is_array($value))
			{
				$formatted_xml .= $this->getXMLTabs($times_deep) . "<" . $key . ">" . $value . "</" . $key . ">" . $this->getXMLBreaks();
			}
			else
			{
				if(substr($key, 0, 3) === "id_")
				{
					$formatted_xml .= $this->getXMLBelow($value, $times_deep);
				}
				else
				{
					$formatted_xml .= $this->getXMLTabs($times_deep) . "<" . $key . ">" . $this->getXMLBreaks();
					$formatted_xml .= $this->getXMLBelow($value, $times_deep + 1);
					$formatted_xml .= $this->getXMLTabs($times_deep) . "</" . $key . ">" . $this->getXMLBreaks();
				}
			}
		}

		return $formatted_xml;
	}
	function getXMLTabs($times_deep)
	{
		return ($this->format_xml ? $format = str_repeat("\t", $times_deep) : "");
	}
	function getXMLBreaks()
	{
		return ($this->format_xml ? "\n" : "");
	}
	function debugDumpArray()
	{
		return $this->xml_objects;
	}
}

?>
