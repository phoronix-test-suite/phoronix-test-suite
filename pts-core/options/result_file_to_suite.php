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

pts_loader::load_definitions("result-file.xml");
pts_loader::load_definitions("test-suite.xml");

class result_file_to_suite implements pts_option_interface
{
	public static function run($r)
	{
		$result_file = false;
		if(count($r) != 0)
		{
			$result_file = $r[0];
		}

		while(($result_file = pts_find_result_file($result_file)) == false)
		{
			$result_file = pts_user_io::prompt_user_input("Enter name of result file");
		}

		$suite_name = pts_user_io::prompt_user_input("Enter name of suite");
		$suite_test_type = pts_user_io::prompt_text_menu("Select test type", pts_types::subsystem_targets());
		$suite_maintainer = pts_user_io::prompt_user_input("Enter suite maintainer name");
		$suite_description = pts_user_io::prompt_user_input("Enter suite description");

		$xml_writer = new tandem_XmlWriter();
		$xml_writer->addXmlObject(P_SUITE_TITLE, 0, $suite_name);
		$xml_writer->addXmlObject(P_SUITE_VERSION, 0, "1.0.0");
		$xml_writer->addXmlObject(P_SUITE_MAINTAINER, 0, $suite_maintainer);
		$xml_writer->addXmlObject(P_SUITE_TYPE, 0, $suite_test_type);
		$xml_writer->addXmlObject(P_SUITE_DESCRIPTION, 0, $suite_description);

		// Read results file
		$result_file = new pts_result_file($result_file);

		foreach($result_file->get_result_objects() as $i => $result_object)
		{
			$xml_writer->addXmlObject(P_SUITE_TEST_NAME, $i, $result_object->test_profile->get_identifier());

			if($result_object->get_arguments() != null && $result_object->get_arguments_description() != null)
			{
				$xml_writer->addXmlObject(P_SUITE_TEST_ARGUMENTS, $i, $result_object->get_arguments());
				$xml_writer->addXmlObject(P_SUITE_TEST_DESCRIPTION, $i, $result_object->get_arguments_description());
			}
		}

		// Finish it off
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

		if($xml_writer->saveXMLFile($save_to) != false)
		{
			echo "\n\nSaved To: " . $save_to . "\nTo run this suite, type: phoronix-test-suite benchmark " . $suite_identifier . "\n\n";
		}
	}
}

?>
