<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
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
			default:
				$auto_answer = "true";
				break;
		}

		$answer = pts_string_bool($auto_answer);
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

	if(is_numeric($terminal_width) && $header_size > $terminal_width && $terminal_width > 1)
	{
		$header_size = $terminal_width;
	}

	return "\n" . str_repeat($char, $header_size) . "\n" . $heading . "\n" . str_repeat($char, $header_size) . "\n\n";
}
function pts_format_time_string($time, $format = "SECONDS", $standard_version = true, $round_to = 0)
{
	// Format an elapsed time string
	if($format == "MINUTES")
	{
		$time *= 60;
	}
	if($round_to > 0)
	{
		$time += $round_to - ($time % $round_to);
	}

	$formatted_time = array();

	if($time > 0)
	{
		$time_r[0] = array(floor($time / 3600), "Hour");
		$time_r[1] = array(floor(($time - ($time_r[0][0] * 3600)) / 60), "Minute");
		$time_r[2] = array($time % 60, "Second");

		foreach($time_r as $time_segment)
		{
			if($time_segment[0] > 0)
			{
				$formatted_part = $time_segment[0];

				if($standard_version)
				{
					$formatted_part .= " " . $time_segment[1];

					if($time_segment[0] > 1)
					{
						$formatted_part .= "s";
					}
				}
				else
				{
					$formatted_part .= strtolower(substr($time_segment[1], 0, 1));
				}

				array_push($formatted_time, $formatted_part);
			}
		}
	}

	return implode(($standard_version ? ", " : ""), $formatted_time);
}

?>
