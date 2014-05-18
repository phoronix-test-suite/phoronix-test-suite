<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2012, Phoronix Media
	Copyright (C) 2010 - 2012, Michael Larabel

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

class pts_suite_nye_XmlReader extends nye_XmlReader
{
	static $temp_suite = null;

	public function __construct($read_xml)
	{
		if(!isset($xml_file[512]) && defined('PTS_TEST_SUITE_PATH') && is_file(PTS_TEST_SUITE_PATH . $read_xml . '/suite-definition.xml'))
		{
			$read_xml = PTS_TEST_SUITE_PATH . $read_xml . '/suite-definition.xml';
		}
		else if(substr($read_xml, -4) == '.zip' && is_file($read_xml))
		{
			$zip = new ZipArchive();

			if($zip->open($read_xml) === true)
			{
				$read_xml = $zip->getFromName('suite-definition.xml');
				$zip->close();
			}
		}
		else if(isset(self::$temp_suite[$name]))
		{
			$read_xml = self::$temp_suite[$name];
		}

		parent::__construct($read_xml);
	}
	public function validate()
	{
		return $this->dom->schemaValidate(PTS_OPENBENCHMARKING_PATH . 'schemas/test-suite.xsd');
	}
	public static function set_temporary_suite($name, $suite_xml)
	{
		self::$temp_suite[$name] = $suite_xml;
	}
}
?>
