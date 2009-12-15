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

class bisect extends pts_module_interface
{
	const module_name = "PTS Bisect / Regression Tracker";
	const module_version = "0.1.0";
	const module_description = "This module layers the Phoronix Test Suite atop the Git revision control system to automate the process of bisecting a Git repository to track down a performance regression. Read the included documentation for instructions. Other version control systems to be supported in the future.";
	const module_author = "Phoronix Media";

	public static function module_setup()
	{
		return array(
		new pts_module_option("repo_dir", "Enter the path to the local folder of the repository to be tested", "LOCAL_DIRECTORY"),
		new pts_module_option("rev_good", "Enter the revision of a good version", null, "Original Head"),
		new pts_module_option("rev_bad", "Enter the revision of a bad version"),
		new pts_module_option("build_script", "Enter the location of the program's build script", "LOCAL_EXECUTABLE"),
		new pts_module_option("initial_result_file", "Enter the test result identifier that exhibits the regression", "PTS_TEST_RESULT"),
		);
	}
	public static function module_setup_validate($options)
	{
		$options["repo_dir"] = pts_add_trailing_slash($options["repo_dir"]);

		if(!is_dir($options["repo_dir"] . ".git"))
		{
			echo $options["repo_dir"] . " is not a Git directory!\n";
			return array();
		}

		$result_file = new pts_result_file($options["initial_result_file"]);
		$result_objects = $result_file->get_result_objects();

		if(count($result_objects) == 1)
		{
			$selected_index = 0;
		}
		else
		{
			do
			{
				$selected_index = pts_text_input("Enter the index of the test that you wish to run");
			}
			while(!isset($result_objects[($selected_index--)]));
		}

		$system_identifiers = $result_object->get_result_buffer()->get_identifiers();
		$values = $result_object->get_result_buffer()->get_values();

		$proportion = $result_objects[$selected_index]->get_proportion();

		$bad_run = pts_text_select_menu("Select test run that regressed", $system_identifiers);
		$good_run = pts_text_select_menu("Select test run that is good", $system_identifiers);

		$bad_run_index = array_search($bad_run, $system_identifiers);
		$good_run_index = array_search($good_run, $system_identifiers);

		$options["regressed_performance_number"] = $values[$bad_run_index];
		$options["good_performance_number"] = $values[$good_run_index];
		$options["regression_fraction"] = $values[$bad_run_index] / $values[$good_run_index];
		$options["test_proportion"] = $proportion;
		$options["test_name"] = $result_objects[$selected_index]->get_test_name();
		$options["test_args"] = $result_objects[$selected_index]->get_arguments();
		$options["test_attr"] = $result_objects[$selected_index]->get_attributes();

		return $options;
	}
	public static function user_commands()
	{
		return array("start" => "bisect_start");
	}

	//
	// User Run Command(s)
	//

	public static function bisect_start()
	{
		if(!pts_module::is_module_setup())
		{
			echo "\nYou first must run:\n\nphoronix-test-suite module-setup git-bisect\n\n";
			return false;
		}
		if(!($git_bin = pts_executable_in_path("git")))
		{
			echo "\nGit must first be installed\n\n";
			return false;
		}

		$repo_dir = pts_module::read_option("repo_dir");
		$build_script = pts_module::read_option("build_script");

		if(pts_module::read_option("current_status") == false || pts_module::read_option("current_status") == "START")
		{
			$xml_writer = new tandem_XmlWriter();
			$xml_writer->addXmlObject(P_SUITE_TITLE, 0, "Bisect");
			$xml_writer->addXmlObject(P_SUITE_VERSION, 0, "1.0.0");
			$xml_writer->addXmlObject(P_SUITE_MAINTAINER, 0, "Phoronix Media");
			$xml_writer->addXmlObject(P_SUITE_TYPE, 0, "System");
			$xml_writer->addXmlObject(P_SUITE_DESCRIPTION, 0, "This is for automated testing.");
			$xml_writer->addXmlObject(P_SUITE_TEST_NAME, 1, pts_module::read_option("test_name"));
			$xml_writer->addXmlObject(P_SUITE_TEST_ARGUMENTS, 1,pts_module::read_option("test_args"));
			$xml_writer->addXmlObject(P_SUITE_TEST_DESCRIPTION, 1, pts_module::read_option("test_attr"));
			$xml_writer->saveXMLFile(XML_SUITE_LOCAL_DIR . "bisect-test.xml");

			$rev_good = pts_module::read_option("rev_good");
			pts_module::set_option("current_status", "GOOD_TEST");
			pts_module::set_option("current_revision", $rev_good);
			shell_exec("cd $repo_dir; $git_bin checkout $rev_good; $build_script;");

			// TODO: Add back script to recover PTS run if a reboot occurs during the build progress, or just leave it up to the build script
		}

		if(pts_module::read_option("current_status") == "GOOD_TEST")
		{
			pts_module::set_option("current_status", "PROCESS_GOOD_TEST");
			pts_module::set_option("current_result", -1);
			pts_run_option_next("run_test", "bisect-test", array("AUTOMATED_MODE" => true, "DO_NOT_SAVE_RESULTS" => true));
			pts_run_option_next("bisect.start");
			return true;
		}

		if(pts_module::read_option("current_status") == "PROCESS_GOOD_TEST")
		{
			$result = pts_module::read_option("current_result");
			pts_module::set_option("current_result", -1);

			if($result > 1)
			{
				pts_module::set_option("good_result", $result);
				pts_module::set_option("regression_threshold", $result * pts_module::read_option("regression_fraction"));
				pts_module::set_option("current_status", "START_BISECT");
			}
			else
			{
				pts_module::set_option("current_status", "START");
			}
		}

		if(pts_module::read_option("current_status") == "START_BISECT")
		{
			$rev_bad = pts_module::read_option("rev_bad");
			$rev_good = pts_module::read_option("rev_good");

			$output = shell_exec("cd $repo_dir; $git_bin checkout -f; $git_bin bisect start; $git_bin bisect bad $rev_bad; $git_bin bisect good $rev_good");

			if(($start = strrpos($output, "[")) !== false)
			{
				$output = substr($output, $start + 1);
				$output = substr($output, 0, strpos($output, "]"));
				pts_module::set_option("current_revision", $output);
			}

			pts_module::set_option("current_status", "RUN_BUILD_SCRIPT");
		}

		if(pts_module::read_option("current_status") == "RUN_BUILD_SCRIPT")
		{
			pts_module::set_option("current_status", "RUN_TEST");
			shell_exec("cd $repo_dir; $build_script;");
		}

		if(pts_module::read_option("current_status") == "RUN_TEST")
		{
			pts_module::set_option("current_status", "PROCESS_TEST");
			pts_module::set_option("current_result", -1);
			pts_run_option_next("run_test", "bisect-test", array("AUTOMATED_MODE" => true, "DO_NOT_SAVE_RESULTS" => true));
			pts_run_option_next("bisect.start");
			return true;
		}

		if(pts_module::read_option("current_status") == "PROCESS_TEST")
		{
			$result = pts_module::read_option("current_result");

			if($result != -1)
			{
				pts_module::set_option("last_result", $result);
				pts_module::set_option("current_result", -1);
			}

			if($result > 1)
			{
				$regression_threshold = pts_module::read_option("regression_threshold");
				$good_result = pts_module::read_option("good_result");
				$biggest_delta = pts_module::read_option("biggest_delta");
				$current_revision = pts_module::read_option("current_revision");

				if(pts_module::read_option("HIB"))
				{
					$compare_fraction = $result / $good_result;

					if($compare_fraction > $biggest_delta)
					{
						pts_module::set_option("biggest_delta", $compare_fraction);
						pts_module::set_option("biggest_delta_rev", $current_revision);
					}

					if($result < ($regression_threshold * 1.03))
					{
						// Bad
						pts_module::set_option("current_status", "BAD_NEXT_BISECT");
					}
					else
					{
						// Good
						pts_module::set_option("current_status", "GOOD_NEXT_BISECT");
					}
				}
				else
				{
					$compare_fraction = $good_result / $result;

					if($compare_fraction > $biggest_delta)
					{
						pts_module::set_option("biggest_delta", $compare_fraction);
						pts_module::set_option("biggest_delta_rev", $current_revision);
					}

					if($result > ($regression_threshold * 0.97))
					{
						// Bad
						pts_module::set_option("current_status", "BAD_NEXT_BISECT");
					}
					else
					{
						// Good
						pts_module::set_option("current_status", "GOOD_NEXT_BISECT");
					}
				}
			}
			else
			{
				echo "No result!";
				return false;
			}
		}

		if(pts_module::read_option("current_status") == "GOOD_NEXT_BISECT" || pts_module::read_option("current_status") == "BAD_NEXT_BISECT")
		{
			$output = trim(shell_exec("cd $repo_dir; $git_bin checkout -f; $git_bin bisect " . (substr(pts_module::read_option("current_status"), 0, 3) == "BAD" ? "bad" : "good")));

			if(strpos($output, "first bad commit"))
			{
				echo $output;
				$commit = substr($output, 0, strpos($output, " "));
				pts_module::set_option("bad_revision", $commit);
				return true;
			}

			if(($start = strrpos($output, "[")) !== false)
			{
				$output = substr($output, $start + 1);
				$output = substr($output, 0, strpos($output, "]"));
				pts_module::set_option("current_revision", $output);
			}

			pts_module::set_option("current_status", "RUN_BUILD_SCRIPT");
		}
	}

	//
	// PTS Module API Hooks
	//
	
	public static function __post_test_run($test_result)
	{
		if($test_result->get_test_identifier() == pts_module::read_option("test_name") && $test_result->get_attribute("EXTRA_ARGUMENTS") == pts_module::read_option("test_args"))
		{
			$result = $test_result->get_result();
			pts_module::set_option("current_result", $result);
		}
	}
}

?>
