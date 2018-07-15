<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2016, Phoronix Media
	Copyright (C) 2010 - 2016, Michael Larabel
	nye_XmlReader.php: The XML reading object for the Phoronix Test Suite succeeding tandem_XmlReader

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

// TODO XXX possibly simply a lot of this with xml_parse_into_struct

class nye_XmlReader
{
	protected $tag_fallback = false; // Fallback value if tag is not present
	protected $file_location = false;
	public $dom; // The DOM
	protected $times_fallback = 0;

	public function __construct($xml_file)
	{
		libxml_use_internal_errors(true);
		$this->dom = new DOMDocument();

		// TODO: investigate whether using the LIBXML_COMPACT option on loading actually increases performance
		if(!is_object($xml_file) && !isset($xml_file[1024]) && is_file($xml_file))
		{
			$this->dom->load($xml_file);
			$this->file_location = $xml_file;
		}
		else if($xml_file != null)
		{
			$this->dom->loadXML($xml_file);
		}
	}
	public function getFileLocation()
	{
		return $this->file_location;
	}
	public function getXMLValue($xml_tag, $fallback_value = -1)
	{
		$steps = explode('/', $xml_tag);
		$narrow = $this->dom->getElementsByTagName(array_shift($steps));

		foreach($steps as $step)
		{
			if($narrow->length == 0)
			{
				break;
			}

			$narrow = $narrow->item(0)->getElementsByTagName($step);
		}

		return $narrow->length == 1 ? $narrow->item(0)->nodeValue : $this->handleXmlZeroTagFallback($xml_tag, ($fallback_value === -1 ? $this->tag_fallback : $fallback_value));
	}
	public function getXMLArrayValues($xml_tag, $break_depth = -1)
	{
		$steps = explode('/', $xml_tag);
		$narrow = $this->dom->getElementsByTagName(array_shift($steps));
		$values = $this->processXMLArraySteps($steps, $narrow, 0, $break_depth);

		return isset($values[0]) ? $values : $this->handleXmlZeroTagArrayFallback($xml_tag, $values, $break_depth);
	}
	protected function processXMLArraySteps($steps, $narrow, $steps_offset = 0, $break_depth = -1)
	{
		$values = array();

		for($i = $steps_offset, $c = count($steps); $i < $c && $narrow->length > 0; $i++)
		{
			$narrow = $narrow->item(0)->getElementsByTagName($steps[$i]);

			if($i == $break_depth)
			{
				for($x = 0; $x < $break_depth || $x == 0; $x++)
				{
					for($j = 0; $j < $narrow->length; $j++)
					{
						$values[] = $this->processXMLArraySteps($steps, $narrow->item($j)->getElementsByTagName($steps[$i + 1]), $i + 2);
					}
				}
				break;
			}
			else if($i == ($c - 2))
			{
				for($j = 0; $j < $narrow->length; $j++)
				{
					$extract = $narrow->item($j)->getElementsByTagName($steps[$i + 1]);
					$values[] = ($extract->length > 0 ? $extract->item(0)->nodeValue : null);
				}
				break;
			}
		}

		return $values;
	}
	public function times_fallback()
	{
		return $this->times_fallback;
	}
	protected function handleXmlZeroTagFallback($xml_tag, $fallback_value)
	{
		if($fallback_value != null)
			$this->times_fallback++;

		return $fallback_value;
	}
	protected function handleXmlZeroTagArrayFallback($xml_tag, $fallback_value, $break_depth = -1)
	{
		if($fallback_value != null)
			$this->times_fallback++;
		return $fallback_value;
	}
	public function getXML()
	{
		return $this->dom->saveXML();
	}
}

?>
