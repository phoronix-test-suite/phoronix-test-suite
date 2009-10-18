<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel

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

class network_setup implements pts_option_interface
{
	public static function run($r)
	{
		echo pts_string_header("Network Setup");

		if(!pts_bool_question("Configure the Phoronix Test Suite to use a HTTP proxy (y/N)?", false))
		{
			return false;
		}

		$proxy_address = pts_text_input("Enter IP address / server name of proxy");
		$proxy_port = pts_text_input("Enter TCP port for proxy server");

		echo "\nTesting Proxy Server (" . $proxy_address . ":" . $proxy_port . ")\n";

		if(pts_http_get_contents("http://www.phoronix-test-suite.com/PTS", $proxy_address, $proxy_port) == "PTS")
		{
			echo "\nProxy Setup Completed; Storing Network Settings.\n";
			pts_user_config_init(array(P_OPTION_NET_PROXY_ADDRESS => $proxy_address, P_OPTION_NET_PROXY_PORT => $proxy_port));
		}
		else
		{
			echo "\nProxy Setup Failed.\n";
		}
	}
}

?>
