<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel

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

class import_profile_package implements pts_option_interface
{
	static $temp_path = null;

	public static function required_function_sets()
	{
		return array("validation");
	}
	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, "pts_is_file_or_url", "to_import", "A local file or remote URL of a PTS test profile package to import must be passed.")
		);
	}
	public static function run($args)
	{
		self::$temp_path = pts_temp_dir();

		$to_import_name = basename($args["to_import"]);

		if(is_file($args["to_import"]))
		{
			copy($args["to_import"], self::$temp_path . "profile-package.zip");
		}
		else
		{
			pts_download($args["to_import"], self::$temp_path . "profile-package.zip");
		}

		pts_extract(self::$temp_path . "profile-package.zip");

		$xml_file = pts_glob(self::$temp_path . "*.xml");

		if(count($xml_file) == 0)
		{
			echo "\nNo XML test profile was found.\n";
			self::cleanup_temp_environment();
			return false;
		}

		if(!is_dir(self::$temp_path . "test-resources"))
		{
			echo "\nNo test-resources directory found.\n";
			self::cleanup_temp_environment();
			return false;
		}

		$xml_file = array_pop($xml_file);
		$validation_errors = array();
		$test_parser = new pts_test_tandem_XmlReader($xml_file);
		$imported_test_version = $test_parser->getXmlValue(P_TEST_PTSVERSION);
		pts_validation_check_xml_tags($test_parser, pts_validation_required_test_tags(), $validation_errors);

		if(count($validation_errors) != 0)
		{
			pts_validation_print_problem("ERROR", $validation_errors);
			self::cleanup_temp_environment();
			return false;
		}

		$test_profile_identifier = basename($xml_file, ".xml");

		$existing_test_profile_version = pts_test_profile_version($test_profile_identifier); // the version if the profile is installed already otherwise returns null
		$can_write = true;

		if($existing_test_profile != null && pts_version_newer($imported_test_version, $existing_test_profile_version) == $existing_test_profile_version)
		{
			echo "\nThere already exists a " . $test_profile_identifier . " test profile that is newer than what you are trying to import.\n";
			self::cleanup_temp_environment();
			return false;
		}

		if(is_file(XML_PROFILE_LOCAL_DIR . $test_profile_identifier . ".xml"))
		{
			pts_rename(XML_PROFILE_LOCAL_DIR . $test_profile_identifier . ".xml", XML_PROFILE_LOCAL_DIR . $test_profile_identifier . ".xml.old");
		}
		if(is_dir(TEST_RESOURCE_LOCAL_DIR . $test_profile_identifier))
		{
			pts_rename(TEST_RESOURCE_LOCAL_DIR  . $test_profile_identifier, TEST_RESOURCE_LOCAL_DIR  . $test_profile_identifier . ".old");
		}

		pts_move($xml_file, XML_PROFILE_LOCAL_DIR . $test_profile_identifier . ".xml");
		pts_mkdir(TEST_RESOURCE_LOCAL_DIR . $test_profile_identifier);

		foreach(pts_glob(self::$temp_path . "test-resources/*") as $test_resource_file)
		{
			echo $test_resource_file;
			pts_move($test_resource_file, TEST_RESOURCE_LOCAL_DIR . $test_profile_identifier . "/" . basename($test_resource_file));
		}

		pts_rebuild_test_type_cache($identifier);
		self::cleanup_temp_environment();
		pts_set_assignment_next("PREV_TEST_IDENTIFIER", $test_profile_identifier);
		echo "\n\n" . $test_profile_identifier . " is now installed.\n\n";
	}
	protected static function cleanup_temp_environment()
	{
		pts_remove(self::$temp_path);
		pts_unlink(self::$temp_path);
	}
}

?>
