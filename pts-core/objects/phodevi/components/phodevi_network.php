<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2016 - 2018, Phoronix Media
	Copyright (C) 2016 - 2018, Michael Larabel
	phodevi_network.php: The PTS Device Interface object for network devices

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

class phodevi_network extends phodevi_device_interface
{
	public static function properties()
	{
		return array(
			'identifier' => new phodevi_device_property('network_device_string', phodevi::smart_caching)
			);
	}
	public static function network_device_string()
	{
		$network = array();

		if(phodevi::is_macosx())
		{
			// TODO: implement
		}
		else if(phodevi::is_bsd())
		{
			foreach(array('dev.em.0.%desc', 'dev.wpi.0.%desc', 'dev.mskc.0.%desc') as $controller)
			{
				$pci = phodevi_bsd_parser::read_sysctl($controller);

				if(!empty($pci))
				{
					array_push($network, $pci);
				}
			}
		}
		else if(phodevi::is_windows())
		{
 			$network = phodevi_windows_parser::get_wmi_object_multi('Win32_NetworkAdapter', 'Name');
			foreach($network as $i => &$n)
			{
				if(stripos($n, 'debug') !== false || stripos($n, 'pseudo') !== false || strpos($n, 'WAN ') !== false)
				{
					unset($network[$i]);
				}
				$n = str_replace(array('(2)', '(R)'), null, $n);
			}
		}
		else if(phodevi::is_linux())
		{
			foreach(array('Ethernet controller', 'Network controller') as $controller)
			{
				$pci = phodevi_linux_parser::read_pci($controller);

				if(!empty($pci))
				{
					array_push($network, $pci);
				}
			}
		}

		return implode(' + ', $network);
	}
}

?>
