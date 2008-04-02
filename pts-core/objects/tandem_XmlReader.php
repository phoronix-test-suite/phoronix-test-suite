<?php

/*
	tandem_XmlReader: A very simple XML parser with a few extras... Does not support attributes on tags, etc.
	A work in progress. Was designed for just some select needs in the past. No XML validation is done with this parser, etc.

	Copyright Michael Larabel (C) 2004 - 2008
*/

class tandem_XmlReader
{
	var $XML_DATA;

	function __construct($XML)
	{
		$this->XML_DATA = $XML;
	}
	function getStatement($STATEMENT_NAME)
	{
		return $this->listStatements(TRUE, $STATEMENT_NAME);
	}
	function listStatements($SEARCH_DO = FALSE, $SEARCH_QUERY = "")
	{
		// TODO: add check to make sure it doesn't occur in XML, but before XML start after XML declaration

		$XML_COPY = $this->XML_DATA;
		preg_match_all("'<!--(.*?) -->'si", $XML_COPY, $STATEMENT_MATCHES);

		$RETURN_ARRAY = array();

		foreach($STATEMENT_MATCHES[0] as $statement)
		{
			$name = substr($statement, 0, strpos($statement, ':'));
			$name = trim(strstr($name, ' '));

			if($SEARCH_DO)
			{
				if($name == $SEARCH_QUERY)
				{
					$value = strstr($statement, ':');
					$value = trim(substr($value, 1, strpos($value, "-->") - 1));

					array_push($RETURN_ARRAY, $value);
				}
			}
			else
			{
				array_push($RETURN_ARRAY, $name);
			}
		}
		return $RETURN_ARRAY;
	}
	function getXMLValue($XML_TAG)
	{
		return $this->getValue($XML_TAG, $this->XML_DATA);
	}
	function getValue($XML_TAG, $XML_MATCH)
	{
		$XML_LOCATION_OFFSET = 0;

		do
		{
			$LOCATION_NEXT_DEPTH = strpos($XML_TAG, '/', $XML_LOCATION_OFFSET);

			if($LOCATION_NEXT_DEPTH === FALSE)
				$LOCATION_NEXT_DEPTH = strlen($XML_TAG);

			$THIS_XML_TAG = substr($XML_TAG, $XML_LOCATION_OFFSET, $LOCATION_NEXT_DEPTH - $XML_LOCATION_OFFSET);
			preg_match("'<$THIS_XML_TAG>(.*?)</$THIS_XML_TAG>'si", $XML_MATCH, $NEW_MATCH);

			if(sizeof($NEW_MATCH) > 1)
				$XML_MATCH = $NEW_MATCH[1];
			else
				$XML_MATCH = null;

			$XML_LOCATION_OFFSET = $LOCATION_NEXT_DEPTH + 1;
		}
		while($XML_LOCATION_OFFSET < strlen($XML_TAG));

		return $XML_MATCH;
	}
	function getXMLArrayValues($XML_TAG)
	{
		return $this->getArrayValues($XML_TAG, $this->XML_DATA);
	}
	function getArrayValues($XML_TAG, $XML_MATCH)
	{
		$XML_ARRAY_VALUES = $this->LocationHierarchyToArray($XML_TAG);
		$THIS_XML_CONTENTS = $XML_MATCH;

		for($i = 0; $i < sizeof($XML_ARRAY_VALUES) - 2; $i++)
			$THIS_XML_CONTENTS = $this->getValue($XML_ARRAY_VALUES[$i], $THIS_XML_CONTENTS);

		$LAST_XML_VALUE = $XML_ARRAY_VALUES[sizeof($XML_ARRAY_VALUES) - 2];

		preg_match_all("'<$LAST_XML_VALUE>(.*?)</$LAST_XML_VALUE>'si", $THIS_XML_CONTENTS, $XML_MATCHES);

		$RETURN_ARRAY = array();

		for($i = 0; $i < sizeof($XML_MATCHES[1]); $i++)
		{
			$THIS_ITEM = $this->getValue(end($XML_ARRAY_VALUES), $XML_MATCHES[1][$i]);

		//	if(!empty($THIS_ITEM))
				array_push($RETURN_ARRAY, $THIS_ITEM);
		}

		return $RETURN_ARRAY;
	}
	function LocationHierarchyToArray($LOCATION)
	{
		$RETURN_ARRAY = array();
		$TEMP_LOCATION = $LOCATION;

		while(($POS_NEXT_DELIMITER = strpos($TEMP_LOCATION, '/')) !== FALSE)
		{
			array_push($RETURN_ARRAY, substr($TEMP_LOCATION, 0, $POS_NEXT_DELIMITER));
			$TEMP_LOCATION = substr($TEMP_LOCATION, $POS_NEXT_DELIMITER + 1);
		}

		if(!empty($TEMP_LOCATION))
			array_push($RETURN_ARRAY, $TEMP_LOCATION);

		return $RETURN_ARRAY;
	}
}
?>
