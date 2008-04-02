<?php

/*
	tandem_XmlWriter: A very simple XML writerr with a few extras... Does not support attributes on tags, etc.
	A work in progress. Was designed for just some select needs in the past. Does support linking to an XSL as 
	well as whether to format the XML or not, etc. Also provides a MD5 checksum of the XML body.

	Copyright Michael Larabel (C) 2004 - 2008
*/

class tandem_XmlWriter
{
	var $XML_OBJECTS = array();
	var $XML_STRING_PATHS = array();
	var $XML_STATEMENTS = array();
	var $FORMAT_XML;
	var $XSL_BINDING = null;

	function __construct($READ_FROM_XML = "", $ENCRYPTION_ALGORITHM = "", $NICE_FORMATTING = TRUE)
	{
		// TODOs
		// $READ_FROM_XML: Convert existing XML into array, to be appended to
		// $ENCRYPTION_ALGORITHM: The encryption algorithm to eval() on XML_OBJECTS

		$this->FORMAT_XML = $NICE_FORMATTING;
	}
	function setXslBinding($URL)
	{
		$this->XSL_BINDING = $URL;
	}
	function addXmlObject($XML_LOCATION, $UNIQUE_IDENTIFIER = 0, $XML_VALUE, $STD_STEP = null, $STEP_ID = null)
	{
		$XML_ARRAY = array();
		$ALT_STEP = -1;
		$steps = 0;
		
		if($STD_STEP == null)
			$STD_STEP = 2;
		if($STEP_ID == null)
			$STEP_ID = $UNIQUE_IDENTIFIER;

		if(array_search("$UNIQUE_IDENTIFIER,$XML_LOCATION", $this->XML_STRING_PATHS) !== FALSE)
			$ALT_STEP = 2;
		else
			array_push($this->XML_STRING_PATHS, "$UNIQUE_IDENTIFIER,$XML_LOCATION");

		do
		{
			$steps++;
			$NEXT_POS = strrpos($XML_LOCATION, '/');
			$CURRENT_TAG = substr($XML_LOCATION, ($NEXT_POS !== FALSE) ? $NEXT_POS + 1 : 0);
			$XML_LOCATION = substr($XML_LOCATION, 0, $NEXT_POS);

			if(empty($XML_ARRAY))
			{
				$XML_ARRAY = $XML_VALUE;
			}
		
			if(!empty($CURRENT_TAG))
				$XML_ARRAY = array("$CURRENT_TAG" => $XML_ARRAY);

			if($steps == $STD_STEP)
				$XML_ARRAY = array("id_$UNIQUE_IDENTIFIER" => $XML_ARRAY);
			if($steps == $ALT_STEP)
				$XML_ARRAY = array("id_$STEP_ID" => $XML_ARRAY);
		}
		while($NEXT_POS !== FALSE);

		$this->XML_OBJECTS = array_merge_recursive($this->XML_OBJECTS, $XML_ARRAY);
	}
	function addStatement($STATEMENT_NAME, $STATEMENT_VALUE)
	{
		array_push($this->XML_STATEMENTS, trim($STATEMENT_NAME . ": " . $STATEMENT_VALUE));
	}
	function getXMLStatements()
	{
		$RETURN_STRING = "";
		
		$PRINT_STATEMENTS = array_reverse($this->XML_STATEMENTS);

		foreach($PRINT_STATEMENTS as $statement)
		{
			$RETURN_STRING .= "<!-- $statement -->" . "\n";
		}

		return $RETURN_STRING;
	}
	function getXML()
	{
		$FORMATTED_XML = $this->getXMLBelow($this->XML_OBJECTS, 0);

		$this->addStatement("Generated", date("Y-m-d H:i:s"));
		$this->addStatement("Checksum", md5($FORMATTED_XML));

		return "<?xml version=\"1.0\"?>\n" . $this->getXSL() . $this->getXMLStatements() . $FORMATTED_XML;
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
		$FORMATTED_XML = "";

		foreach($XML_ARRAY as $KEY => $VALUE)
			if(!is_array($VALUE))
			{
				$FORMATTED_XML .= $this->getXMLTabs($TIMES_DEEP) . "<$KEY>$VALUE</$KEY>" . $this->getXMLBreaks();
			}
			else
			{
				if(substr($KEY, 0, 3) === "id_")
					$FORMATTED_XML .= $this->getXMLBelow($VALUE, $TIMES_DEEP);
				else
				{
					$FORMATTED_XML .= $this->getXMLTabs($TIMES_DEEP) . "<$KEY>" . $this->getXMLBreaks();
					$FORMATTED_XML .= $this->getXMLBelow($VALUE, $TIMES_DEEP + 1);
					$FORMATTED_XML .= $this->getXMLTabs($TIMES_DEEP) . "</$KEY>" . $this->getXMLBreaks();
				}

			}

		return $FORMATTED_XML;
	}
	function getXMLTabs($TIMES_DEEP)
	{
		if($this->FORMAT_XML !== TRUE)
			return;

		$RETURN_FORMAT = "";

		for($i = 0; $i < $TIMES_DEEP; $i++)
			$RETURN_FORMAT .= "\t";

		return $RETURN_FORMAT;
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
