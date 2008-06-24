<?php

/*
	Phoronix Test Suite "Trondheim"
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2004-2008, Michael Larabel
	pts_module_interface.php: The generic Phoronix Test Suite module object that is extended by the specific modules/plug-ins

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

class system_monitor extends pts_module_interface
{
	const module_name = "System Monitor";
	const module_version = "1.1.0";
	const module_description = "This module contains the sensor monitoring support.";
	const module_author = "Michael Larabel";

	public static function module_info()
	{
		$info = "";

		$info .= "\nMonitoring these sensors are as easy as running your normal Phoronix Test Suite commands but at the beginning of the command add: MONITOR=<selected sensors> (example: MONITOR=cpu.temp,cpu.voltage phoronix-test-suite benchmark universe). Below are all of the sensors supported by this version of the Phoronix Test Suite.\n\n";
		$info .= "Supported Options:\n";
		foreach(self::monitor_arguments() as $arg)
			$info .= "  - " . $arg . "\n";

		return $info;
	}

	//
	// General Functions
	//

	public static function __startup($obj = NULL)
	{
		$to_show = getenv("MONITOR");
		
		$to_show = explode(',', $to_show);
		$monitor_all = in_array("all", $to_show);
		$monitor_temp = in_array("all.temp", $to_show) || $monitor_all;
		$monitor_power = in_array("all.power", $to_show) || $monitor_all;
		$monitor_voltage = in_array("all.voltage", $to_show) || $monitor_all;
		$monitor_freq = in_array("all.freq", $to_show) || $monitor_all;
		$monitor_usage = in_array("all.usage", $to_show) || $monitor_all;

		if(in_array("gpu.temp", $to_show)  || $monitor_temp)
		{
			define("MONITOR_GPU_TEMP", 1);
			pts_module::save_file(".s/GPU_TEMPERATURE");
		}
		if(in_array("cpu.temp", $to_show)  || $monitor_temp)
		{	
			define("MONITOR_CPU_TEMP", 1);
			pts_module::save_file(".s/CPU_TEMPERATURE");
		}
		if(in_array("sys.temp", $to_show)  || $monitor_temp)
		{	
			define("MONITOR_SYS_TEMP", 1);
			pts_module::save_file(".s/SYS_TEMPERATURE");
		}
		if(in_array("battery.power", $to_show) || $monitor_power)
		{	
			define("MONITOR_BATTERY_POWER", 1);
			pts_module::save_file(".s/BATTERY_POWER");
		}
		if(in_array("cpu.voltage", $to_show) || $monitor_voltage)
		{	
			define("MONITOR_CPU_VOLTAGE", 1);
			pts_module::save_file(".s/CPU_VOLTAGE");
		}
		if(in_array("v3.voltage", $to_show) || $monitor_voltage)
		{
			define("MONITOR_V3_VOLTAGE", 1);
			pts_module::save_file(".s/BATTERY_POWER");
		}
		if(in_array("v5.voltage", $to_show) || $monitor_voltage)
		{
			define("MONITOR_V5_VOLTAGE", 1);
			pts_module::save_file(".s/V5_VOLTAGE");
		}
		if(in_array("v12.voltage", $to_show) || $monitor_voltage)
		{
			define("MONITOR_V12_VOLTAGE", 1);
			pts_module::save_file(".s/V12_VOLTAGE");
		}
		if(in_array("cpu.freq", $to_show) || $monitor_freq)
		{
			define("MONITOR_CPU_FREQ", 1);
			pts_module::save_file(".s/CPU_FREQ");
		}
		if(in_array("gpu.freq", $to_show) || $monitor_freq)
		{
			define("MONITOR_GPU_FREQ", 1);
			pts_module::save_file(".s/GPU_FREQ");
		}
		if(in_array("gpu.usage", $to_show) || $monitor_usage)
		{
			define("MONITOR_GPU_USAGE", 1);
			pts_module::save_file(".s/GPU_USAGE");
		}
		if(in_array("cpu.usage", $to_show) || $monitor_usage)
		{
			define("MONITOR_CPU_USAGE", 1);
			pts_module::save_file(".s/CPU_USAGE");
		}

		pts_module::pts_timed_function(15, "pts_monitor_update");
	}
	public static function __shutdown($obj = NULL)
	{
		if(defined("PTS_EXIT"))
			return;

		$device = array();
		$type = array();
		$unit = array();
		$m_array = array();
		$type_index = array();
		$type_index["THERMAL"] = array();
		$type_index["POWER"] = array();
		$type_index["VOLTAGE"] = array();
		$type_index["FREQUENCY"] = array();
		$type_index["USAGE"] = array();

		if(defined("MONITOR_GPU_TEMP"))
		{
			$this_array = self::parse_monitor_log(".s/GPU_TEMPERATURE");

			if(is_array($this_array) && !empty($this_array[0]))
			{
				array_push($device, "GPU");
				array_push($type, "Thermal");
				array_push($unit, "°C");
				array_push($m_array, $this_array);
				array_push($type_index["THERMAL"], count($m_array) - 1);
			}
		}
		if(defined("MONITOR_CPU_TEMP"))
		{
			$this_array = self::parse_monitor_log(".s/CPU_TEMPERATURE");

			if(is_array($this_array) && !empty($this_array[0]))
			{
				array_push($device, "CPU");
				array_push($type, "Thermal");
				array_push($unit, "°C");
				array_push($m_array, $this_array);
				array_push($type_index["THERMAL"], count($m_array) - 1);
			}
		}
		if(defined("MONITOR_SYS_TEMP"))
		{
			$this_array = self::parse_monitor_log(".s/SYS_TEMPERATURE");

			if(is_array($this_array) && !empty($this_array[0]))
			{
				array_push($device, "System");
				array_push($type, "Thermal");
				array_push($unit, "°C");
				array_push($m_array, $this_array);
				array_push($type_index["THERMAL"], count($m_array) - 1);
			}
		}
		if(defined("MONITOR_BATTERY_POWER"))
		{
			$this_array = self::parse_monitor_log(".s/BATTERY_POWER");

			if(is_array($this_array) && !empty($this_array[0]))
			{
				array_push($device, "Battery");
				array_push($type, "Power");
				array_push($unit, "Milliwatts");
				array_push($m_array, $this_array);
				array_push($type_index["POWER"], count($m_array) - 1);
			}
		}
		if(defined("MONITOR_CPU_VOLTAGE"))
		{
			$this_array = self::parse_monitor_log(".s/CPU_VOLTAGE");

			if(is_array($this_array) && !empty($this_array[0]))
			{
				array_push($device, "CPU");
				array_push($type, "Voltage");
				array_push($unit, "Volts");
				array_push($m_array, $this_array);
				array_push($type_index["VOLTAGE"], count($m_array) - 1);
			}
		}
		if(defined("MONITOR_V3_VOLTAGE"))
		{
			$this_array = self::parse_monitor_log(".s/V3_VOLTAGE");

			if(is_array($this_array) && !empty($this_array[0]))
			{
				array_push($device, "+3.33V");
				array_push($type, "Voltage");
				array_push($unit, "Volts");
				array_push($m_array, $this_array);
				array_push($type_index["VOLTAGE"], count($m_array) - 1);
			}
		}
		if(defined("MONITOR_V5_VOLTAGE"))
		{
			$this_array = self::parse_monitor_log(".s/V5_VOLTAGE");

			if(is_array($this_array) && !empty($this_array[0]))
			{
				array_push($device, "+5.00V");
				array_push($type, "Voltage");
				array_push($unit, "Volts");
				array_push($m_array, $this_array);
				array_push($type_index["VOLTAGE"], count($m_array) - 1);
			}
		}
		if(defined("MONITOR_V12_VOLTAGE"))
		{
			$this_array = self::parse_monitor_log(".s/V12_VOLTAGE");

			if(is_array($this_array) && !empty($this_array[0]))
			{
				array_push($device, "+12.00V");
				array_push($type, "Voltage");
				array_push($unit, "Volts");
				array_push($m_array, $this_array);
				array_push($type_index["VOLTAGE"], count($m_array) - 1);
			}
		}
		if(defined("MONITOR_CPU_FREQ"))
		{
			$this_array = self::parse_monitor_log(".s/CPU_FREQ");

			if(is_array($this_array) && !empty($this_array[0]))
			{
				array_push($device, "CPU");
				array_push($type, "Frequency");
				array_push($unit, "MHz");
				array_push($m_array, $this_array);
				array_push($type_index["FREQUENCY"], count($m_array) - 1);
			}
		}
		if(defined("MONITOR_GPU_FREQ"))
		{
			$this_array = self::parse_monitor_log(".s/GPU_FREQ");

			if(is_array($this_array) && !empty($this_array[0]))
			{
				array_push($device, "GPU");
				array_push($type, "Frequency");
				array_push($unit, "MHz");
				array_push($m_array, $this_array);
				array_push($type_index["FREQUENCY"], count($m_array) - 1);
			}
		}
		if(defined("MONITOR_GPU_USAGE"))
		{
			$this_array = self::parse_monitor_log(".s/GPU_USAGE");

			if(is_array($this_array) && !empty($this_array[0]))
			{
				array_push($device, "GPU");
				array_push($type, "Usage");
				array_push($unit, "Percent");
				array_push($m_array, $this_array);
				array_push($type_index["USAGE"], count($m_array) - 1);
			}
		}
		if(defined("MONITOR_CPU_USAGE"))
		{
			$this_array = self::parse_monitor_log(".s/CPU_USAGE");

			if(is_array($this_array) && !empty($this_array[0]))
			{
				array_push($device, "CPU");
				array_push($type, "Usage");
				array_push($unit, "Percent");
				array_push($m_array, $this_array);
				array_push($type_index["USAGE"], count($m_array) - 1);
			}
		}

		$info_report = "";

		if(count($m_array[0]) == 1)
		{
			$info_report .= "Current Sensor Readings:\n\n";
			for($i = 0; $i < count($m_array); $i++)
			{
				$info_report .= $device[$i] . " " . $type[$i] . " Monitor: " . $m_array[$i][0] . " " .  $unit[$i];

				if($i < (count($m_array) - 1))
					$info_report .= "\n";
			}
		}
		else
		{
			for($i = 0; $i < count($m_array); $i++)
			{
				// Calculate statistics

				if($i > 0)
					$info_report .= "\n\n";

				$low = 0;
				$high = 0;
				$total = 0;

				foreach($m_array[$i] as $temp)
				{
					if($temp < $low || ($low == 0 && $type[$i] <> "Usage"))
						$low = $temp;
					else if($temp > $high)
						$high = $temp;

					$total += $temp;
				}
				$avg = $total / count($m_array[$i]);

				$info_report .= $device[$i] . " " . $type[$i] . " Statistics:\n\nLow: " . pts_trim_double($low) . ' ' . $unit[$i] . "\nHigh: " . pts_trim_double($high) . ' ' . $unit[$i] . "\nAverage: " . pts_trim_double($avg) . ' ' . $unit[$i];
			}

			if(trim($info_report) != "")
			{
				if(pts_gd_available())
				{
					$image_list = array();
					pts_module::copy_file(RESULTS_VIEWER_DIR . "pts-monitor-viewer.html", "pts-monitor-viewer.html");
					pts_module::copy_file(RESULTS_VIEWER_DIR . "pts.js", "pts-monitor-viewer/pts.js");
					pts_module::copy_file(RESULTS_VIEWER_DIR . "pts-viewer.css", "pts-monitor-viewer/pts-viewer.css");

					$image_count = 0;
					foreach($type_index as $key => $sub_array)
					{
						if(count($sub_array) > 0)
						{
							$graph_title = $type[$sub_array[0]] . " Monitor";
							$graph_unit = $unit[$sub_array[0]];
							$graph_unit = str_replace("°C", "Degrees Celsius", $graph_unit);
							$sub_title = date("F j, Y") . " - ";

							if(isset($GLOBALS["TO_RUN"]))
								$sub_title .= $GLOBALS["TO_RUN"];
							else
								$sub_title .= date("g:i A");

							$t = new pts_LineGraph($graph_title, $sub_title, $graph_unit);

							$first_run = true;
							foreach($sub_array as $id_point)
							{
								$t->loadGraphValues($m_array[$id_point], $device[$id_point]);

								if($first_run)
								{
									$t->loadGraphIdentifiers($m_array[$id_point]);
									$t->hideGraphIdentifiers();
									$first_run = false;
								}
							}
							$t->loadGraphVersion(PTS_VERSION);
							$t->save_graph(pts_module::save_dir() . THIS_RUN_TIME . '-' . $image_count . ".png");
							$t->renderGraph();

							array_push($image_list, THIS_RUN_TIME . '-' . $image_count . ".png");
							$image_count++;
						}
					}
					$url = implode($image_list, ",");
				}
			}
		}

		// Elapsed time
		$time_diff = PTS_END_TIME - PTS_START_TIME;

		if($time_diff > 10 && count($m_array) > 0)
			$info_report .= "\n\nElapsed Time: " . pts_format_time_string($time_diff);

		// terminal output
		if(!empty($info_report))
			echo pts_string_header($info_report);

		if(count($m_array[0]) > 1 && !empty($url))
		{
			$file = pts_module::save_file("link-latest.html", "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\"><html><head><title>Phoronix Test Suite</title><meta http-equiv=\"REFRESH\" content=\"0;url=pts-monitor-viewer.html#$url\"></HEAD><BODY></BODY></HTML>
	");
			if($file != FALSE)
				display_web_browser($file);
		}
	}

	//
	// Extra Functions
	//

	public function pts_monitor_update()
	{
		if(defined("MONITOR_GPU_TEMP"))
		{
			$temp = graphics_processor_temperature();

			if($temp != -1)
				pts_module::save_file(".s/GPU_TEMPERATURE", $temp, true);
		}
		if(defined("MONITOR_CPU_TEMP"))
		{
			$temp = processor_temperature();

			if($temp != -1)
				pts_module::save_file(".s/CPU_TEMPERATURE", $temp, true);
		}
		if(defined("MONITOR_SYS_TEMP"))
		{
			$temp = system_temperature();

			if($temp != -1)
				pts_module::save_file(".s/SYS_TEMPERATURE", $temp, true);
		}
		if(defined("MONITOR_BATTERY_POWER"))
		{
			$state = read_acpi("/battery/BAT0/state", "charging state");
			$power = read_acpi("/battery/BAT0/state", "present rate");

			if($state == "discharging")
			{
				if(($end = strpos($power, ' ')) > 0)
					$power = substr($power, 0, $end);

				if(!empty($power))
					pts_module::save_file(".s/BATTERY_POWER", $power, true);
			}
		}
		if(defined("MONITOR_CPU_VOLTAGE"))
		{
			$voltage = system_line_voltage("CPU");

			if($voltage != -1)
				pts_module::save_file(".s/GPU_VOLTAGE", $voltage, true);
		}
		if(defined("MONITOR_V3_VOLTAGE"))
		{
			$voltage = system_line_voltage("V3");

			if($voltage != -1)
				pts_module::save_file(".s/V3_VOLTAGE", $voltage, true);
		}
		if(defined("MONITOR_V5_VOLTAGE"))
		{
			$voltage = system_line_voltage("V5");

			if($voltage != -1)
				pts_module::save_file(".s/V5_VOLTAGE", $voltage, true);
		}
		if(defined("MONITOR_V12_VOLTAGE"))
		{
			$voltage = system_line_voltage("V12");

			if($voltage != -1)
				pts_module::save_file(".s/V12_VOLTAGE", $voltage, true);
		}
		if(defined("MONITOR_CPU_FREQ"))
		{
			$speed = current_processor_frequency();

			if($speed > 0)
				pts_module::save_file(".s/CPU_FREQ", $speed, true);
		}
		if(defined("MONITOR_GPU_FREQ"))
		{
			$speed = graphics_processor_frequency();

			if(!empty($speed[0]))
				pts_module::save_file(".s/GPU_FREQ", $speed[0], true);
		}
		if(defined("MONITOR_GPU_USAGE"))
		{
			$usage = graphics_gpu_usage();

			if($usage != "")
				pts_module::save_file(".s/GPU_USAGE", $usage, true);
		}
		if(defined("MONITOR_CPU_USAGE"))
		{
			$usage = current_processor_usage();

			if($usage != -1)
				pts_module::save_file(".s/CPU_USAGE", $usage, true);
		}
	}
	private function parse_monitor_log($log_file)
	{
		$log_f = pts_module::read_file($log_file);
		pts_module::remove_file($log_file);
		$line_breaks = explode("\n", $log_f);
		$results = array();

		foreach($line_breaks as $line)
		{
			$line = trim($line);
			if(!empty($line))
				array_push($results, $line);
		}

		return $results;
	}
	private function monitor_arguments()
	{
		return array("all", "all.temp", "all.power", "all.voltage", "all.freq", "all.usage", "gpu.temp", "cpu.temp", "sys.temp", "battery.power", "cpu.voltage", "v3.voltage", "v5.voltage", "v12.voltage", "cpu.freq", "gpu.freq", "gpu.usage", "cpu.usage");
	}
}

?>
