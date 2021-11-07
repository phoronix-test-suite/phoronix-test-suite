<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2016, Phoronix Media
	Copyright (C) 2010 - 2016, Michael Larabel
	nye_XmlWriter.php: The XML writing object for the Phoronix Test Suite succeeding tandem_XmlWriter

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

class nye_XmlWriter
{
	protected $items;
	public $dom;
	protected $times_fallback = 0;

	public function __construct($xsl_binding = null, $force_nice_formatting = false)
	{
		$this->dom = new DOMDocument('1.0');
		//$this->dom->formatOutput = (PTS_IS_CLIENT && !defined('PHOROMATIC_DB_INIT') && !defined('PAGE_LOAD_START_TIME')) || $force_nice_formatting;
		$this->dom->formatOutput = !defined('OPENBENCHMARKING_BUILD') || $force_nice_formatting;
		//$this->dom->preserveWhiteSpace = false;
		$this->items = array();

		if($this->dom->formatOutput)
		{
			$pts_comment = $this->dom->createComment(pts_core::program_title());
			$this->dom->appendChild($pts_comment);
		}

		if($xsl_binding != null)
		{
			$xslt = $this->dom->createProcessingInstruction('xml-stylesheet', 'type="text/xsl" href="' . $xsl_binding . '"');
			$this->dom->appendChild($xslt);
		}
	}
	public function addXmlNodeWNE($xml_location, $xml_value = null)
	{
		// When Not Empty, add the XML node
		return $xml_value === null || $xml_value === false ? false : $this->addXmlNode($xml_location, $xml_value);
	}
	public function addXmlNode($xml_location, $xml_value = null)
	{
		$nodes = explode('/', $xml_location);
		$pointer = &$this->items;

		for($i = 0, $node_count = count($nodes); $i < $node_count; $i++)
		{
			if(!isset($pointer[$nodes[$i]]) || ($i == ($node_count - 2) && isset($pointer[$nodes[$i]][$nodes[($i + 1)]])))
			{
				$pointer[$nodes[$i]] = array();
				$pointer[$nodes[$i]][0] = $this->dom->createElement($nodes[$i]);

				if($i == 0)
				{
					$this->dom->appendChild($pointer[$nodes[$i]][0]);
				}
				else
				{
					$pointer[0]->appendChild($pointer[$nodes[$i]][0]);

					if($i == ($node_count - 1))
					{
						if($xml_value === null)
						{
							// Otherwise null throws PHP deprecation error with PHP 8.1+
							$xml_value = '';
						}
						$t = $this->dom->createTextNode($xml_value);
						$pointer[$nodes[$i]][0]->appendChild($t);
					}
				}
			}

			$pointer = &$pointer[$nodes[$i]];
		}
	}
	public function addXmlNodeFromReader($xml_location, &$xml, $default_value = null)
	{
		$value = $xml->getXmlValue($xml_location);

		if(empty($value))
		{
			$value = $default_value;

			if($default_value != null)
				$this->times_fallback++;
		}

		$this->addXmlNode($xml_location, $value);
	}
	public function times_fallback()
	{
		return $this->times_fallback;
	}
	public function saveXMLFile($to_file)
	{
		return $this->dom->save($to_file);
	}
	public function getXML()
	{
		return $this->dom->saveXML();
	}
}

?>
