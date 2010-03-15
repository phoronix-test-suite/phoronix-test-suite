<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
	pts_graph_config_tandem_XmlReader.php: The XML reading object for the Phoronix Test Suite for the graph config

	Additional Notes: A very simple XML parser with a few extras... Does not currently support attributes on tags, etc.
	A work in progress. This was originally designed for just some select needs in the past. No XML validation is done with this parser, etc.

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

class pts_graph_config_tandem_XmlReader extends tandem_XmlReader
{
	protected $override_values;

	public function __construct($new_values = null)
	{
		pts_loader::load_definitions("graph-config.xml");

		$file = null;

		if(PTS_MODE == "CLIENT")
		{
			if(is_file(PTS_USER_DIR . "graph-config.xml"))
			{
				$file = file_get_contents(PTS_USER_DIR . "graph-config.xml");
			}
			else if(is_file(RESULTS_VIEWER_DIR . "graph-config-template.xml"))
			{
				$file = file_get_contents(RESULTS_VIEWER_DIR . "graph-config-template.xml");
			}
		}
		else if(defined("PTS_LIB_GRAPH_CONFIG_XML") && is_file(PTS_LIB_GRAPH_CONFIG_XML))
		{
			$file = PTS_LIB_GRAPH_CONFIG_XML;
		}

		$this->override_values = (is_array($new_values) ? $new_values : false);

		parent::__construct($file);
	}
	function getValue($xml_path, $xml_tag = null, $xml_match = null, $is_fallback_call = false)
	{
		if($this->override_values != false && isset($this->override_values[$xml_path]))
		{
			return $this->override_values[$xml_path];
		}
		else
		{
			return parent::getValue($xml_path, $xml_tag, $xml_match, $is_fallback_call);
		}
	}
	function handleXmlZeroTagFallback($xml_tag)
	{
		static $fallback_reader = null;
		$fallback_value = false;

		if($fallback_reader != null || is_file(STATIC_DIR . "graph-config-defaults.xml"))
		{
			if($fallback_reader == null)
			{
				$fallback_reader = new tandem_XmlReader(STATIC_DIR . "graph-config-defaults.xml");
			}

			$fallback_value = $fallback_reader->getXMLValue($xml_tag);
		}
		else if(PTS_MODE == "CLIENT")
		{
			echo "\nUndefined Graph Config Option: $xml_tag\n";
		}

		return $fallback_value;
	}
}
?>
