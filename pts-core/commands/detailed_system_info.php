<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2011 - 2015, Phoronix Media
	Copyright (C) 2011 - 2015, Michael Larabel

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

class detailed_system_info implements pts_option_interface
{
	const doc_section = 'System';
	const doc_description = 'Display detailed information about the installed system hardware and software information as detected by the Phoronix Test Suite Phodevi Library.';

	public static function run($r)
	{
		pts_client::$display->generic_heading('System Information');
		echo 'Hardware:' . PHP_EOL . phodevi::system_hardware(true) . PHP_EOL . PHP_EOL;
		echo 'Software:' . PHP_EOL . phodevi::system_software(true) . PHP_EOL . PHP_EOL;

		//
		// Processor Information
		//

		$cpu_flags = phodevi_cpu::get_cpu_flags();
		echo PHP_EOL . 'PROCESSOR:' . PHP_EOL . PHP_EOL;
		echo 'Core Count: ' . phodevi_cpu::cpuinfo_core_count() . PHP_EOL;
		echo 'Thread Count: ' . phodevi_cpu::cpuinfo_thread_count() . PHP_EOL;
		echo 'Cache Size: ' . phodevi_cpu::cpuinfo_cache_size() . ' KB' . PHP_EOL;

		echo 'Instruction Set Extensions: ' . phodevi_cpu::instruction_set_extensions() . PHP_EOL;
		echo 'AES Encryption: ' . ($cpu_flags & phodevi_cpu::get_cpu_feature_constant('aes') ? 'YES' : 'NO') . PHP_EOL;
		echo 'Energy Performance Bias: ' . ($cpu_flags & phodevi_cpu::get_cpu_feature_constant('epb') ? 'YES' : 'NO') . PHP_EOL;
		echo 'Virtualization: ' . (phodevi_cpu::virtualization_technology() ? phodevi_cpu::virtualization_technology() : 'NO') . PHP_EOL;

		// Other info
		foreach(pts_arrays::to_array(pts_test_run_manager::pull_test_notes(true)) as $test_note_head => $test_note)
		{
			echo ucwords(str_replace('-', ' ', $test_note_head)) . ': ' . $test_note . PHP_EOL;
		}
	}
}

?>
