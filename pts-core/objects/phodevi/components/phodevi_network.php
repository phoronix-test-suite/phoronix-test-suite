<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2016 - 2021, Phoronix Media
	Copyright (C) 2016 - 2021, Michael Larabel
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
			'identifier' => new phodevi_device_property('network_device_string', phodevi::smart_caching),
			'mac-address' => new phodevi_device_property('get_mac_address', phodevi::std_caching),
			'ip' => new phodevi_device_property('get_ip', phodevi::std_caching),
			'active-network-interface' => new phodevi_device_property('get_active_network_interface', phodevi::std_caching),
			);
	}
	public static function get_active_network_interface()
	{
		$dev = '';

		// try and get the device with the default route
		if ($ip = pts_client::executable_in_path('ip'))
		{
			$out = shell_exec("$ip route 2>&1");
			if(!empty($out))
			{
				$start = strpos($out, ' dev ');
				if($start !== false)
				{
					$start += 5; // length of ' dev '
					if(($xx = strpos($out, ' ', $start)) !== false)
					{
						$dev = substr($out, $start, $xx - $start);
					}
				}
			}
		}

		// we grab the last field of the `netstat -nr` output, betting on *bsd not expiring it's default route
		if(empty($dev) && $netstat = pts_client::executable_in_path('netstat')) {
			$out = shell_exec("$netstat -rn 2>&1");
			$lines = explode("\n", $out);
			foreach ($lines as $line) {
				$start = substr($line,0,7);
				if ($start == '0.0.0.0' || $start === 'default') {
					$dev = trim(substr(trim($line),strrpos($line,' ')));
					return $dev;
				}
			}
		}
		return $dev;
	}
	public static function get_ip()
	{
		$local_ip = false;
		$interface = phodevi::read_property('network', 'active-network-interface');

		if(($ifconfig = pts_client::executable_in_path('ifconfig')))
		{
			$ifconfig = shell_exec($ifconfig . " $interface 2>&1");
			$offset = 0;
			while(($ipv4_pos = strpos($ifconfig, 'inet addr:', $offset)) !== false)
			{
				$ipv4 = substr($ifconfig, $ipv4_pos + strlen('inet addr:'));
				$ipv4 = substr($ipv4, 0, strpos($ipv4, ' '));
				$local_ip = $ipv4;

				if($local_ip != '127.0.0.1' && $local_ip != null)
				{
					break;
				}
				$offset = $ipv4_pos + 1;
			}
			if($local_ip == null)
			{
				while(($ipv4_pos = strpos($ifconfig, 'inet ', $offset)) !== false)
				{
					$ipv4 = substr($ifconfig, $ipv4_pos + strlen('inet '));
					$ipv4 = substr($ipv4, 0, strpos($ipv4, ' '));
					$local_ip = $ipv4;

					if($local_ip != '127.0.0.1' && $local_ip != null)
					{
						if(strpos($local_ip, ' ') === false && ($x = strpos($local_ip, '/')) !== false)
						{
							// NetBSD reporting
							$local_ip = substr($local_ip, 0, $x);
						}
						break;
					}
					$offset = $ipv4_pos + 1;
				}
			}
		}
		else if(phodevi::is_windows())
		{
			$ipconfig = shell_exec('ipconfig');
			$offset = 0;

			while(($ipv4_pos = strpos($ipconfig, 'IPv4 Address.', $offset)) !== false)
			{
				$ipv4 = substr($ipconfig, $ipv4_pos);
				$ipv4 = substr($ipv4, strpos($ipv4, ': ') + 2);
				$ipv4 = substr($ipv4, 0, strpos($ipv4, "\n"));
				$local_ip = trim($ipv4);

				if($local_ip != '127.0.0.1' && $local_ip != null && strpos($local_ip, '169.254') === false)
				{
					break;
				}
				$offset = $ipv4_pos + 3;
			}
		}
		else if(pts_client::executable_in_path('hostname'))
		{
			$hostname_i = explode(' ', trim(shell_exec('hostname -I 2>&1')));
			$hostname_i = array_shift($hostname_i);
			if(count(explode('.', $hostname_i)) == 4)
			{
				$local_ip = $hostname_i;
			}
		}

		if(empty($local_ip) && function_exists('net_get_interfaces'))
		{
			// The below code should work as of net_get_interfaces() as of PHP 7.3 in cross-platform manner
			$net_interfaces = net_get_interfaces();
			foreach($net_interfaces as $interface => $interface_info)
			{
				if(isset($interface_info['unicast'][1]['address']) && !empty($interface_info['unicast'][1]['address']) && $interface_info['unicast'][1]['address'] != '127.0.0.1')
				{
					$local_ip = $interface_info['unicast'][1]['address'];
					break;
				}
			}
		}

		return $local_ip;
	}
	public static function get_mac_address()
	{
		$mac = false;

		if(phodevi::is_linux())
		{
			if($interface = phodevi::read_property('network', 'active-network-interface'))
			{
				$addr =  "/sys/class/net/$interface/address";
				if(is_file($addr))
				{
					$mac = pts_file_io::file_get_contents($addr);
				}
			}

			if(empty($mac))
			{
				foreach(pts_file_io::glob('/sys/class/net/*/operstate') as $net_device_state)
				{
					if(pts_file_io::file_get_contents($net_device_state) == 'up')
					{
						$addr = dirname($net_device_state) . '/address';
						if(is_file($addr))
						{
							$mac = pts_file_io::file_get_contents($addr);
							break;
						}
					}
				}
			}
		}
		else if(phodevi::is_windows())
		{
			$getmac = shell_exec('getmac');
			$getmac = trim(substr($getmac, strpos($getmac, "\n", strpos($getmac, '======='))));
			$getmac = substr($getmac, 0, strpos($getmac, ' '));
			if(strlen($getmac) <= 17)
			{
				$mac = str_replace('-', ':', $getmac);
			}
		}

		if(empty($mac) && ($ifconfig = pts_client::executable_in_path('ifconfig')))
		{
			$ifconfig = shell_exec($ifconfig . ' 2>&1');
			$offset = 0;
			while(($hwaddr_pos = strpos($ifconfig, 'HWaddr ', $offset)) !== false || ($hwaddr_pos = strpos($ifconfig, 'ether ', $offset)) !== false)
			{
				$hw_addr = substr($ifconfig, $hwaddr_pos);
				$hw_addr = substr($hw_addr, (strpos($hw_addr, ' ') + 1));
				$hw_addr = substr($hw_addr, 0, strpos($hw_addr, ' '));
				if(($x = strpos($hw_addr, PHP_EOL)) != false)
				{
					$hw_addr = substr($hw_addr, 0, $x);
				}

				$mac = $hw_addr;

				if($mac != null)
				{
					break;
				}
				$offset = $hwaddr_pos + 1;
			}
		}
		if(empty($mac) && ($netstat = pts_client::executable_in_path('netstat')))
		{
			// Needed on at least OpenBSD as their `ifconfig` does not expose the MAC address
			$netstat = shell_exec($netstat . ' -in 2>&1');
			foreach(explode(PHP_EOL, $netstat) as $line)
			{
				$line = explode(' ', $line);
				foreach($line as $i => $r)
				{
					if($r == null)
						unset($line[$i]);
				}
				$line = array_values($line);

				if(!isset($line[3]))
				{
					continue;
				}

				$address = explode(':', $line[3]);

				if(count($address) == 6 && $address[0] != '00' && $address[5] != '00')
				{
					foreach($address as $seg)
					{
						if(strlen($seg) != 2)
						{
							continue;
						}
					}

					$mac = $line[3];
				}
			}
		}

		return $mac;
	}
	public static function network_device_string()
	{
		$network = array();

		if(phodevi::is_macos())
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
				if(stripos($n, 'debug') !== false || stripos($n, 'pseudo') !== false  || stripos($n, ' virtual') !== false || strpos($n, 'WAN ') !== false)
				{
					unset($network[$i]);
				}
				$n = str_replace(array('(2)', '(R)'), '', $n);
			}
		}
		else if(phodevi::is_linux())
		{
			$pci = phodevi_linux_parser::read_pci_multi(array('Ethernet controller', 'Network controller'));

			if(!empty($pci))
			{
				$network = $pci;
			}
		}

		return pts_arrays::array_to_cleansed_item_string($network);
	}
}

?>
