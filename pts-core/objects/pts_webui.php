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

class pts_webui
{
	public static function load_web_interface($interface, $PATH, $page_class_location, $html_class_loation = 'html/')
	{
		if(!class_exists($interface) && is_file($page_class_location . $interface . '.php'))
		{
			require($page_class_location . $interface . '.php');

			$response = $interface::preload($PATH);

			if($response === true)
			{
				return $interface;
			}
			else if($response === false)
			{
				return false;
			}
			else
			{
				return self::load_web_interface($response, $PATH, $page_class_location, $html_class_loation);
			}
		}
		else if(is_file($html_class_loation . $interface . '.html'))
		{
			return $interface;
		}

		return false;
	}
	public static function r2d_array_to_table(&$r2d, $width = '100%')
	{
		$ret = '<table width="' . $width . ';">';
		foreach($r2d as $row => $tr)
		{
			if(!is_array($tr) && !is_numeric($row))
			{
				$tr = array($row, $tr);
			}

			$ret .= '<tr>';
			if(count($tr) == 1)
			{
				$ret .= '<th colspan="2" style="text-align: center;">' . $tr[0] . '</th>';
			}
			else
			{
				foreach($tr as $col_i => $col)
				{
					$type = $col_i == 0 ? 'th' : 'td';
					$ret .= '<' . $type . '>' . $col . '</' . $type . '>';
				}
			}
			$ret .= '</tr>';
		}
		$ret .= '</table>';

		return $ret;
	}
	public static function r1d_array_to_table(&$r1d)
	{
		echo '<table width="100%;">';
		foreach($r1d as $i => $td)
		{
			echo '<tr>';
			$type = $i == 0 ? 'th' : 'td';
			echo '<' . $type . ' style="text-align: center;">' . $td . '</' . $type . '>';
			echo '</tr>';
		}
		echo '</table>';
	}
	public static function websocket_setup_defines()
	{
		$pts_ws_port = getenv('PTS_WEBSOCKET_PORT');

		// http://www.phoronix.com/forums/showthread.php?102512-Remote-gui-not-accessible-in-Phoronix-Test-Suite-5-2&p=430312#post430312
		if(!isset($_SERVER['SERVER_ADDR']))
		{
			$_SERVER['SERVER_ADDR'] = gethostbyname(gethostname());
		}

		if($_SERVER['SERVER_ADDR'] === '::1')
		{
			$server_address = 'localhost';
		}
		else
		{
			$server_address = $_SERVER['SERVER_ADDR'];
		}

		define('PTS_WEBSOCKET_SERVER', 'ws://' . $server_address . ':' . $pts_ws_port . '/');
		setcookie('pts_websocket_server', PTS_WEBSOCKET_SERVER, (time() + 60 * 60 * 24), '/');
	}
}

?>
