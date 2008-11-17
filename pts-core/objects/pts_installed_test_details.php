<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel

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

class pts_installed_test_details
{
	var $identifier;
	var $name;

	public function __construct($identifier)
	{
		$xml_parser = new pts_test_tandem_XmlReader(pts_location_test($identifier));
		$this->identifier = $identifier;
		$this->name = $xml_parser->getXMLValue(P_TEST_TITLE);
	}
	public function __toString()
	{
		$str = "";

		if(!empty($this->name))
		{
			$str = sprintf("%-18ls - %-30ls\n", $this->identifier, $this->name);
		}

		return $str;
	}
}

?>
