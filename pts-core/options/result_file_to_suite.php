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

class result_file_to_suite implements pts_option_interface
{
	public static function run($r)
	{
		pts_load_function_set("run");
		echo pts_string_header("Test Suite Creation Utility");

		$result_file = false;
		if(count($r) != 0)
		{
			$result_file = $r[0];
		}

		while(($result_file = pts_find_result_file($result_file)) == false)
		{
			$result_file = pts_text_input("Enter name of result file");
		}

		$suite_name = pts_text_input("Enter name of suite");
		$suite_test_type = pts_text_select_menu("Select test type", pts_subsystem_test_types());
		$suite_maintainer = pts_text_input("Enter suite maintainer name");
		$suite_description = pts_text_input("Enter suite description");

		$xml_writer = new tandem_XmlWriter();
		$xml_writer->addXmlObject(P_SUITE_TITLE, 0, $suite_name);
		$xml_writer->addXmlObject(P_SUITE_VERSION, 0, "1.0.0");
		$xml_writer->addXmlObject(P_SUITE_MAINTAINER, 0, $suite_maintainer);
		$xml_writer->addXmlObject(P_SUITE_TYPE, 0, $suite_test_type);
		$xml_writer->addXmlObject(P_SUITE_DESCRIPTION, 0, $suite_description);
		$write_position = 1;

		// Read results file
		$xml_parser = new pts_results_tandem_XmlReader($result_file);
		$tests = $xml_parser->getXMLArrayValues(P_RESULTS_TEST_TESTNAME);
		$arguments = $xml_parser->getXMLArrayValues(P_RESULTS_TEST_ARGUMENTS);
		$attributes = $xml_parser->getXMLArrayValues(P_RESULTS_TEST_ATTRIBUTES);

		for($i = 0; $i < count($tests); $i++)
		{
			$xml_writer->addXmlObject(P_SUITE_TEST_NAME, $write_position, $tests[$i]);

			if(!empty($arguments[$i]) && !empty($attributes[$i]))
			{
				$xml_writer->addXmlObject(P_SUITE_TEST_ARGUMENTS, $write_position, $arguments[$i]);
				$xml_writer->addXmlObject(P_SUITE_TEST_DESCRIPTION, $write_position, $attributes[$i]);
			}
			$write_position++;
		}

		// Finish it off
		$suite_identifier = pts_input_string_to_identifier(str_replace(" ", "-", strtolower($suite_name)));

		if(is_file(XML_SUITE_LOCAL_DIR . $suite_identifier . ".xml"))
		{
			$suite_append = 1;
			do
			{
				$suite_append++;
			}
			while(is_file(XML_SUITE_LOCAL_DIR . $suite_identifier . "-" . $suite_append . ".xml"));
			$suite_identifier .= "-" . $suite_append;
		}
		$save_to = XML_SUITE_LOCAL_DIR . $suite_identifier . ".xml";

		$fp = file_put_contents($save_to, $xml_writer->getXML());

		if($fp != false)
		{
			echo "\n\nSaved To: " . $save_to . "\nTo run this suite, type: phoronix-test-suite benchmark " . $suite_identifier . "\n\n";
		}
	}
}

?>
