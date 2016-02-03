<?php

/*
	Copyright (C) 2012, ESET s.r.o. - Linux/Mac test lab

	Modified in 2015 by Jakub Maleszewski to make the code compatible
	with improved monitoring subsystem.
 */


class network_usage extends phodevi_sensor
{
	const SENSOR_TYPE = 'network';
	const SENSOR_SENSES = 'usage';
	const SENSOR_UNIT = 'Kilobytes/second';

	private $interface_to_monitor = NULL;

	function __construct($instance, $parameter)
	{
		parent::__construct($instance, $parameter);

		if($parameter !== NULL)
		{
			$this->interface_to_monitor = $parameter;
		}
		else if(self::get_supported_devices() != null)
		{
			$ifaces = self::get_supported_devices();
			$this->interface_to_monitor = $ifaces[0];
		}
	}
	public static function parameter_check($parameter)
	{
		if($parameter === null || in_array($parameter, self::get_supported_devices() ) )
		{
			return true;
		}

		return false;
	}
	public function get_readable_device_name()
	{
		return $this->interface_to_monitor;
	}

	public static function get_supported_devices()
	{
		if(phodevi::is_linux())
		{
			//TODO write network_usage_linux function
//			$iface_list = shell_exec("ls -1 /sys/class/net | grep -v lo");
//			$iface_array = explode("\n", $iface_list);
//
//			return $iface_array;
			return NULL;
		}
		if(phodevi::is_bsd() || phodevi::is_macosx())
		{
			$iface_list = shell_exec("ifconfig -lu | tr ' ' '\n' | grep -v 'lo0'");
			$iface_array = pts_strings::trim_explode(" ", $iface_list);

			return $iface_array;
		}

		return NULL;
	}
	public function read_sensor()
	{
		$net_speed = -1;

		if(phodevi::is_bsd() || phodevi::is_macosx())
		{
			$net_speed = $this->network_usage_bsd();
		}

		return pts_math::set_precision($net_speed, 2);
	}
	private function network_usage_bsd()
	{
		$net_speed = -1;
		$counter_old = self::net_counter_bsd($this->interface_to_monitor);
		$timestamp_old = time();

		sleep(1);

		$counter_new = self::net_counter_bsd($this->interface_to_monitor);
		$timestamp_new = time();
		$net_speed = (($counter_new - $counter_old) >> 10) / ($timestamp_new - $timestamp_old);

		return $net_speed;
	}
	private static function net_counter_bsd($IFACE = 'en0')
	{
		$net_counter = -1;
		if(pts_client::executable_in_path('netstat') != false)
		{
			$netstat_lines = explode("\n", shell_exec('netstat -ib 2>&1'));
			$ibytes_index = -1;
			$obytes_index = -1;
			$ibytes = -1;
			$obytes = -1;

			foreach($netstat_lines as $line)
			{
				if(strtok($line, ' ') == 'Name')
				{
					$ibytes_index = strpos($line, 'Ierrs') + 6;
					$obytes_index = strpos($line, 'Oerrs') + 6;
					continue;
				}
				//$netstat_data = pts_strings::trim_explode(' ', $line);
				/*
				 * Sample output:
				 * Name  Mtu   Network       Address            Ipkts Ierrs     Ibytes    Opkts Oerrs     Obytes  Coll
				 * en0   1500  <Link#4>    00:25:4b:c5:95:66    23350     0   19111634    13494     0    1632167     0
				 */

				if(strtok($line, ' ') == $IFACE)
				{
					$ibytes = strtok(substr($line, $ibytes_index), ' ');
					$obytes = strtok(substr($line, $obytes_index), ' ');
					$net_counter = $ibytes + $obytes;
				}
				if($ibytes != -1 && $obytes != -1)
					break;
			}
		}
		return $net_counter;
	}
}

?>
