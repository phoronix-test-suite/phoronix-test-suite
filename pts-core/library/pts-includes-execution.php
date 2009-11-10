<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel
	pts-includes-execution.php: Functions needed for execution during only the test installation and run processes

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

function pts_call_test_script($test_identifier, $script_name, $print_string = "", $pass_argument = "", $extra_vars = null, $use_ctp = true)
{
	$result = null;
	$test_directory = TEST_ENV_DIR . $test_identifier . "/";

	$tests_r = ($use_ctp ? pts_contained_tests($test_identifier, true) : array($test_identifier));

	foreach($tests_r as &$this_test)
	{
		if(is_file(($run_file = pts_location_test_resources($this_test) . $script_name . ".php")) || is_file(($run_file = pts_location_test_resources($this_test) . $script_name . ".sh")))
		{
			$file_extension = substr($run_file, (strrpos($run_file, ".") + 1));

			if(!empty($print_string))
			{
				echo $print_string;
			}

			if($file_extension == "php")
			{
				if($script_name == "parse-results")
				{
					$extra_vars["PARSE_RESULTS_SCRIPT"] = $run_file;
					$run_file = TEST_LIBRARIES_DIR . "results-parsing-functions.php";
				}

				$this_result = pts_exec("cd " .  $test_directory . " && " . PHP_BIN . " " . $run_file . " \"" . $pass_argument . "\" 2>&1", $extra_vars);
			}
			else if($file_extension == "sh")
			{
				$this_result = pts_exec("cd " .  $test_directory . " && sh " . $run_file . " \"" . $pass_argument . "\" 2>&1", $extra_vars);
			}
			else
			{
				$this_result = null;
			}

			if(trim($this_result) != "")
			{
				$result = $this_result;
			}
		}
	}

	return $result;
}

?>
