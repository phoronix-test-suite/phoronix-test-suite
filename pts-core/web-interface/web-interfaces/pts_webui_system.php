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
	/*	list($area_width, $area_height) = phodevi::read_property('gpu', 'screen-resolution');
		$area_width = round($area_width / 2);
		$area_height = round($area_height / 2);
		var_dump(phodevi::system_hardware());


		$svg_dom = new pts_svg_dom($area_width, $area_height);
		$svg_dom->add_element('rect', array('x' => 50, 'y' => 0, 'width' => 100, 'height' => 100, 'fill' => '#000000'));
		$output_type = 'SVG';
		$graph = $svg_dom->output(null, $output_type);
		echo substr($graph, strpos($graph, '<svg')); */

		echo '<script text="text/javascript">
			var socket_connected = 0;

			function web_socket_connect()
			{
				socket = new WebSocket("' . PTS_WEBSOCKET_SERVER . 'main");
				socket.onopen    = function(msg){ socket_connected = 1; socket.send("user-large-svg-system-graphs");  setInterval(function(){if(socket_connected == 1) { socket.send("user-large-svg-system-graphs"); }},1000); };
				socket.onmessage = function(msg){ var j = JSON.parse(msg.data); if(j.pts.element.name == "large_svg_graphs") { document.getElementById("large_svg_graphs").innerHTML = j.pts.element.contents; } };
				socket.onclose   = function(msg){ socket_connected = 0; };
				return false;
			}
			web_socket_connect();

		</script>';

	}
}

?>
