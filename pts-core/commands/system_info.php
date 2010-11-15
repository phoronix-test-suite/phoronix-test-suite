<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel

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

class system_info implements pts_option_interface
{
	public static function run($r)
	{
		pts_client::$display->generic_heading("System Information");
		echo "Hardware:\n" . phodevi::system_hardware(true) . "\n\n";
		echo "Software:\n" . phodevi::system_software(true) . "\n\n";

		$writer = new nye_XmlWriter(1111);
pts_load_xml_definitions("test-suite.xml");
		$writer->addXmlObject("PhoronixTestSuite/SuiteInformation/Title", "Testing");
		$writer->addXmlObject("PhoronixTestSuite/SuiteInformation/Version", "2e2g");


		$writer->addXmlObject(P_SUITE_TEST_NAME, "EEEE");
		$writer->addXmlObject(P_SUITE_TEST_ARGUMENTS, "FFFFF");
		$writer->addXmlObject(P_SUITE_TEST_DESCRIPTION, "GGGGGG");

		$writer->addXmlObject(P_SUITE_TEST_NAME, "JJJJJJ");
		$writer->addXmlObject(P_SUITE_TEST_ARGUMENTS, "KKKKKK");
		$writer->addXmlObject(P_SUITE_TEST_DESCRIPTION, "LLLLL");

		$writer->addXmlObject(P_SUITE_MAINTAINER, "XXXXX");


		echo $writer->getXML();
	}
}

?>
