<?php

/*
	Phoronix Test Suite "Trondheim"
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2004-2008, Michael Larabel
	tandem_XmlReader.php: The XML writing object for the Phoronix Test Suite.

	Additional Notes: A very simple XML writer with a few extras... Does not support attributes on tags, etc.
	A work in progress. This was originally designed for just some select needs in the past. It does support linking to an XSL as 
	well as whether to format the XML or not, etc. Also provides a MD5 checksum of the XML body.
*/

class tandem_XmlWriter
{
	var $XML_OBJECTS = array();
	var $XML_STRING_PATHS = array();
	var $XML_STATEMENTS = array();
	var $XML_CHECKSUM = false;
	var $FORMAT_XML;
	var $XSL_BINDING = null;

	function __construct($READ_FROM_XML = "", $NOT_IMPLEMENTED = "", $NICE_FORMATTING = TRUE)
	{
		$this->FORMAT_XML = $NICE_FORMATTING;
	}
	function setXslBinding($URL)
	{
		$this->XSL_BINDING = $URL;
	}
	function writeXmlCheckSum()
	{
		$this->XML_CHECKSUM = true;
	}
	function addXmlObject($XML_LOCATION, $UNIQUE_IDENTIFIER = 0, $XML_VALUE, $STD_STEP = null, $STEP_ID = null)
	{
		$xml_array = array();
		$alt_step = -1;
		$steps = 0;
		
		if($STD_STEP == null)
			$STD_STEP = 2;
		if($STEP_ID == null)
			$STEP_ID = $UNIQUE_IDENTIFIER;

		if(array_search("$UNIQUE_IDENTIFIER,$XML_LOCATION", $this->XML_STRING_PATHS) !== FALSE)
			$alt_step = 2;
		else
			array_push($this->XML_STRING_PATHS, "$UNIQUE_IDENTIFIER,$XML_LOCATION");

		$xml_steps = explode('/', $XML_LOCATION);
		foreach(array_reverse($xml_steps) as $current_tag)
		{
			$steps++;

			if(empty($xml_array))
			{
				$xml_array = $XML_VALUE;
			}
			if(!empty($current_tag))
				$xml_array = array("$current_tag" => $xml_array);

			if($steps == $STD_STEP)
				$xml_array = array("id_$UNIQUE_IDENTIFIER" => $xml_array);
			if($steps == $alt_step)
				$xml_array = array("id_$STEP_ID" => $xml_array);
		}

		$this->XML_OBJECTS = array_merge_recursive($this->XML_OBJECTS, $xml_array);
	}
	function addStatement($STATEMENT_NAME, $STATEMENT_VALUE)
	{
		array_push($this->XML_STATEMENTS, trim($STATEMENT_NAME . ": " . $STATEMENT_VALUE));
	}
	function getXMLStatements()
	{
		$return_string = "";
		$statements_to_print = array_reverse($this->XML_STATEMENTS);

		foreach($statements_to_print as $statement)
		{
			$return_string .= "<!-- $statement -->" . "\n";
		}

		return $return_string;
	}
	function getXML()
	{
		$formatted_xml = $this->getXMLBelow($this->XML_OBJECTS, 0);

		$this->addStatement("Generated", date("Y-m-d H:i:s"));

		if($this->XML_CHECKSUM)
			$this->addStatement("Checksum", md5($formatted_xml));

		return "<?xml version=\"1.0\"?>\n" . $this->getXSL() . $this->getXMLStatements() . $formatted_xml;
	}
	function getXSL()
	{
		if($this->XSL_BINDING != null)
		{
			return "<?xml-stylesheet type=\"text/xsl\" href=\"" . $this->XSL_BINDING . "\" ?>\n";
		}
	}
	function getJustXML()
	{
		return $this->getXMLBelow($this->XML_OBJECTS, 0);
	}
	function getXMLBelow($XML_ARRAY, $TIMES_DEEP)
	{
		$formatted_xml = "";

		foreach($XML_ARRAY as $key => $value)
		{
			if(!is_array($value))
			{
				$formatted_xml .= $this->getXMLTabs($TIMES_DEEP) . "<$key>$value</$key>" . $this->getXMLBreaks();
			}
			else
			{
				if(substr($key, 0, 3) === "id_")
				{
					$formatted_xml .= $this->getXMLBelow($value, $TIMES_DEEP);
				}
				else
				{
					$formatted_xml .= $this->getXMLTabs($TIMES_DEEP) . "<$key>" . $this->getXMLBreaks();
					$formatted_xml .= $this->getXMLBelow($value, $TIMES_DEEP + 1);
					$formatted_xml .= $this->getXMLTabs($TIMES_DEEP) . "</$key>" . $this->getXMLBreaks();
				}
			}
		}

		return $formatted_xml;
	}
	function getXMLTabs($TIMES_DEEP)
	{
		if($this->FORMAT_XML !== TRUE)
			return;

		$return_format = "";

		for($i = 0; $i < $TIMES_DEEP; $i++)
			$return_format .= "\t";

		return $return_format;
	}
	function getXMLBreaks()
	{
		if($this->FORMAT_XML !== TRUE)
			return;

		return "\n";
	}
	function debugDumpArray()
	{
		return $this->XML_OBJECTS;
	}
}
?>
