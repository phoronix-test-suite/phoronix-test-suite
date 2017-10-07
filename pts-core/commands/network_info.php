<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2016, Phoronix Media
	Copyright (C) 2016, Michael Larabel

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

class network_info implements pts_option_interface
{
	const doc_section = 'User Configuration';
	const doc_description = 'This option will print information detected by the Phoronix Test Suite around the system\'s network configuration.';

	public static function run($r)
	{
		$table = array();
		$table[] = array('Local IP:', pts_network::get_local_ip());
		$table[] = array('Interface:', pts_network::get_active_network_interface());
		$table[] = array('Network MAC: ', pts_network::get_network_mac());
		$table[] = array('Wake On LAN: ', implode(' ', pts_network::get_network_wol()));

		if(pts_network::get_network_proxy() != false)
		{
			foreach(pts_network::get_network_proxy() as $item => $val)
				$table[] = array('Proxy ' . $item, $val);
		}
		$table[] = array('Can Reach Phoronix-Test-Suite.com:', pts_network::http_get_contents('http://www.phoronix-test-suite.com/PTS') == 'PTS' ? 'YES' : 'NO');
		$table[] = array('Can Reach OpenBenchmarking.org:', pts_network::http_get_contents('http://openbenchmarking.org/PTS') == 'PTS' ? 'YES' : 'NO');
		echo PHP_EOL . pts_user_io::display_text_table($table, null, 0) . PHP_EOL;
	}
}

?>
