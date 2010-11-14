<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010, Phoronix Media
	Copyright (C) 2010, Michael Larabel
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

class nye_XmlReader
{
	protected $tag_fallback = false; // Fallback value if tag is not present
	protected $dom; // The DOM

	public function __construct($xml_file)
	{
		$this->dom = new DOMDocument();

		if(is_file($xml_file))
		{
			$this->dom->load($xml_file);
		}
		else
		{
			$this->dom->loadXML($xml_file);
		}
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

		for($i = $steps_offset, $c = count($steps); $i < $c; $i++)
		{
			if($narrow->length == 0)
			{
				break;
			}

			$narrow = $narrow->item(0)->getElementsByTagName($steps[$i]);

			if($i == $break_depth)
			{
				for($j = 0; $j < $narrow->length; $j++)
				{
					array_push($values, $this->processXMLArraySteps($steps, $narrow->item($j)->getElementsByTagName($steps[$i + 1]), $i + 2));
				}
				break;
			}
			else if($i == ($c - 2))
			{
				for($j = 0; $j < $narrow->length; $j++)
				{
					$extract = $narrow->item($j)->getElementsByTagName($steps[$i + 1]);

					if($extract->length > 0)
					{
						array_push($values, $extract->item(0)->nodeValue);
					}
					else
					{
						array_push($values, null);
					}
				}
				break;
			}
		}

		return $values;
	}
	protected function handleXmlZeroTagFallback($xml_tag, $fallback_value)
	{
		return $fallback_value;
	}
	protected function handleXmlZeroTagArrayFallback($xml_tag, $fallback_value, $break_depth = -1)
	{
		return $fallback_value;
	}
}

?>
