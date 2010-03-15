<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2004 - 2009, Michael Larabel
	tandem_XmlReader.php: The XML reading object for the Phoronix Test Suite.

	Additional Notes: A very simple XML parser with a few extras... Does not currently support attributes on tags, etc.

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

class tandem_XmlReader
{
	protected $xml_data = null; // XML contents
	protected $tag_fallback_value = null; // Fallback value if tag is not present

	function __construct($read_xml)
	{
		if(substr(trim($read_xml), 0, 1) != '<' && (is_readable($read_xml) || (strpos($read_xml, "://") !== false && strpos($read_xml, "://") < strpos($read_xml, "\n"))))
		{
			$read_xml = file_get_contents($read_xml);
		}

		//$read_xml = str_replace(array("\n", "\t"), null, $read_xml);

		$this->xml_data = $read_xml;
	}
	function getStatement($statement)
	{
		return $this->listStatements(true, $statement);
	}
	function listStatements($perform_search = false, $search_query = "")
	{
		$return_r = array();
		$comment_statements = $this->parseLineCommentsFromFile();

		foreach($comment_statements as $statement)
		{
			$name = substr($statement, 0, strpos($statement, ":"));
			$name = trim(strstr($name, " "));

			if($perform_search && !empty($search_query))
			{
				if($name == $search_query)
				{
					$value = trim(substr(strstr($statement, ":"), 1));
					array_push($return_r, $value);
				}
			}
			else
			{
				array_push($return_r, $name);
			}
		}

		return $return_r;
	}
	function getXMLValue($xml_tag, $fallback_value = false)
	{
		return ($v = $this->getValue($xml_tag)) != false ? $v : $fallback_value;
	}
	function isDefined($xml_tag)
	{
		return $this->getValue($xml_tag) != null;
	}
	function getValue($xml_path, $xml_tag = null, $xml_match = null, $is_fallback_call = false)
	{
		if($xml_tag == null)
		{
			$xml_tag = $xml_path;
		}
		if($xml_match == null)
		{
			$xml_match = $this->xml_data;
		}

		foreach(explode('/', $xml_tag) as $xml_step)
		{
			$xml_match = $this->parseXMLString($xml_step, $xml_match, false);

			if($xml_match === false)
			{
				$xml_match = !$is_fallback_call ? $this->handleXmlZeroTagFallback($xml_path) : $this->tag_fallback_value;
			}
		}

		return $xml_match;
	}
	function parseXMLString($xml_tag, $to_parse, $multi_search = true)
	{
		$return = false;

		$open_tag = "<" . $xml_tag . ">";
		$close_tag  = "</" . $xml_tag . ">";
		$open_tag_length = strlen($open_tag);
		$close_tag_length = strlen($close_tag);

		if($multi_search)
		{
			$temp = $to_parse;
			$return = array();

			do
			{
				$found = false;

				if(($start = strpos($temp, $open_tag)) !== false)
				{
					$temp = substr($temp, $start + $open_tag_length);

					if(($end = strpos($temp, $close_tag)) !== false)
					{
						$contents = substr($temp, 0, $end);
						$temp = substr($temp, strlen($contents) + $close_tag_length);
						array_push($return, $contents);
						$found = true;
					}
				}
			}
			while($found);

			if(!isset($return[0]))
			{
				$return = false;
			}
		}
		else
		{
			if(($start = strpos($to_parse, $open_tag)) !== false)
			{
				$to_parse = substr($to_parse, $start + $open_tag_length);

				if(($end = strpos($to_parse, $close_tag)) !== false)
				{
					$return = substr($to_parse, 0, $end);
				}
			}
		}

		return $return;
	}
	function parseLineCommentsFromFile($read_from = null)
	{
		if(empty($read_from))
		{
			$read_from = $this->xml_data;
		}

		$return_r = array();
		$temp = $read_from;

		do
		{
			$return = null;

			if(($start = strpos($temp, "<!--")) !== false)
			{
				$temp = substr($temp, $start + 4);

				if(($end = strpos($temp, "-->")) !== false)
				{
					$return = substr($temp, 0, $end);
					$temp = substr($temp, strlen($return) + 3);
				}
			}

			if($return != null)
			{
				array_push($return_r, $return);
			}
		}
		while($return != null);

		return $return_r;
	}
	function handleXmlZeroTagFallback($xml_tag)
	{
		return $this->tag_fallback_value;
	}
	function getXMLValues($xml_tag)
	{
		return $this->getXMLArrayValues($xml_tag);
	}
	function getXMLArrayValues($xml_tag)
	{
		return $this->getArrayValues($xml_tag, $this->xml_data);
	}
	function getArrayValues($xml_tag, $xml_match)
	{
		$return_r = array();
		$xml_steps = explode("/", $xml_tag);
		$xml_steps_count = count($xml_steps);
		$this_xml = $xml_match;

		for($i = 0; $i < ($xml_steps_count - 2); $i++)
		{
			$this_xml = $this->getValue($xml_tag, $xml_steps[$i], $this_xml);
		}

		$xml_matches = $this->parseXMLString($xml_steps[($xml_steps_count - 2)], $this_xml);
		$end_tag = end($xml_steps);

		if($xml_matches != false)
		{
			foreach($xml_matches as $match)
			{
				$this_item = $this->getValue($xml_tag, $end_tag, $match);

				//if($this_item != false)
				//{
					array_push($return_r, $this_item);
				//}
			}
		}

		return $return_r;
	}
}
?>
