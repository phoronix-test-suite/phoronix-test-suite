<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2004-2008, Michael Larabel
	gpu_error_counter.php: A module that attempts to track GPU (Graphics Processing Unit) errors

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

class gpu_error_counter extends pts_module_interface
{
	const module_name = "GPU Error Counter";
	const module_version = "1.0.0";
	const module_description = "This is a module that attempts to track GPU (Graphics Processing Unit) errors if any occur. Currently only NVIDIA graphics cards are supported.";
	const module_author = "Phoronix Media";

	static $error_pointer = 0;
	static $error_count = 0; // Number of GPU errors that were detected
	static $error_analysis = array(); // Array of error break down. For each array index is for a test where an error happened, it's TEST_NAME => ERROR_COUNT


	public static function __startup()
	{
		if(!IS_NVIDIA_GRAPHICS)
			return PTS_MODULE_UNLOAD;

		self::$error_pointer = self::nvidia_gpu_error_count(); // Set the pointer
	}
	public static function __post_test_run()
	{
		$current_error_position = self::nvidia_gpu_error_count();

		if($current_error_position > self::$error_pointer && !empty($GLOBALS["TEST_IDENTIFIER"]))
		{
			// GPU Error(s) Happened During The Test
			$this_test = $GLOBALS["TEST_IDENTIFIER"];
			$this_error_count = $current_error_position - self::$error_pointer;

			if(isset(self::$error_analysis[$this_test]))
				$this_error_count += self::$error_analysis[$this_test];

			self::$error_analysis[$this_test] = $this_error_count; // Tally up errors for this test
			self::$error_count += $this_error_count; // Add to total error count
			self::$error_pointer = $current_error_position; // Reset the pointer
		}
	}
	public static function __shutdown()
	{
		if(self::$error_count > 0)
		{
			$error_breakdown = "\n";
			foreach(self::$error_analysis as $test => $error_count)
				$error_breakdown .= "\n" . $test . ": " . $error_count;

			echo pts_string_header("GPU Errors: " . $error_count. $error_breakdown);
		}
	}
	protected static function nvidia_gpu_error_count()
	{
		$count = read_nvidia_extension("GPUErrors");

		if($count == null || !is_numeric($count))
			$count = 0;

		return $count;
	}
}

?>
