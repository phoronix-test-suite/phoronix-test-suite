<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2013, Phoronix Media
	Copyright (C) 2013, Michael Larabel

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


class pts_webui_system implements pts_webui_interface
{
	public static function page_title()
	{
		return 'System';
	}
	public static function page_header()
	{
		return null;
	}
	public static function preload($PAGE)
	{
		return true;
	}
	public static function render_page_process($PATH)
	{
		$component_modal = array(
			'CPU' => array(
				phodevi::read_property('cpu', 'model'),
				phodevi::read_property('cpu', 'core-count') . ' Logical Cores - ' . phodevi::read_property('cpu', 'default-frequency') . ' GHz'),
			'Motherboard' => array(
				phodevi::read_property('motherboard', 'identifier'),
				phodevi::read_property('chipset', 'identifier')
				),
			'Memory' => array(
				phodevi::read_property('memory', 'identifier'),
				null
				),
			'Disk' => array(
				phodevi::read_property('disk', 'identifier'),
				phodevi::read_property('disk', 'scheduler'),
				),
			'Graphics' => array(
				phodevi::read_property('gpu', 'model'),
				phodevi::read_property('gpu', 'frequency') . ' - ' . phodevi::read_property('monitor', 'identifier')
				)
		);
		echo '<div style="overflow: hidden; text-align: center; height: inherit; vertical-align: center; margin: auto auto;">';
		foreach($component_modal as $component)
		{
			echo '<div class="pts_system_component_bar"><h1>' . $component[0] . '</h1><p>' . $component[1] . '</p></div>';
		}
		echo '</div>';

		echo '<div id="large_svg_graphs" style="margin: 10px 0; text-align: center;"></div>';
		echo '<div id="system_log_viewer"><select id="log_viewer_selector" onchange="javascript:log_viewer_change(); return true;"></select><div id="system_log_display"></div></div>';

		echo '<script text="text/javascript">
			pts_web_socket.submit_event("available-system-logs", "available_system_logs", "update_system_log_viewer");
			pts_web_socket.submit_event("user-large-svg-system-graphs", "large_svg_graphs", "update_large_svg_graph_space");
			setInterval(function(){if(pts_web_socket.is_connected()) { pts_web_socket.send("user-large-svg-system-graphs"); }},1000);
		</script>';

	}
}

?>
