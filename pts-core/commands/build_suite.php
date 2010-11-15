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

pts_load_xml_definitions("result-file.xml");
pts_load_xml_definitions("test-suite.xml");

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
			$test_profile = new pts_test_profile($identifier);

			if($test_profile->is_supported())
			{
				array_push($possible_tests, $identifier);
			}
		}

		$xml_writer = new nye_XmlWriter();
		$xml_writer->addXmlNode(P_SUITE_TITLE, $suite_name);
		$xml_writer->addXmlNode(P_SUITE_VERSION, "1.0.0");
		$xml_writer->addXmlNode(P_SUITE_MAINTAINER, $suite_maintainer);
		$xml_writer->addXmlNode(P_SUITE_TYPE, $suite_test_type);
		$xml_writer->addXmlNode(P_SUITE_DESCRIPTION, $suite_description);
		$write_position = 1;

		foreach($r as $test_object)
		{
			$test_object = pts_types::identifier_to_object($test_object);

			if($test_object instanceof pts_test_profile)
			{
				list($args, $description) = pts_test_run_options::prompt_user_options($test_object);

				for($i = 0; $i < count($args); $i++)
				{
					$xml_writer->addXmlNode(P_SUITE_TEST_NAME, $test_object->get_identifier());
					$xml_writer->addXmlNode(P_SUITE_TEST_ARGUMENTS, $args[$i]);
					$xml_writer->addXmlNode(P_SUITE_TEST_DESCRIPTION, $description[$i]);
					$write_position++;
				}
			}
			else if($test_object instanceof pts_test_suite)
			{
				$xml_writer->addXmlNode(P_SUITE_TEST_NAME, $test_object->get_identifier());
				$write_position++;
			}
		}

		$input_option = null;

		do
		{
			switch($input_option)
			{
				case "Add Test":
					$test_to_add = pts_user_io::prompt_text_menu("Enter test name", $possible_tests);
					$test_profile = new pts_test_profile($test_to_add);

					list($args, $description) = pts_test_run_options::prompt_user_options($test_profile);

					for($i = 0; $i < count($args); $i++)
					{
						$xml_writer->addXmlNode(P_SUITE_TEST_NAME, $test_to_add);
						$xml_writer->addXmlNode(P_SUITE_TEST_ARGUMENTS, $args[$i]);
						$xml_writer->addXmlNode(P_SUITE_TEST_DESCRIPTION, $description[$i]);
						$write_position++;
					}
					break;
				case "Add Sub-Suite":
					$suite_to_add = pts_user_io::prompt_text_menu("Enter test suite", $possible_suites);

					$xml_writer->addXmlNode(P_SUITE_TEST_NAME, $suite_to_add);
					$write_position++;
					break;
			}
			echo "\nAvailable Options:\n";
			$input_option = pts_user_io::prompt_text_menu("Select next operation", array("Add Test", "Add Sub-Suite", "Save & Exit"));
		}
		while($input_option != "Save & Exit");

		$suite_identifier = pts_test_run_manager::clean_save_name_string($suite_name);

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
