<?php

// Copyright (C) 2012, ESET s.r.o. - Linux/Mac test lab

class network_usage implements phodevi_sensor
{
	private static $timestamp_old = -1;
	private static $counter_old = -1;

	public static function get_type()
	{
		return 'network';
	}
	public static function get_sensor()
	{
		return 'usage';
	}
	public static function get_unit()
	{
		return 'Kilobytes/second';
	}
	public static function support_check()
	{
		$test = self::read_sensor();
		return is_numeric($test) && $test != -1;
	}
	private static function net_counter($IFACE = 'en0')
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
	public static function read_sensor()
	{
		//chosen iterface - redo this if parameterized sensors become possible
		$iface = 'en0';
		$net_speed = -1;

		if(self::$timestamp_old = -1)
		{
			self::$counter_old = self::net_counter($iface);
			self::$timestamp_old = time();
			sleep(1);
		}

		$counter_new = self::net_counter($iface);
		$timestamp_new = time();
		$net_speed = (($counter_new - self::$counter_old) >> 10) / ($timestamp_new - self::$timestamp_old);
		return $net_speed;
	}
}
?>
