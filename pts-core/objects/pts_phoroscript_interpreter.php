<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2010, Phoronix Media
	Copyright (C) 2009 - 2010, Michael Larabel

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

class pts_phoroscript_interpreter
{
	private $script_file;
	private $environmental_variables;
	private $var_current_directory;

	public function __construct($script, $env_vars = null, $set_current_path = null)
	{
		if(!isset($env_vars["HOME"]))
		{
			$env_vars["HOME"] = $set_current_path;
		}

		$this->environmental_variables = $env_vars;
		$this->script_file = is_file($script) ? $script : null;
		$this->var_current_directory = $set_current_path;
	}
	protected function get_real_path($path)
	{
		return $this->var_current_directory . $path . '/';
	}
	protected function find_file_in_array(&$string_array)
	{
		$found_file = false;

		foreach($string_array as $segment)
		{
			if(is_file($segment))
			{
				$found_file = $segment;
				break;
			}
			else if(is_file($this->var_current_directory . $segment))
			{
				$found_file = $this->var_current_directory . $segment;
				break;
			}
		}

		return $found_file;
	}
	public function execute_script($pass_arguments = null)
	{
		if($this->script_file == null)
		{
			return false;
		}

		$script_contents = file_get_contents($this->script_file);
		$prev_exit_status = 0;
		$script_pointer = -1;

		do
		{
			$exit_status = 0;

			if($prev_exit_status != 0)
			{
				$exit_status = $prev_exit_status;
				$prev_exit_status = 0;
			}

			$script_contents = substr($script_contents, ($script_pointer + 1));
			$line = $script_contents;
			$prev_script_pointer = $script_pointer;

			if(($script_pointer = strpos($line, "\n")) !== false)
			{
				$line = substr($line, 0, $script_pointer);
			}

			$line_r = pts_trim_explode(' ', $line);

			switch($line_r[0])
			{
				case 'mv':
				case 'cp':
					// TODO: implement folder support better and glob support
					$line_r[2] = $this->get_real_path($line_r[2]);
					$line_r[1] = $this->get_real_path($line_r[1]);

					if(is_file($line_r[2]))
					{
						unlink($line_r[2]);
					}

					copy($line_r[1], $line_r[2]);

					if($line_r[0] == 'mv')
					{
						unlink($line_r[1]);
					}
					break;
				case 'cd':
					if($line_r[1] == '..')
					{
						if(substr($this->var_current_directory, -1) == '/')
						{
							$this->var_current_directory = substr($this->var_current_directory, 0, -1);
						}

						$this->var_current_directory = substr($this->var_current_directory, 0, strrpos($this->var_current_directory, '/') + 1);
					}
					else if($line_r[1] == '~')
					{
						$this->var_current_directory = $this->environmental_variables["HOME"];
					}
					else if(is_readable($line_r[1]))
					{
						$this->var_current_directory = $line_r[1];
					}
					else if(is_readable($this->get_real_path($line_r[1])))
					{
						$this->var_current_directory = $this->get_real_path($line_r[1]);
					}
					break;
				case 'touch':
					if(!is_file($this->var_current_directory . $line_r[1]) && is_writable($this->var_current_directory))
					{
						touch($this->var_current_directory . $line_r[1]);
					}
					break;
				case 'mkdir':
					pts_mkdir($this->var_current_directory . $line_r[1]);
					break;
				case 'rm':
					for($i = 1; $i < count($line_r); $i++)
					{
						if(is_file($this->var_current_directory . $line_r[$i]))
						{
							unlink($this->var_current_directory . $line_r[$i]);
						}
						else if(is_dir($this->var_current_directory . $line_r[$i]))
						{
							pts_remove($this->var_current_directory . $line_r[$i], null, true);
						}
					}
					break;
				case 'chmod':
					$chmod_file = self::find_file_in_array($line_r);

					if($chmod_file)
					{
						chmod($chmod_file, 0755);
					}
					break;
				case 'unzip':
					$zip_file = self::find_file_in_array($line_r);
					pts_zip_archive_extract($zip_file, $this->var_current_directory);
					break;
				case 'tar':
					// TODO: implement, i.e. tar -xvf ../../openarena-benchmark-files-4.tar.gz
					break;
				case 'echo':
					if($line == "echo $? > ~/install-exit-status")
					{
						file_put_contents($this->var_current_directory . "install-exit-status", $exit_status);
						break;
					}
					else if($line == "echo $? > ~/test-exit-status")
					{
						file_put_contents($this->var_current_directory . "test-exit-status", $exit_status);
						break;
					}

					$start_echo = strpos($script_contents, "\"") + 1;

					do
					{
						$end_echo = strpos($script_contents, "\"", $start_echo);
					}
					while($script_contents[($end_echo - 1)] == "\\");

					$script_pointer = strpos($script_contents, "\n", $end_echo);
					$line_remainder = substr($script_contents, ($end_echo + 1), ($script_pointer - $end_echo - 1));
					$echo_contents = substr($script_contents, $start_echo, ($end_echo - $start_echo));

					if(($to_file = strpos($line_remainder, ' > ')) !== false)
					{
						$to_file = trim(substr($line_remainder, $to_file + 3));

						if(($end_file = strpos($to_file, ' ')) !== false)
						{
							$to_file = substr($to_file, 0, $end_file);
						}

						// TODO: right now it's expecting the file location pipe to be relative location
						$echo_contents = str_replace("\\$", "\$", $echo_contents);
						file_put_contents($this->var_current_directory . $to_file, $echo_contents);
					}
					else
					{
						echo $echo_contents;
					}
					break;
				case '#!/bin/sh':
				case '#':
				case null:
					// IGNORE
					break;
				case 'case':
					echo "\nUNHANDLED EVENT\n";
					return false;
					// TODO: decide how to handle
					break;
				default:
					$exec_output = array();
					exec("cd " . $this->var_current_directory . "; " . $line . " 2>&1", $exec_output, $prev_exit_status);
					break;
			}
		}
		while($script_contents != false);
	}
}

?>
