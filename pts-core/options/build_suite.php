<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel

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
	public static function run($r)
	{
		pts_client::$display->generic_heading("Test Suite Creation");

		$suite_name = pts_user_io::prompt_user_input("Enter name of suite");
		$suite_test_type = pts_user_io::prompt_text_menu("Select test type", pts_types::subsystem_targets());
		$suite_maintainer = pts_user_io::prompt_user_input("Enter suite maintainer name");
		$suite_description = pts_user_io::prompt_user_input("Enter suite description");

		$possible_suites = pts_suites::available_suites();
		$possible_tests = array();
		foreach(pts_tests::available_tests() as $identifier)
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

		foreach($r as $test_object)
		{
			if(pts_is_test($test_object))
			{
				list($args, $description) = pts_test_run_options::prompt_user_options($test_object);

				for($i = 0; $i < count($args); $i++)
				{
					$xml_writer->addXmlObject(P_SUITE_TEST_NAME, $write_position, $test_object);
					$xml_writer->addXmlObject(P_SUITE_TEST_ARGUMENTS, $write_position, $args[$i]);
					$xml_writer->addXmlObject(P_SUITE_TEST_DESCRIPTION, $write_position, $description[$i]);
					$write_position++;
				}
			}
			else if(pts_is_suite($test_object))
			{
				$xml_writer->addXmlObject(P_SUITE_TEST_NAME, $write_position, $test_object);
				$write_position++;
			}
		}

		do
		{
			switch($input_option)
			{
				case "Add Test":
					$test_to_add = pts_user_io::prompt_text_menu("Enter test name", $possible_tests);

					list($args, $description) = pts_test_run_options::prompt_user_options($test_to_add);

					for($i = 0; $i < count($args); $i++)
					{
						$xml_writer->addXmlObject(P_SUITE_TEST_NAME, $write_position, $test_to_add);
						$xml_writer->addXmlObject(P_SUITE_TEST_ARGUMENTS, $write_position, $args[$i]);
						$xml_writer->addXmlObject(P_SUITE_TEST_DESCRIPTION, $write_position, $description[$i]);
						$write_position++;
					}
					break;
				case "Add Sub-Suite":
					$suite_to_add = pts_user_io::prompt_text_menu("Enter test suite", $possible_suites);

					$xml_writer->addXmlObject(P_SUITE_TEST_NAME, $write_position, $suite_to_add);
					$write_position++;
					break;
			}
			echo "\nAvailable Options:\n";
			$input_option = pts_user_io::prompt_text_menu("Select next operation", array("Add Test", "Add Sub-Suite", "Save & Exit"));
		}
		while($input_option != "Save & Exit");

		$suite_identifier = pts_test_run_manager::clean_save_name_string(str_replace(" ", "-", strtolower($suite_name)));

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

		$fp = $xml_writer->saveXMLFile($save_to);

		if($fp != false)
		{
			echo "\n\nSaved To: " . $save_to . "\nTo run this suite, type: phoronix-test-suite benchmark " . $suite_identifier . "\n\n";
		}
	}
}

?>
