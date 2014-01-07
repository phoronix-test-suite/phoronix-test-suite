<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2013 - 2014, Phoronix Media
	Copyright (C) 2013 - 2014, Michael Larabel

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


class pts_webui_component implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Component';
	}
	public static function page_header()
	{
		return null;
	}
	public static function preload($PAGE)
	{
		return in_array($PAGE[0], array('CPU', 'GPU', 'Motherboard', 'Disk', 'Memory', 'Software')) ? true : 'pts_webui_main';
	}
	public static function render_page_process($PATH)
	{
		$COMPONENT = trim(str_replace('%20', ' ', $PATH[0]));

		switch($COMPONENT)
		{
			case 'CPU':
				$model = phodevi::read_property('cpu', 'model');
				$ob_type = 'Processor';
				$sensor_flag = 'all.cpu';
				$cpu_flags = phodevi_cpu::get_cpu_flags();
				$features = array(array('Frequency', phodevi::read_property('cpu', 'mhz-default-frequency') . ' MHz'),
					array('Core Count', phodevi_cpu::cpuinfo_core_count()),
					array('Thread Count', phodevi_cpu::cpuinfo_thread_count()),
					array('Cache Size', phodevi_cpu::cpuinfo_cache_size() . ' KB'),
					array('Instruction Set Extensions', phodevi_cpu::instruction_set_extensions()),
					array('AES Encryption', ($cpu_flags & phodevi_cpu::get_cpu_feature_constant('aes') ? 'YES' : 'NO')),
					array('Energy Performance Bias', ($cpu_flags & phodevi_cpu::get_cpu_feature_constant('epb') ? 'YES' : 'NO')),
					array('Virtualization', (phodevi_cpu::virtualization_technology() ? phodevi_cpu::virtualization_technology() : 'NO')),
					array('Scaling Governor', phodevi::read_property('cpu', 'scaling-governor'))
					);
				$software_features = array();
				break;
			case 'GPU':
				$model = phodevi::read_property('gpu', 'model');
				$ob_type = 'Graphics';
				$sensor_flag = 'all.gpu';
				$features = array(array('Frequency', implode(' / ', phodevi::read_property('gpu', 'stock-frequency')) . ' MHz'),
					array('vRAM Capacity', phodevi::read_property('gpu', 'memory-capacity') . ' MB'),
					array('Compute Cores', phodevi::read_property('gpu', 'compute-cores')),
					array('Screen Resolution', phodevi::read_property('gpu', 'screen-resolution-string')),
					array('2D Acceleration', phodevi::read_property('gpu', '2d-acceleration'))
					);
				$software_features = array(
					array('Video Driver', phodevi::read_property('system', 'display-driver-string')),
					array('OpenGL Driver', phodevi::read_property('system', 'opengl-driver')),
					array('Kernel', phodevi::read_property('system', 'kernel')),
					array('Video Drivers', phodevi::read_property('system', 'display-driver-string')),
					array('Display Server', phodevi::read_property('system', 'display-server'))
					);
				break;
			case 'Motherboard':
				$model = phodevi::read_property('motherboard', 'identifier');
				$ob_type = 'System';
				$sensor_flag = 'all.sys';
				$features = array(array('Chipset', phodevi::read_property('chipset', 'identifier')),
					array('Serial Number', phodevi::read_property('motherboard', 'serial-number')),
					array('Network', phodevi::read_property('network', 'identifier')),
					array('Audio', phodevi::read_property('audio', 'identifier'))
					);
				$software_features = array();
				break;
			case 'Disk':
				$model = phodevi::read_property('disk', 'identifier');
				$ob_type = 'Disk';
				$sensor_flag = 'all.hdd';
				$mo = phodevi::read_property('disk', 'mount-options');
				$mo = isset($mo['mount-options']) ? $mo['mount-options'] : null;
				$features = array(array('I/O Scheduler', phodevi::read_property('disk', 'scheduler')),
					array('Mount Options', $mo),
					array('File-System', phodevi::read_property('system', 'filesystem'))
					);
				$software_features = array();
				break;
			case 'Memory':
				$model = phodevi::read_property('memory', 'identifier');
				$ob_type = 'Memory';
				$sensor_flag = 'all.memory';
				$features = array();
				$software_features = array();
				break;
			case 'Software':
				$model = phodevi::read_property('system', 'operating-system');
				$ob_type = '';
				$sensor_flag = 'all.sys';
				$features = array(array('Kernel', phodevi::read_property('system', 'kernel-string')),
					array('Compiler', phodevi::read_property('system', 'compiler')),
					array('Desktop', phodevi::read_property('system', 'desktop-environment')),
					array('Display Server', phodevi::read_property('system', 'display-server')),
					array('Display Driver', phodevi::read_property('system', 'display-driver-string')),
					array('OpenGL Driver', phodevi::read_property('system', 'opengl-driver')),
					array('File-System', phodevi::read_property('system', 'filesystem')),
					array('System Layer', phodevi::read_property('system', 'system-layer')),
					);
				$software_features = array(array('Kernel Parameters', phodevi::read_property('system', 'kernel-parameters')),
					array('Hostname', phodevi::read_property('system', 'hostname')),
					array('Local IP Address', $_SERVER['HTTP_HOST'])
					);
				break;
		}

		echo '<h1>' . $model . '</h1>';

		echo '<div id="pts_side_pane" style="max-width: 30%;">';

		if(!empty($features))
		{
			echo '<h2>' . $COMPONENT . ' Features</h2>';
			echo '<ul>';
			foreach($features as $feature)
			{
				if(isset($feature[1]))
				{
					$feature[0] .= ':';

					if($feature[1] == null)
					{
						$feature[1] = 'N/A';
					}
				}
				$feature[0] = '<strong>' . $feature[0] . '</strong>';

				echo '<li>' . implode(' ', $feature) . '</li>' . PHP_EOL;
			}
			echo '</ul>';
			echo '<hr />';
		}

		if(!empty($software_features))
		{
			echo '<h2>' . $COMPONENT . ' Software Features</h2>';
			echo '<ul>';
			foreach($software_features as $feature)
			{
				if(isset($feature[1]))
				{
					$feature[0] .= ':';

					if($feature[1] == null)
					{
						$feature[1] = 'N/A';
					}
				}
				$feature[0] = '<strong>' . $feature[0] . '</strong>';

				echo '<li>' . implode(' ', $feature) . '</li>' . PHP_EOL;
			}
			echo '</ul>';
			echo '<hr />';
		}

		echo '<div class="pts_pane_window">Log-in to OpenBenchmarking.org to gain access to more functionality.</div>';

		echo '</div>';

		echo '<div id="svg_graphs" style="margin: 10px 0; text-align: right;"></div>';
		echo '<div id="tests_by_popularity" style="margin: 10px 0; text-align: left;"></div>';

		echo '<div id="system_log_viewer"><select id="log_viewer_selector" onchange="javascript:log_viewer_change(); return true;"></select><div id="system_log_display"></div></div>';

		echo '<script text="text/javascript">
			pts_web_socket.submit_event("user-svg-system-graphs ' . $sensor_flag .'", "svg_graphs", "update_svg_graph_space");
			pts_web_socket.submit_event("available-system-logs System ' . $COMPONENT . '", "available_system_logs", "update_system_log_viewer");
			pts_web_socket.submit_event("tests-by-popularity 6 ' . $ob_type .'", "tests_by_popularity", "tests_by_popularity_display");

			setInterval(function(){if(pts_web_socket.is_connected()) { pts_web_socket.send("user-svg-system-graphs ' . $sensor_flag .'"); }},1000);
		</script>';
	}
}

?>
