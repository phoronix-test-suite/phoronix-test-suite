<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2012 - 2021, Phoronix Media
	Copyright (C) 2012 - 2021, Michael Larabel

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

ini_set('memory_limit', '16192M');

class ob_test_profile_analyze implements pts_option_interface
{
	const doc_section = 'OpenBenchmarking.org';
	const doc_description = 'This option is intended for test profile creators and generates a range of meta-data and other useful information that can be submitted to OpenBenchmarking.org to provide more verbose information for users of your test profiles.';

	public static function run($r)
	{
		//ini_set('memory_limit', '2048M');
		// https://software.intel.com/sites/landingpage/IntrinsicsGuide/#expand=641
		$intrinsics_search = array(
			);
		foreach(pts_types::identifiers_to_test_profile_objects($r, false, true) as $test_profile)
		{
			$qualified_identifier = $test_profile->get_identifier();

			// Set some other things...
			pts_env::set('FORCE_TIMES_TO_RUN', 1);
			pts_env::set('TEST_RESULTS_NAME', $test_profile->get_title() . ' Testing ' . date('Y-m-d'));
			pts_env::set('TEST_RESULTS_IDENTIFIER', 'Sample Run');
			pts_env::set('TEST_RESULTS_DESCRIPTION', 1);

			pts_openbenchmarking_client::override_client_setting('AutoUploadResults', false);
			pts_openbenchmarking_client::override_client_setting('UploadSystemLogsByDefault', true);
			pts_test_installer::standard_install($qualified_identifier, true);
			if($test_profile->test_installation == false || !$test_profile->test_installation->is_installed())
			{
				// Test has issues even installing, so skip it...
				continue;
			}
			$test_binary = self::locate_test_profile_lead_binary($test_profile);

			// search for intrinsics
			$intrinsics = array();

			//self::recursively_search_text_files_in_dir($test_profile->get_test_executable_dir(), $intrics_search, $intrinsics);
			$honors_cflags = false;
			$shared_library_dependencies = array();
			$instruction_usage = array();

			if(!empty($test_binary) && is_executable($test_binary))
			{
				$ldd = shell_exec('ldd ' . $test_binary);
				if(!empty($ldd))
				{
					$ldd = trim($ldd);
				}

				foreach(explode(PHP_EOL, $ldd) as $line)
				{
					$line = explode(' => ', $line);

					if(count($line) == 2)
					{
						$shared_library_dependencies[] = trim(basename($line[0]));
					}
				}

				$libraries_found_in_test_dir = array();
				foreach($shared_library_dependencies as $look_for_so)
				{
					$looked_so = self::recursively_find_file($test_profile->get_test_executable_dir(), $look_for_so, false);
					if($looked_so)
					{
						$libraries_found_in_test_dir[] = $looked_so;
					}
				}

				//echo PHP_EOL . 'SHARED LIBRARY DEPENDENCIES: ' . PHP_EOL;
				//print_r($shared_library_dependencies);

				$external_dependencies = $test_profile->get_external_dependencies();
				foreach(array('default', 'sandybridge', 'skylake', 'tigerlake', 'cascadelake -mprefer-vector-width=512', 'sapphirerapids -mprefer-vector-width=512', 'alderlake', 'znver2', 'znver3') as $march)
				{
					// So for any compiling tasks they will try to use the most aggressive instructions possible
					if($march != 'default')
					{
						if(!in_array('build-utilities', $external_dependencies))
						{
							continue;
						}
						putenv('CFLAGS= -march=' . $march . ' -O3 ');
						putenv('CXXFLAGS= -march=' . $march . ' -O3 ');
					}
					else
					{
						putenv('CFLAGS');
						putenv('CXXFLAGS');
					}
					if(($x = strpos($march, ' ')) !== false)
					{
						$march = substr($march, 0, $x);
					}
					pts_test_installer::standard_install($qualified_identifier, true);
					if($test_profile->test_installation && $test_profile->test_installation->is_installed())
					{
						$iu = array();
						self::analyze_binary_instruction_usage($test_binary, $iu);

						foreach($libraries_found_in_test_dir as $lib_check)
						{
							if(is_file($lib_check))
							{
								// Scan locally built libs too
								self::analyze_binary_instruction_usage($lib_check, $iu);
							}
						}

						if($iu != null)
						{
							if($march != 'default' && $honors_cflags == false && !empty($iu) && isset($instruction_usage['default']) && $iu != $instruction_usage['default'])
							{
								$honors_cflags = true;
							}
							$instruction_usage[$march] = $iu;
						}
					}
				}
				
				echo PHP_EOL . pts_client::cli_just_bold('SHARED LIBRARIES: ') . implode(' ', $shared_library_dependencies);
				echo PHP_EOL . pts_client::cli_just_bold('INSTRUCTION USE: ') . PHP_EOL;
				$table = array();
				foreach($instruction_usage as $target => $values)
				{
					$table[] = array_merge(array('   ' . pts_client::cli_just_bold($target . ': ')), array_keys($values));
				}
				echo pts_user_io::display_text_table($table);
				echo PHP_EOL . pts_client::cli_just_bold('HONORS FLAGS: ') . ($honors_cflags ? 'YES' : 'NO') . PHP_EOL;

				if(pts_openbenchmarking_client::user_name() != false)
				{
					$server_response = pts_openbenchmarking::make_openbenchmarking_request('upload_test_meta', array(
						'i' => $test_profile->get_identifier(),
						'ldd_libraries' => implode(',', $shared_library_dependencies),
						'instruction_set_usage' => base64_encode(json_encode($instruction_usage)),
						'honors_cflags' => ($honors_cflags ? 1 : 0)
						));
						//var_dump($server_response);
						$json = json_decode($server_response, true);
				}
			}
			else
			{
				echo PHP_EOL . $test_binary;
				echo PHP_EOL . 'Test binary could not be found.' . PHP_EOL;
		//		return false;
			}
		}

	}
	public static function locate_test_profile_lead_binary(&$test_profile)
	{
		$test_profile_launcher = $test_profile->get_test_executable_dir() . $test_profile->get_test_executable();

		if(!is_file($test_profile_launcher))
		{
			echo PHP_EOL . $test_profile_launcher . ' not found.' . PHP_EOL;
			return false;
		}
		$original_launcher_contents = file_get_contents($test_profile_launcher);
		$test_binary = false;

		if(($s = strpos($original_launcher_contents, '$LOG_FILE')))
		{
			$launcher_contents = substr($original_launcher_contents, 0, $s);
			$tline = trim(str_replace(array('	', '   ', 'mpirun', 'mpiexec', './'), '', substr($launcher_contents, strrpos($launcher_contents, PHP_EOL) + 1)));
			$test_binary = pts_strings::first_in_string($tline);
			if(strpos($test_binary, '=') !== false)
			{
				// Likely an env var being set first, so go to 2nd word
				$test_binary = pts_strings::first_in_string(trim(str_replace($test_binary, '', $tline)));
			}
			
			if($test_binary && substr($test_binary, 0, 1) == '-')
			{
				$tline = substr($launcher_contents, strrpos($launcher_contents, PHP_EOL) + 1);
				if(strpos($tline, 'mpirun') !== false || strpos($tline, 'mpiexec') !== false)
				{
					$tline = trim(str_replace(array('	', '   ', 'mpirun', 'mpiexec', './'), '', $tline));
					foreach(explode(' ', $tline) as $possible_cmd)
					{
						if(substr($possible_cmd, 0, 1) == '-')
						{
							continue;
						}

						if(is_executable(($cmd = $test_profile->get_test_executable_dir() . $test_binary)) || ($cmd = pts_client::executable_in_path($possible_cmd)) || ($cmd = self::recursively_find_file($test_profile->get_test_executable_dir(), $possible_cmd)))
						{
							$test_binary = $cmd;
							break;
						}
						else if(($cmd = self::recursively_find_file($test_profile->get_test_executable_dir(), basename($possible_cmd))))
						{
							$test_binary = $cmd;
							break;
						}
					}
				}
			}
		}
		else if($s = strpos($original_launcher_contents, './'))
		{
			$launcher_contents = $original_launcher_contents;
			$test_binary = substr($original_launcher_contents, ($s + 2));
			$test_binary = substr($test_binary, 0, strpos($test_binary, ' '));
		}
		
		if($test_binary == 'echo' && ($s = strpos($original_launcher_contents, '$LOG_FILE')))
		{
			$launcher_contents = substr($original_launcher_contents, 0, $s);
			$exec_line = trim(str_replace(array('	', '   ', 'mpirun', 'mpiexec', './'), '', substr($launcher_contents, strrpos($launcher_contents, PHP_EOL) + 1)));
			if(($x = strpos($exec_line, '| ')) !== false)
			{
				$exec_line = substr($exec_line, ($x + 2));
			}
			$test_binary = pts_strings::first_in_string($exec_line);
		}

		if(strpos($test_binary, '.app') && strpos($original_launcher_contents, '$LOG_FILE') != ($s = strrpos($original_launcher_contents, '$LOG_FILE')))
		{
			$launcher_contents = substr($original_launcher_contents, 0, $s);
			$test_binary = pts_strings::first_in_string(trim(str_replace(array('	', '   ', 'mpirun', 'mpiexec', './'), '', substr($launcher_contents, strrpos($launcher_contents, PHP_EOL) + 1))));
		}

		if($test_binary)
		{
			if(is_executable($test_profile->get_test_executable_dir() . $test_binary) && $test_profile->get_test_executable_dir() . $test_binary != $test_profile_launcher)
			{
				$test_binary = $test_profile->get_test_executable_dir() . $test_binary;
			}
			else if(($s = strpos($launcher_contents, PHP_EOL . 'cd ')))
			{
				$cd = (substr($launcher_contents, ($s + 4)));
				$cd = substr($cd, 0, strpos($cd, PHP_EOL));

				if(is_executable($test_profile->get_test_executable_dir() . '/' . $cd . '/' . $test_binary))
				{
					$test_binary = $test_profile->get_test_executable_dir() . '/' . $cd . '/' . $test_binary;
				}
			}
			else if(($e = pts_client::executable_in_path($test_binary)))
			{
				$test_binary = $e;
			}

			if($test_binary != null && !is_executable($test_binary))
			{
				// Helping qe and others that use ../ relative path handling not handled by above code, just scan directory for matching binary name...
				$basename_binary = basename($test_binary);
				$search_binary = self::recursively_find_file($test_profile->get_test_executable_dir(), $basename_binary);
				if($search_binary)
				{
					$test_binary = $search_binary;
				}
			}
			
			//var_dump($test_binary);
		}

		return $test_binary;
	}
	public static function recursively_search_text_files_in_dir($object, &$search_for, &$hits)
	{
		if(is_dir($object))
		{
			$object = pts_strings::add_trailing_slash($object);
		}

		foreach(pts_file_io::glob($object . '*') as $to_read)
		{
			if(pts_file_io::is_text_file($to_read))
			{
				$to_read_contents = file_get_contents($to_read);
				foreach($search_for as $search => $report)
				{
					if(strpos($to_read_contents, $search) !== false)
					{
						if(!isset($hits[$to_read]))
						{
							$hits[$to_read][] = $report;
						}
					}
				}
				
			}
			else if(is_dir($to_read))
			{
				self::recursively_search_text_files_in_dir($to_read, $search_for, $hits);
			}
		}
	}
	public static function recursively_find_file($search_in, $search_for, $executable_check = true)
	{
		foreach(pts_file_io::glob($search_in . '*') as $to_read)
		{
			if(is_file($to_read) && (!$executable_check || is_executable($to_read)) && basename($to_read) == $search_for)
			{
				return $to_read;
				
			}
			else if(is_dir($to_read))
			{
				$found = self::recursively_find_file($to_read . '/', $search_for, $executable_check);
				if($found)
				{
					return $found;
				}
			}
		}
		
		return false;
	}
	public static function analyze_binary_instruction_usage(&$binary, &$instruction_usage = null)
	{
		// Based on data from https://github.com/dirtyepic/scripts/blob/master/analyze-x86
		$instruction_checks = phodevi_cpu::interesting_instructions();
		
		foreach($instruction_checks as $set => &$instructions)
		{
			if(!is_array($instructions))
			{
				$instructions = explode(' ', trim($instructions));
			}
			$instructions = array_map('trim', $instructions);
			$instructions = array_map('strtolower', $instructions);
		}

		foreach(array_keys($instruction_checks) as $set)
		{
			if(!isset($instruction_usage[$set]))
			{
				$instruction_usage[$set] = array();
			}
		}
		
		$objdump_file =  tempnam('/tmp', 'objdump');
		shell_exec('objdump -d ' . $binary . ' | cut -f3 | cut -d\' \' -f1 > ' . $objdump_file);

		$handle = fopen($objdump_file, 'r');
		while(($instruction = fgets($handle)) !== false)
		{
			$matched_instruction = false;
			foreach($instruction_checks as $set => $instructions)
			{
				$instr = trim(strtolower($instruction));
				if($instr != null && in_array($instr, $instructions))
				{
					if(!in_array($instr, $instruction_usage[$set]))
					{
						$instruction_usage[$set][] = $instr;
					}
					$matched_instruction = true;
					break;
				}
			}

			if($matched_instruction == false)
			{
				//$instruction_usage['OTHER'] += 1;
			}
		}

		// Look for zmm register use to also find AVX-512 use by a binary
		$zmm_usage = shell_exec('objdump -d ' . $binary . ' 2>1 | grep %zmm0');
		if(!empty($zmm_usage) && strpos($zmm_usage, 'zmm0') !== false)
		{
			$instruction_usage['AVX512'][] = '(zmm register use)';
		}

		fclose($handle);
		unlink($objdump_file);
		foreach($instruction_usage as $instruction => $usage)
		{
			if(!is_array($instruction_usage[$instruction]) || empty($instruction_usage[$instruction]))
			{
				unset($instruction_usage[$instruction]);
			}
		}
	}
}

?>
