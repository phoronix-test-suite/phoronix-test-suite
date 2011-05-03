<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010, Phoronix Media
	Copyright (C) 2010, Michael Larabel

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

class pts_graph_config_nye_XmlReader extends nye_XmlReader
{
	protected $override_values;

	public function __construct($new_values = null)
	{
		$file = null;

		if(PTS_IS_CLIENT)
		{
			if(is_file(PTS_USER_PATH . 'graph-config.xml'))
			{
				$file = PTS_USER_PATH . 'graph-config.xml';
			}
			else if(is_file(PTS_RESULTS_VIEWER_PATH . 'graph-config-template.xml'))
			{
				$file = PTS_RESULTS_VIEWER_PATH . 'graph-config-template.xml';
			}
		}
		else if(defined('PTS_LIB_GRAPH_CONFIG_XML') && is_file(PTS_LIB_GRAPH_CONFIG_XML))
		{
			$file = PTS_LIB_GRAPH_CONFIG_XML;
		}

		$this->override_values = (is_array($new_values) ? $new_values : false);

		parent::__construct($file);
	}
	public function getXMLValue($xml_tag, $fallback_value = false)
	{
		if($this->override_values != false && isset($this->override_values[$xml_tag]))
		{
			$value = $this->override_values[$xml_tag];
		}
		else
		{
			$value = parent::getXMLValue($xml_tag, $fallback_value);
		}

		return $value;
	}
	public function handleXmlZeroTagFallback($xml_tag, $fallback_value)
	{
		static $fallback_reader = null;
		$fallback_value = false;

		if($fallback_reader != null || is_file(PTS_CORE_STATIC_PATH . 'graph-config-defaults.xml'))
		{
			if($fallback_reader == null)
			{
				$fallback_reader = new nye_XmlReader(PTS_CORE_STATIC_PATH . 'graph-config-defaults.xml');
			}

			$fallback_value = $fallback_reader->getXMLValue($xml_tag, $fallback_value);
		}
		else if(PTS_IS_CLIENT)
		{
			echo "\nUndefined Graph Config Option: $xml_tag\n";
		}

		return $fallback_value;
	}
}
?>
