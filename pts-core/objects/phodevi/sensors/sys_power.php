<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2024, Phoronix Media
	Copyright (C) 2009 - 2024, Michael Larabel

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

class sys_power extends phodevi_sensor
{
	const SENSOR_TYPE = 'sys';
	const SENSOR_SENSES = 'power';

	private static $battery_sys = false;
	private static $battery_cur = false;
	private static $tegra_power = false;
	private static $wattsup_meter = false;
	private static $wattsup_meter_raw = false;
	private static $ipmitool = false;
	private static $ipmitool_ps = false;
	private static $ipmitool_platform = false;
	private static $ipmitool_dcmi = false;
	private static $windows_battery = false;
	private static $hwmon_power_meter = false;

	public static function get_unit()
	{
		$unit = null;

		if(self::$battery_sys || self::$tegra_power || self::$windows_battery)
		{
			$unit = 'Milliwatts';
		}
		else if(self::$battery_cur)
		{
			$unit = 'microAmps';
		}
		else
		{
			$unit = 'Watts';
		}

		return $unit;
	}
	public function support_check()
	{
		if(phodevi::is_windows())
		{
			if(self::windows_wmi_battery_status_discharge() > 0)
			{
				self::$windows_battery = true;
				return true;
			}
			return false;
		}
		if(($m = getenv('WATTSUP_METER')) != false && is_readable($m))
		{
			$wattsup = self::watts_up_power_meter_raw($m);

			if($wattsup > 0.5 && is_numeric($wattsup))
			{
				self::$wattsup_meter_raw = $m;
				return true;
			}
		}
		if(pts_client::executable_in_path('wattsup'))
		{
			$meters = false;
			$wattsup = self::watts_up_power_meter($meters);

			if($wattsup > 0.5 && is_numeric($wattsup))
			{
				self::$wattsup_meter = $meters;
				return true;
			}
		}

		$test = self::sys_battery_power();
		if(is_numeric($test) && $test != -1 && $test > 0)
		{
			self::$battery_sys = true;
			return true;
		}

		$test = self::sys_power_current();
		if(is_numeric($test) && $test != -1)
		{
			self::$battery_cur = true;
			return true;
		}

		if(is_readable('/sys/bus/i2c/drivers/ina3221x/0-0041/iio:device1/in_power0_input'))
		{
			$in_power0_input = pts_file_io::file_get_contents('/sys/bus/i2c/drivers/ina3221x/0-0041/iio:device1/in_power0_input');
			if($in_power0_input > 1000)
			{
				self::$tegra_power = true;
				return true;
			}
		}

		$hwmon_power = phodevi_linux_parser::read_sysfs_node('/sys/class/hwmon/hwmon*/device/power*_average', 'POSITIVE_NUMERIC', array('name' => 'power_meter'), 1, true);
		if($hwmon_power != -1)
		{
			// Intel node manager -Meter measures total domain
			self::$hwmon_power_meter = $hwmon_power;
			return true;
		}
		if(pts_client::executable_in_path('ipmitool'))
		{
			// TODO add check for Platform Power A like on AvenueCity
			$ipmi_read = phodevi_linux_parser::read_ipmitool_sensor('Node Power');

			if($ipmi_read > 0 && is_numeric($ipmi_read))
			{
				self::$ipmitool = true;
				return true;
			}

			$ipmi_read = phodevi_linux_parser::read_ipmitool_dcmi_power();

			if($ipmi_read > 0 && is_numeric($ipmi_read))
			{
				self::$ipmitool_dcmi = true;
				return true;
			}

			$ipmi_ps1 = phodevi_linux_parser::read_ipmitool_sensor('PS1_Input_Power');
			//$ipmi_ps2 = phodevi_linux_parser::read_ipmitool_sensor('PS2_Input_Power');
			if(is_numeric($ipmi_ps1) && $ipmi_ps1 > 1)
			{
				self::$ipmitool_ps = true;
				return true;
			}
			
			$ipmi_ps1 = phodevi_linux_parser::read_ipmitool_sensor('PSU1 Input Power');
			//$ipmi_ps2 = phodevi_linux_parser::read_ipmitool_sensor('PS2_Input_Power');
			if(is_numeric($ipmi_ps1) && $ipmi_ps1 > 1)
			{
				self::$ipmitool_platform = true;
				return true;
			}
		}
	}
	public function read_sensor()
	{
		if(self::$battery_sys)
		{
			return self::sys_battery_power();
		}
		else if(self::$battery_cur)
		{
			return self::sys_power_current();
		}
		else if(self::$wattsup_meter_raw)
		{
			return self::watts_up_power_meter_raw(self::$wattsup_meter_raw);
		}
		else if(self::$wattsup_meter)
		{
			return self::watts_up_power_meter(self::$wattsup_meter);
		}
		else if(self::$tegra_power)
		{
			if(is_readable('/sys/bus/i2c/drivers/ina3221x/0-0041/iio:device1/in_power0_input'))
			{
				return pts_file_io::file_get_contents('/sys/bus/i2c/drivers/ina3221x/0-0041/iio:device1/in_power0_input');
			}
		}
		else if(self::$ipmitool)
		{
			return phodevi_linux_parser::read_ipmitool_sensor('Node Power');
		}
		else if(self::$ipmitool_ps)
		{
			return phodevi_linux_parser::read_ipmitool_sensor('PS1_Input_Power', 0) + phodevi_linux_parser::read_ipmitool_sensor('PS2_Input_Power', 0);
		}
		else if(self::$ipmitool_platform)
		{
			return phodevi_linux_parser::read_ipmitool_sensor('PSU1 Input Power', 0) + phodevi_linux_parser::read_ipmitool_sensor('PSU2 Input Power', 0);
		}
		else if(self::$ipmitool_dcmi)
		{
			return phodevi_linux_parser::read_ipmitool_dcmi_power();
		}
		else if(self::$windows_battery)
		{
			return self::windows_wmi_battery_status_discharge();
		}
		else if(self::$hwmon_power_meter)
		{
			$p = pts_file_io::file_get_contents(self::$hwmon_power_meter);
			if($p > 1000000)
			{
				$p = $p / 1000000;
			}
			return $p;
		}
	}
	private static function windows_wmi_battery_status_discharge()
	{
		$output = trim(shell_exec('powershell -NoProfile (Get-WmiObject -Namespace "root\wmi" BatteryStatus).DischargeRate'));
		if(!empty($output) && is_numeric($output) && $output > 0)
		{
			return $output;
		}
		else
		{
			return -1;
		}
	}
	private static function watts_up_power_meter(&$meters = false)
	{
		if($meters == false)
		{
			$meters = pts_file_io::glob('/dev/ttyUSB*');
			foreach($meters as &$meter)
			{
				$meter = basename($meter);
			}
		}

		$value = 0;
		// Allow reading multiple meters
		foreach($meters as $meter)
		{
			// Sometimes Wattsup have read failures returning 0....
			// try reading up to 3 times...
			for($i = 0; $i < 3; $i++)
			{
				$output = trim(shell_exec('wattsup -c 1 ' . $meter . ' watts 2>&1'));
				$output = explode(PHP_EOL, $output);

				do
				{
					$this_value = array_pop($output);
				}
				while(!is_numeric($this_value) && count($output) > 0);

				if(is_numeric($this_value) && $this_value > 0)
				{
					$value += $this_value;
					break;
				}
			}
			if(!is_numeric($this_value) || $this_value <= 0)
			{
				// Avoid case of one meter failing to read but other one reporting....
				return -1;
			}
		}

		return is_numeric($value) ? $value : -1;
	}
	private static function watts_up_power_meter_raw($meter)
	{
		// https://arcb.csc.ncsu.edu/~mueller/cluster/arc/wattsup/metertools-1.0.0/docs/meters/wattsup/comprotocol.pdf
		$buffer = null;
		$handle = @fopen($meter, 'r');
		if($handle)
		{
			$times = 0;
			while(($buffer = fgets($handle, 4096)) !== false)
			{
				$times++;
				if($times == 4 || strpos($buffer, '#d') !== false)
					break;
			}
			fclose($handle);
		}
		$watts = -1;
		if(strpos($buffer, '#d') !== false)
		{
			$buffer = explode(',', $buffer);
			if(isset($buffer[3]) && $buffer[3] > 10)
			{
				$watts = $buffer[3] / 10;
			}
		}

		return $watts;
	}
	private static function sys_power_current()
	{
		// Returns power consumption rate in uA
		$current = -1;

		if(phodevi::is_linux())
		{
			$raw_current = phodevi_linux_parser::read_sysfs_node('/sys/devices/w1_bus_master1/*/getcurrent', 'NO_CHECK');

			if($raw_current != -1)
			{
				if(substr($raw_current, 0, 1) == '-')
				{
					$current = substr($raw_current, 1);
				}
			}
		}
		else if(phodevi::is_macos())
		{
			$current = abs(phodevi_osx_parser::read_osx_system_profiler('SPPowerDataType', 'Amperage', false, array(), false)); // in mA
		}

		return $current;
	}
	private static function sys_battery_power()
	{
		// Returns power consumption rate in mW
		$rate = -1;

		if(phodevi::is_linux())
		{
			$power_now = phodevi_linux_parser::read_sysfs_node('/sys/class/power_supply/*/power_now', 'POSITIVE_NUMERIC', array('status' => 'Discharging'));

			if($power_now != -1)
			{
				// sysfs power_now seems to be displayed in microWatts
				$rate = pts_math::set_precision($power_now / 1000, 2);
			}

			if($rate == -1)
			{
				$battery = array('/battery/BAT0/state', '/battery/BAT1/state');
				$state = phodevi_linux_parser::read_acpi($battery, 'charging state');
				$power = phodevi_linux_parser::read_acpi($battery, 'present rate');
				$voltage = phodevi_linux_parser::read_acpi($battery, 'present voltage');

				if($state == 'discharging')
				{
					$power_unit = substr($power, strrpos($power, ' ') + 1);
					$power = substr($power, 0, strpos($power, ' '));

					if($power_unit == 'mA')
					{
						$voltage_unit = substr($voltage, strrpos($voltage, ' ') + 1);
						$voltage = substr($voltage, 0, strpos($voltage, ' '));

						if($voltage_unit == 'mV')
						{
							$rate = round(($power * $voltage) / 1000);
						}
					}
					else if($power_unit == 'mW')
					{
						$rate = $power;
					}
				}
			}

			// TODO add glob or BATT at least
			if($rate == -1 && is_file('/sys/class/power_supply/BAT0/voltage_now') && is_file('/sys/class/power_supply/BAT0/current_now'))
			{
				$voltage_now = pts_file_io::file_get_contents('/sys/class/power_supply/BAT0/voltage_now');
				$current_now = pts_file_io::file_get_contents('/sys/class/power_supply/BAT0/current_now');
				if(is_numeric($voltage_now) && is_numeric($current_now))
				{
					$voltage_now = $voltage_now / 1000;
					$current_now = $current_now / 1000;
					$power_now = $voltage_now * $current_now / 1000;

					if($power_now > 1)
					{
						$rate = $power_now;
					}
				}
			}
			if($rate == -1 && is_file('/sys/class/power_supply/BAT1/voltage_now') && is_file('/sys/class/power_supply/BAT1/current_now'))
			{
				$voltage_now = pts_file_io::file_get_contents('/sys/class/power_supply/BAT1/voltage_now') / 1000;
				$current_now = pts_file_io::file_get_contents('/sys/class/power_supply/BAT1/current_now') / 1000;
				$power_now = $voltage_now * $current_now / 1000;

				if($power_now > 1)
				{
					$rate = $power_now;
				}
			}
		}
		else if(phodevi::is_macos())
		{
			$amperage = phodevi_osx_parser::read_osx_system_profiler('SPPowerDataType', 'Amperage', false, array(), false); // in mA
			$voltage = phodevi_osx_parser::read_osx_system_profiler('SPPowerDataType', 'Voltage'); // in mV (no need to uncache since we just ran it)

			if($amperage > 0 && $voltage > 0)
			{
				$rate = round(($amperage * $voltage) / 1000);
			}
			else if(pts_client::executable_in_path('ioreg'))
			{
				$ioreg = shell_exec("ioreg -l | grep LegacyBatteryInfo | cut -d '{' -f 2 | tr -d \} | tr ',' '=' | awk -F'=' '{print ($2*$10/10^22)}' 2>&1");

				if(!empty($ioreg))
				{
					$ioreg = trim($ioreg);
					if(is_numeric($ioreg) && $ioreg > 0)
					{
						$rate = $ioreg;
					}
				}
			}
		}
		else if(phodevi::is_solaris())
		{
			$battery = phodevi_solaris_parser::read_hal_property('/org/freedesktop/Hal/devices/pseudo/acpi_drv_0_battery0_0', 'battery.reporting.rate');

			if(is_numeric($battery))
			{
				$rate = $battery;
			}
		}
		else if(phodevi::is_bsd())
		{
			$battery = phodevi_bsd_parser::read_acpiconf('Present rate');

			if($battery && substr($battery, -2) == 'mW')
			{
				$rate = substr($battery, 0, strpos($battery, ' '));
			}
		}

		return $rate;
	}
}

?>
