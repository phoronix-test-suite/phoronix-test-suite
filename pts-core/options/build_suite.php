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

class build_suite implements pts_option_interface
{
	public static function required_function_sets()
	{
		return array("run");
	}
	public static function run($r)
	{
		echo pts_string_header("Test Suite Creation Utility");

		$suite_name = pts_text_input("Enter name of suite");
		$suite_test_type = pts_text_select_menu("Select test type", pts_subsystem_test_types());
		$suite_maintainer = pts_text_input("Enter suite maintainer name");
		$suite_description = pts_text_input("Enter suite description");

		$possible_suites = pts_available_suites_array();
		$possible_tests = array();
		foreach(pts_available_tests_array() as $identifier)
		{
			if(pts_test_supported($identifier))
			{
				array_push($possible_tests, $identifier);
			}
		}

		$xml_writer = new tandem_XmlWriter();
		$xml_writer->addXmlObject(P_SUITE_TITLE, 0, $suite_name);
		$xml_writer->addXmlObject(P_SUITE_VERSION, 0, "1.0.0");
		$xml_writer->addXmlObject(P_SUITE_MAINTAINER, 0, $suite_maintainer);
		$xml_writer->addXmlObject(P_SUITE_TYPE, 0, $suite_test_type);
		$xml_writer->addXmlObject(P_SUITE_DESCRIPTION, 0, $suite_description);
		$write_position = 1;

		do
		{
			switch($input_option)
			{
				case "Add Test":
					$test_to_add = pts_text_select_menu("Enter test name", $possible_tests);

					list($args, $description) = pts_prompt_test_options($test_to_add);

					for($i = 0; $i < count($args); $i++)
					{
						$xml_writer->addXmlObject(P_SUITE_TEST_NAME, $write_position, $test_to_add);
						$xml_writer->addXmlObject(P_SUITE_TEST_ARGUMENTS, $write_position, $args[$i]);
						$xml_writer->addXmlObject(P_SUITE_TEST_DESCRIPTION, $write_position, $description[$i]);
						$write_position++;
					}
					break;
				case "Add Sub-Suite":
					$suite_to_add = pts_text_select_menu("Enter test suite", $possible_suites);

					$xml_writer->addXmlObject(P_SUITE_TEST_NAME, $write_position, $suite_to_add);
					$write_position++;
					break;
			}
			echo "\nAvailable Options:\n";
			$input_option = pts_text_select_menu("Select next operation", array("Add Test", "Add Sub-Suite", "Save & Exit"));
		}
		while($input_option != "Save & Exit");

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
