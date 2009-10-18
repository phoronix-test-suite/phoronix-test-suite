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

class pts_test_notes_manager
{
	private static $notes = array();

	public static function add_note($note)
	{
		$note = trim($note);

		switch($note)
		{
			case "JAVA_VERSION":
				$note = phodevi::read_property("system", "java-version");
				break;
			case "PYTHON_VERSION":
				$note = phodevi::read_property("system", "python-version");
				break;
			case "2D_ACCEL_METHOD":
				$note = phodevi::read_property("gpu", "2d-accel-method");
				$note = !empty($note) ? "2D Acceleration: " . $note : null;
				break;
		}

		if(!empty($note) && !in_array($note, self::$notes))
		{
			array_push(self::$notes, $note);
		}
	}
	public static function generate_test_notes($test_type)
	{
		static $check_processes = null;

		if(empty($check_processes) && is_file(STATIC_DIR . "process-reporting-checks.txt"))
		{
			$word_file = trim(file_get_contents(STATIC_DIR . "process-reporting-checks.txt"));
			$processes_r = pts_trim_explode("\n", $word_file);
			$check_processes = array();

			foreach($processes_r as $p)
			{
				$p = explode("=", $p);
				$p_title = trim($p[0]);
				$p_names = pts_trim_explode(",", $p[1]);

				$check_processes[$p_title] = array();

				foreach($p_names as $p_name)
				{
					array_push($check_processes[$p_title], $p_name);
				}
			}
		}

		if(!IS_BSD)
		{
			self::add_note(self::process_running_string($check_processes));
		}

		// Check if Security Enhanced Linux was enforcing, permissive, or disabled
		if(is_readable("/etc/sysconfig/selinux"))
		{
			if(stripos(file_get_contents("/etc/sysconfig/selinux"), "selinux=disabled") === false)
			{
				self::add_note("SELinux was enabled.");
			}
		}
		else if(is_readable("/proc/cmdline"))
		{
			if(stripos(file_get_contents("/proc/cmdline"), "selinux=1") != false)
			{
				self::add_note("SELinux was enabled.");
			}
		}

		// Power Saving Technologies?
		self::add_note(phodevi::read_property("cpu", "power-savings-mode"));
		self::add_note(phodevi::read_property("motherboard", "power-mode"));
		self::add_note(phodevi::read_property("system", "virtualized-mode"));

		if($test_type == "Graphics" || $test_type == "System")
		{
			$aa_level = phodevi::read_property("gpu", "aa-level");
			$af_level = phodevi::read_property("gpu", "af-level");

			if(!empty($aa_level))
			{
				self::add_note("Antialiasing: " . $aa_level);
			}
			if(!empty($af_level))
			{
				self::add_note("Anisotropic Filtering: " . $af_level);
			}
		}

		$notes_string = trim(implode(". \n", self::$notes));
		self::$notes = array();

		return $notes_string;
	}
	public static function process_running_string($process_arr)
	{
		// Format a nice string that shows processes running
		$p = array();
		$p_string = "";

		$process_arr = pts_to_array($process_arr);

		foreach($process_arr as $p_name => $p_process)
		{
			$p_process = pts_to_array($p_process);

			foreach($p_process as $process)
			{
				if(pts_process_running_bool($process))
				{
					array_push($p, $p_name);
				}
			}
		}

		$p = array_keys(array_flip($p));

		if(($p_count = count($p)) > 0)
		{
			for($i = 0; $i < $p_count; $i++)
			{
				$p_string .= $p[$i];

				if($i != ($p_count - 1) && $p_count > 2)
				{
					$p_string .= ",";
				}
				$p_string .= " ";

				if($i == ($p_count - 2))
				{
					$p_string .= "and ";
				}
			}

			$p_string .= $p_count == 1 ? "was" : "were";
			$p_string .= " running on this system";
		}

		return $p_string;
	}
}

?>
