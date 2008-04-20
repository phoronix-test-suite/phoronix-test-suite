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
		preg_match_all("'<!--(.*?) -->'si", $this->XML_DATA, $statement_maches);
		$return_array = array();

		foreach($statement_maches[0] as $statement)
		{
			$name = substr($statement, 0, strpos($statement, ':'));
			$name = trim(strstr($name, ' '));

			if($SEARCH_DO)
			{
				if($name == $SEARCH_QUERY)
				{
					$value = strstr($statement, ':');
					$value = trim(substr($value, 1, strpos($value, "-->") - 1));

					array_push($return_array, $value);
				}
			}
			else
			{
				array_push($return_array, $name);
			}
		}
		return $return_array;
	}
	function getXMLValue($XML_TAG)
	{
		return $this->getValue($XML_TAG, $this->XML_DATA);
	}
	function getValue($XML_TAG, $XML_MATCH)
	{
		foreach(explode('/', $XML_TAG) as $xml_step)
		{
			preg_match("'<$xml_step>(.*?)</$xml_step>'si", $XML_MATCH, $new_match);

			if(count($new_match) > 1)
				$XML_MATCH = $new_match[1];
			else
				$XML_MATCH = null;
		}

		return $XML_MATCH;
	}
	function getXMLValues($XML_TAG)
	{
		return $this->getXMLArrayValues($XML_TAG);
	}
	function getXMLArrayValues($XML_TAG)
	{
		return $this->getArrayValues($XML_TAG, $this->XML_DATA);
	}
	function getArrayValues($XML_TAG, $XML_MATCH)
	{
		$xml_steps = explode('/', $XML_TAG);
		$this_xml = $XML_MATCH;

		for($i = 0; $i < count($xml_steps) - 2; $i++)
			$this_xml = $this->getValue($xml_steps[$i], $this_xml);

		$next_xml_step = $xml_steps[count($xml_steps) - 2];
		preg_match_all("'<$next_xml_step>(.*?)</$next_xml_step>'si", $this_xml, $xml_matches);

		$return_array = array();
		$extraction_tags = explode(',', end($xml_steps));
		$extraction_tags_count = count($extraction_tags);

		for($i = 0; $i < count($xml_matches[1]); $i++)
		{
			if($extraction_tags_count == 1)
			{
				$this_item = $this->getValue($extraction_tags[0], $xml_matches[1][$i]);
				array_push($return_array, $this_item);
			}
			else
			{
				if($i == 0)
				{
					foreach($extraction_tags as $extract)
						$return_array[$extract] = array();
				}
				foreach($extraction_tags as $extract)
				{
					$this_item = $this->getValue($extract, $xml_matches[1][$i]);
					array_push($return_array[$extract], $this_item);
				}
			}
		}

		return $return_array;
	}
}
?>
