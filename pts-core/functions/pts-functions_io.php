<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-functions_io.php: General user input / output functions

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


function p_str($str_o)
{
	//  $_ENV["LANG"]
	return $str_o;
}
function pts_text_input($question)
{
	do
	{
		echo "\n" . $question . ": ";
		$answer = trim(fgets(STDIN));
	}
	while(empty($answer));

	return $answer;
}
function pts_text_select_menu($user_string, $options_r)
{
	$option_count = count($options_r);

	do
	{
		echo "\n";
		for($i = 0; $i < $option_count; $i++)
		{
				echo ($i + 1) . ": " . $options_r[$i] . "\n";
		}
		echo "\n" . $user_string . ": ";
		$test_choice = trim(fgets(STDIN));
	}
	while(!(in_array($test_choice, $options_r) || isset($options_r[($test_choice - 1)]) && ($test_choice = $options_r[($test_choice - 1)]) != ""));

	return $test_choice;
}
function pts_bool_question($question, $default = true, $question_id = "UNKNOWN")
{
	// Prompt user for yes/no question
	if(pts_read_assignment("IS_BATCH_MODE") != false)
	{
		switch($question_id)
		{
			case "SAVE_RESULTS":
				$auto_answer = pts_read_user_config(P_OPTION_BATCH_SAVERESULTS, "TRUE");
				break;
			case "OPEN_BROWSER":
				$auto_answer = pts_read_user_config(P_OPTION_BATCH_LAUNCHBROWSER, "FALSE");
				break;
			case "UPLOAD_RESULTS":
				$auto_answer = pts_read_user_config(P_OPTION_BATCH_UPLOADRESULTS, "TRUE");
				break;
		}

		if(isset($auto_answer))
		{
			$answer = $auto_answer == "TRUE" || $auto_answer == "1";
		}
		else
		{
			$answer = $default;
		}
	}
	else
	{
		do
		{
			echo $question . " ";
			$input = trim(strtolower(fgets(STDIN)));
		}
		while($input != "y" && $input != "n" && $input != "");

		if($input == "y")
		{
			$answer = true;
		}
		else if($input == "n")
		{
			$answer = false;
		}
		else
		{
			$answer = $default;
		}
	}

	return $answer;
}
function pts_string_header($heading, $char = '=')
{
	// Return a string header
	$header_size = 36;

	foreach(explode("\n", $heading) as $line)
	{
		if(($line_length = strlen($line)) > $header_size)
		{
			$header_size = $line_length;
		}
	}

	$terminal_width = trim(shell_exec("tput cols 2>&1"));

	if($header_size > $terminal_width && $terminal_width > 1)
	{
		$header_size = $terminal_width;
	}

	return "\n" . str_repeat($char, $header_size) . "\n" . $heading . "\n" . str_repeat($char, $header_size) . "\n\n";
}
function pts_format_time_string($time, $format = "SECONDS", $standard_version = true)
{
	// Format an elapsed time string
	if($format == "MINUTES")
	{
		$time *= 60;
	}

	$formatted_time = array();

	if($time > 0)
	{
		$time_hours = floor($time / 3600);
		$time_minutes = floor(($time - ($time_hours * 3600)) / 60);
		$time_seconds = $time % 60;

		if($time_hours > 0)
		{
			if($standard_version)
			{
				$formatted_part = $time_hours . " Hour";

				if($time_hours > 1)
				{
					$formatted_part .= "s";
				}
			}
			else
			{
				$formatted_part = $time_hours . "h";
			}

			array_push($formatted_time, $formatted_part);
		}
		if($time_minutes > 0)
		{
			if($standard_version)
			{
				$formatted_part = $time_minutes . " Minute";

				if($time_minutes > 1)
				{
					$formatted_part .= "s";
				}
			}
			else
			{
				$formatted_part = $time_minutes . "m";
			}

			array_push($formatted_time, $formatted_part);
		}
		if($time_seconds > 0)
		{
			if($standard_version)
			{
				$formatted_part = $time_seconds . " Second";

				if($time_seconds > 1)
				{
					$formatted_part .= "s";
				}
			}
			else
			{
				$formatted_part = $time_seconds . "s";
			}

			array_push($formatted_time, $formatted_part);
		}
	}

	if($standard_version)
	{
		$time_string = implode(", ", $formatted_time);
	}
	else
	{
		$time_string = implode("", $formatted_time);
	}

	return $time_string;
}
function pts_estimated_time_string($time)
{
	// Estimated time that it will take for the test to complete
	$strlen_time = strlen($time);

	if(strlen($time_trim = str_replace("~", "", $time)) != $strlen_time)
	{
		$formatted_string = "Approximately " . $time_trim;
	}
	else if(strlen($time_trim = str_replace(array('l'), '', $time)) != $strlen_time)
	{
		$formatted_string = "Less Than " . $time_trim;
	}
	else if(strlen($time_trim = str_replace(array('g'), '', $time)) != $strlen_time)
	{
		$formatted_string = "Greater Than " . $time_trim;
	}
	else if(strlen($time_trim = str_replace("-", ", ", $time)) != $strlen_time)
	{
		$time_trim = explode(",", $time_trim);

		$time_trim = array_map("trim", $time_trim);

		if(count($time_trim) == 2)
		{
			$formatted_string = $time_trim[0] . " to " . $time_trim[1];
		}
	}
	else
	{
		$formatted_string = $time;
	}

	$formatted_string .= " Minutes";

	return $formatted_string;
}

?>
