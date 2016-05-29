<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2016, Phoronix Media
	Copyright (C) 2009 - 2016, Michael Larabel

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

		if(!empty($note) && !in_array($note, self::$notes))
		{
			self::$notes[] = $note;
		}
	}
	public static function generate_test_notes(&$test_result_objects)
	{
		// TODO XXX: Integrate with system table notes
		static $check_processes = null;

		$test_types = array();
		$test_tags = array();

		foreach($test_result_objects as &$test_result)
		{
			pts_arrays::unique_push($test_types, $test_result->test_profile->get_test_hardware_type());

			foreach($test_result->test_profile->get_internal_tags() as $tag)
			{
				pts_arrays::unique_push($test_tags, $tag);
			}
		}

		if(in_array('Java', $test_tags))
		{
			self::add_note(phodevi::read_property('system', 'java-version'));
		}
		if(in_array('Python', $test_tags))
		{
			self::add_note(phodevi::read_property('system', 'python-version'));
		}
		if(in_array('Wine', $test_tags))
		{
			self::add_note(phodevi::read_property('system', 'wine-version'));
		}
		if(in_array('OpenCL', $test_tags))
		{
			$cores = phodevi::read_property('gpu', 'compute-cores');

			if($cores > 0)
			{
				self::add_note('GPU Compute Cores: ' . $cores);
			}
		}

		/*
		if(phodevi::is_bsd() == false)
		{
			if(empty($check_processes))
			{
				$check_processes = array(
					'Compiz' => array('compiz', 'compiz.real'),
					'Firefox' => array('firefox', 'mozilla-firefox', 'mozilla-firefox-bin'),
					'Thunderbird' => array('thunderbird', 'mozilla-thunderbird', 'thunderbird-bin'),
					'BOINC' => array('boinc', 'boinc_client')
					);
			}

			self::add_note(self::process_running_string($check_processes));
		}
		*/

		// Check if Security Enhanced Linux was enforcing, permissive, or disabled
		if(is_readable('/etc/sysconfig/selinux'))
		{
			if(stripos(file_get_contents('/etc/sysconfig/selinux'), 'selinux=disabled') === false)
			{
				self::add_note('SELinux: Enabled');
			}
		}
		else if(isset(phodevi::$vfs->cmdline))
		{
			if(stripos(phodevi::$vfs->cmdline, 'selinux=1') != false)
			{
				self::add_note('SELinux: Enabled');
			}
		}

		/*
		// Encrypted file-system?
		if(phodevi::is_linux() && is_readable('/sys/fs/ecryptfs/version'))
		{
			self::add_note('eCryptfs was active.');
		}
		*/

		self::add_note(phodevi::read_property('motherboard', 'power-mode'));

		if(in_array('Graphics', $test_types) || in_array('System', $test_types))
		{
			$aa_level = phodevi::read_property('gpu', 'aa-level');
			$af_level = phodevi::read_property('gpu', 'af-level');

			if(!empty($aa_level))
			{
				self::add_note('Antialiasing: ' . $aa_level);
			}
			if(!empty($af_level))
			{
				self::add_note('Anisotropic Filtering: ' . $af_level);
			}
		}

		$notes_string = trim(implode('. ', self::$notes));

		if($notes_string != null)
		{
			$notes_string .= '.';
		}

		self::$notes = array();

		return $notes_string;
	}
	public static function process_running_string($process_arr)
	{
		// Format a nice string that shows processes running
		$p = array();
		$p_string = null;

		$process_arr = pts_arrays::to_array($process_arr);
		foreach($process_arr as $p_name => $p_processes)
		{
			foreach($p_processes as $process)
			{
				if(pts_client::is_process_running($process))
				{
					$p[] = $p_name;
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
					$p_string .= ',';
				}
				$p_string .= ' ';

				if($i == ($p_count - 2))
				{
					$p_string .= 'and ';
				}
			}

			$p_string .= $p_count == 1 ? 'was' : 'were';
			$p_string .= ' running on this system';
		}

		return $p_string;
	}
}

?>
