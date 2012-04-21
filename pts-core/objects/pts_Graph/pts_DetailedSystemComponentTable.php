<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2012, Phoronix Media
	Copyright (C) 2012, Michael Larabel
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
	public function __construct(&$result_file, $log_location, $component_report, $intent = false)
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

		$this->rows = array();
		$this->columns = array();
		$this->table_data = array();
		$logs_to_capture = array();

		if($component_report == 'Processor')
		{
			$this->columns = array('Model Name', 'Core Count', 'Thread Count', 'L2 Cache', 'Cache Size', 'Virtualization', 'Features', 'Flags');
			$logs_to_capture = array('cpuinfo', 'lscpu');
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

					}
				}

				phodevi::$vfs->clear_cache();
			}
		}

		if(empty($this->rows))
		{
			return false;
		}

		if($component_report == 'Processor')
		{
			$flags_data = $this->table_data[array_search('Flags', $this->columns)];

			foreach($flags_data as $i => &$flags)
			{
				$flags = explode(' ', $flags);
				sort($flags);
			}

			$intersect = call_user_func_array('array_intersect', $flags_data);
			sort($intersect);

			foreach($flags_data as $i => &$flags)
			{
				$flags = array_diff($flags, $intersect);
				$flags = implode(' ', $flags);
			}

			$this->table_data[array_search('Flags', $this->columns)] = $flags_data;
		}

		parent::__construct($this->rows, $this->columns, $this->table_data);

		if($component_report == 'Processor' && !empty($intersect))
		{
			$this->addTestNote(implode(' ', $intersect), null, 'Common CPU Flags');
		}
	}
	protected function generate_processor_data(&$result_file, $system_identifier)
	{
		array_push($this->rows, $system_identifier);
		$rows_index = count($this->rows) - 1;

		foreach($this->columns as $i => $cpuinfo_item)
		{
			if($cpuinfo_item == 'Features')
			{
				$line = phodevi_cpu::instruction_set_extensions();
			}
			else if($cpuinfo_item == 'Core Count')
			{
				$line = phodevi_cpu::cpuinfo_core_count();
			}
			else if($cpuinfo_item == 'Thread Count')
			{
				$line = phodevi_cpu::cpuinfo_thread_count();
			}
			else if($cpuinfo_item == 'L2 Cache')
			{
				$line = phodevi_cpu::lscpu_l2_cache();
			}
			else if($cpuinfo_item == 'Virtualization')
			{
				$line = phodevi_cpu::virtualization_technology();
			}
			else
			{
				$line = phodevi_cpu::read_cpuinfo_line(strtolower($cpuinfo_item), false);
			}

			if($line)
			{
				$line = pts_strings::strip_string($line);
			}

			$this->table_data[$i][$rows_index] = $line;
		}
	}
}

?>
