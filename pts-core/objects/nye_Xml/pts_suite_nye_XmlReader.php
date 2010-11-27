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

pts_load_xml_definitions("test-suite.xml");

class pts_suite_nye_XmlReader extends nye_XmlReader
{
	public function __construct($read_xml)
	{
		if(is_file(PTS_TEST_SUITE_PATH . $read_xml . ".xml"))
		{
			$read_xml = PTS_TEST_SUITE_PATH . $read_xml . ".xml";
		}

		parent::__construct($read_xml);
	}
	public function validate()
	{
		return $this->dom->validateSchema(PTS_CORE_PATH . "definitions/test-suite.xsd");
	}
}
?>
