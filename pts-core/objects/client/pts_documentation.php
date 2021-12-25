<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2018, Phoronix Media
	Copyright (C) 2009 - 2018, Michael Larabel

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

class pts_documentation
{
	public static function client_commands_aliases()
	{
		$command_aliases = array();
		foreach(pts_file_io::glob(PTS_COMMAND_PATH . '*.php') as $option_php_file)
		{
			$option_php = basename($option_php_file, '.php');

			include_once($option_php_file);
			if(method_exists($option_php, 'command_aliases'))
			{
				$this_aliases = call_user_func(array($option_php, 'command_aliases'));

				if(is_array($this_aliases))
				{
					foreach($this_aliases as $alias)
					{
						$command_aliases[$alias] = $option_php;
					}
				}
			}
		}

		return $command_aliases;
	}
	public static function client_commands_array()
	{
		$options = array('System' => array(), 'Test Installation' => array(), 'Testing' => array(), 'Batch Testing' => array(), 'OpenBenchmarking.org' => array(), 'Information' => array(), 'Asset Creation' => array(), 'Result Management' => array(), 'Result Analytics' => array(), 'Other' => array());

		foreach(pts_file_io::glob(PTS_COMMAND_PATH . '*.php') as $option_php_file)
		{
			$option_php = basename($option_php_file, '.php');
			$name = str_replace('_', '-', $option_php);

			if(true)
			{
				include_once($option_php_file);

				$reflect = new ReflectionClass($option_php);
				$constants = $reflect->getConstants();

				$doc_description = isset($constants['doc_description']) ? constant($option_php . '::doc_description') : 'No summary is available.';
				$doc_section = isset($constants['doc_section']) ? constant($option_php . '::doc_section') : 'Other';
				$name = isset($constants['doc_use_alias']) ? constant($option_php . '::doc_use_alias') : $name;
				$skip = isset($constants['doc_skip']) ? constant($option_php . '::doc_skip') : false;
				$doc_args = array();

				if($skip)
				{
					continue;
				}

				if(method_exists($option_php, 'argument_checks'))
				{
					$doc_args = call_user_func(array($option_php, 'argument_checks'));
				}

				if(!empty($doc_section) && !isset($options[$doc_section]))
				{
					$options[$doc_section] = array();
				}

				$options[$doc_section][] = array($name, $doc_args, $doc_description);
			}
		}

		return $options;
	}
	public static function client_commands_possible_values()
	{
		static $commands_possible_values = null;

		if(empty($commands_possible_values))
		{
			foreach(pts_file_io::glob(PTS_COMMAND_PATH . '*.php') as $option_php_file)
			{
				$option_php = basename($option_php_file, '.php');
				$name = str_replace('_', '-', $option_php);

				if(!in_array(pts_strings::first_in_string($name, '-'), array('task')))
				{
					include_once($option_php_file);

					$reflect = new ReflectionClass($option_php);
					$constants = $reflect->getConstants();

					$args = null;
					if(method_exists($option_php, 'argument_checks'))
					{
						$args = call_user_func(array($option_php, 'argument_checks'));
					}
					$command_aliases = array();
					if(method_exists($option_php, 'command_aliases'))
					{
						$command_aliases = call_user_func(array($option_php, 'command_aliases'));
					}
					$command_aliases[] = $name;
					if(isset($args[0]) && $args[0] instanceof pts_argument_check)
					{
						$arg_possible_values = $args[0]->possible_values();
						foreach($command_aliases as $alias)
						{
							$commands_possible_values[$alias] = $arg_possible_values;
						}
					}
				}
			}
		}

		return $commands_possible_values;
	}
	public static function basic_description()
	{
		return 'The **Phoronix Test Suite** is the most comprehensive testing and benchmarking platform available for Linux, Solaris, macOS, Windows, and BSD operating systems. The Phoronix Test Suite allows for carrying out tests in a fully automated manner from test installation to execution and reporting. All tests are meant to be easily reproducible, easy-to-use, and support fully automated execution. The Phoronix Test Suite is open-source under the GNU GPLv3 license and is developed by Phoronix Media in cooperation with partners.';
	}
}

?>
