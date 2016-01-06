<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2012 - 2016, Phoronix Media
	Copyright (C) 2012 - 2016, Michael Larabel
	pts_DetailedSystemComponentTable.php: The detailed system component table

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

class pts_DetailedSystemComponentTable extends pts_SideViewTable
{
	public function __construct(&$result_file, $log_location, $intent = false)
	{
		if(!is_readable($log_location))
		{
			return false;
		}
		if($intent == false)
		{
			$intent = -1;
			$intent = pts_result_file_analyzer::analyze_result_file_intent($result_file, $intent, true);
		}

		if(!isset($intent[0]))
		{
			return false;
		}

		if(is_array($intent[0]) && in_array('Processor', $intent[0]))
		{
			$component_report = 'Processor';
		}
		else if(is_array($intent[0]) && in_array('Graphics', $intent[0]))
		{
			$component_report = 'Graphics';
		}
		else
		{
			return false;
		}

		$this->rows = array();
		$this->columns = array();
		$this->table_data = array();
		$logs_to_capture = array();

		if($component_report == 'Processor')
		{
			$this->columns = array('Model Name', 'Core Count', 'Thread Count', 'L2 Cache', 'Cache Size', 'Virtualization', 'Features', 'Flags');
			$logs_to_capture = array('cpuinfo', 'lscpu');
		}
		else if($component_report == 'Graphics')
		{
			$this->columns = array('OpenGL Renderer', 'OpenGL Version', 'GLSL Version', 'OpenGL Extensions');
			$logs_to_capture = array('glxinfo');
		}

		if(is_dir($log_location))
		{
			foreach($result_file->get_system_identifiers() as $system_identifier)
			{
				phodevi::$vfs->clear_cache();
				foreach($logs_to_capture as $log_file_name)
				{
					if(is_file($log_location . $system_identifier . '/' . $log_file_name))
					{
						phodevi::$vfs->set_cache_item($log_file_name, file_get_contents($log_location . $system_identifier . '/' . $log_file_name));
					}
				}

				if(count(phodevi::$vfs->cache_index()) > 0)
				{
					switch($component_report)
					{
						case 'Processor':
							$this->generate_processor_data($result_file, $system_identifier);
							break;
						case 'Graphics':
							$this->generate_graphics_data($result_file, $system_identifier);
							break;

					}
				}

				phodevi::$vfs->clear_cache();
			}
		}

		if(empty($this->rows))
		{
			return false;
		}

		foreach(array(array('Processor', 'Flags', 'Common CPU Flags'), array('Graphics', 'OpenGL Extensions', 'Common OpenGL Extensions')) as $set)
		{
			if($component_report == $set[0])
			{
				$flags_data = $this->table_data[array_search($set[1], $this->columns)];

				foreach($flags_data as $i => &$flags)
				{
					$flags = explode(' ', $flags);
					sort($flags);
				}

				if($flags_data == null || count($flags_data) < 2)
				{
					continue;
				}

				$intersect = call_user_func_array('array_intersect', $flags_data);
				sort($intersect);

				foreach($flags_data as $i => &$flags)
				{
					$flags = array_diff($flags, $intersect);
					$flags = implode(' ', $flags);
				}

				$this->table_data[array_search($set[1], $this->columns)] = $flags_data;
				$intersect_label = $set[2];
				break;
			}
		}

		parent::__construct($this->rows, $this->columns, $this->table_data);

		if(isset($intersect) && !empty($intersect))
		{
			$this->addTestNote(trim(implode(' ', $intersect)), null, $intersect_label);
		}
	}
	protected function generate_processor_data(&$result_file, $system_identifier)
	{
		$this->rows[] = $system_identifier;
		$rows_index = count($this->rows) - 1;

		foreach($this->columns as $i => $cpuinfo_item)
		{
			switch($cpuinfo_item)
			{
				case 'Features':
					$line = phodevi_cpu::instruction_set_extensions();
					break;
				case 'Core Count':
					$line = phodevi_cpu::cpuinfo_core_count();
					break;
				case 'Thread Count':
					$line = phodevi_cpu::cpuinfo_thread_count();
					break;
				case 'L2 Cache':
					$line = phodevi_cpu::lscpu_l2_cache();
					break;
				case 'Virtualization':
					$line = phodevi_cpu::virtualization_technology();
					break;
				default:
					$line = phodevi_cpu::read_cpuinfo_line(strtolower($cpuinfo_item), false);
					break;
			}

			if($line)
			{
				$line = pts_strings::strip_string($line);
			}

			$this->table_data[$i][$rows_index] = $line;
		}
	}
	protected function generate_graphics_data(&$result_file, $system_identifier)
	{
		$this->rows[] = $system_identifier;
		$rows_index = count($this->rows) - 1;

		foreach($this->columns as $i => $cpuinfo_item)
		{
			switch($cpuinfo_item)
			{
				case 'OpenGL Renderer':
					$line = phodevi_parser::read_glx_renderer();
					break;
				case 'OpenGL Version':
					$line = phodevi_parser::software_glxinfo_version();
					break;
				case 'GLSL Version':
					$line = phodevi_parser::software_glxinfo_glsl_version();
					break;
				case 'OpenGL Extensions':
					$line = phodevi_parser::software_glxinfo_opengl_extensions();
					break;
			}

			$this->table_data[$i][$rows_index] = $line;
		}
	}
}

?>
