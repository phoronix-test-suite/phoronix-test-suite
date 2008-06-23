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
	const module_version = "1.0.0";
	const module_description = "This module contains the sensor monitoring support for the Phoronix Test Suite.";
	const module_author = "Michael Larabel";

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
		define("PTS_START_TIME", time());

		if(in_array("gpu.temp", $to_show)  || $monitor_temp)
		{
			define("MONITOR_GPU_TEMP", 1);
			$GLOBALS["GPU_TEMPERATURE"] = array();
		}
		if(in_array("cpu.temp", $to_show)  || $monitor_temp)
		{	
			define("MONITOR_CPU_TEMP", 1);
			$GLOBALS["CPU_TEMPERATURE"] = array();
		}
		if(in_array("sys.temp", $to_show)  || $monitor_temp)
		{	
			define("MONITOR_SYS_TEMP", 1);
			$GLOBALS["SYS_TEMPERATURE"] = array();
		}
		if(in_array("battery.power", $to_show) || $monitor_power)
		{	
			define("MONITOR_BATTERY_POWER", 1);
			$GLOBALS["BATTERY_POWER"] = array();
		}
		if(in_array("cpu.voltage", $to_show) || $monitor_voltage)
		{	
			define("MONITOR_CPU_VOLTAGE", 1);
			$GLOBALS["CPU_VOLTAGE"] = array();
		}
		if(in_array("v3.voltage", $to_show) || $monitor_voltage)
		{
			define("MONITOR_V3_VOLTAGE", 1);
			$GLOBALS["V3_VOLTAGE"] = array();
		}
		if(in_array("v5.voltage", $to_show) || $monitor_voltage)
		{
			define("MONITOR_V5_VOLTAGE", 1);
			$GLOBALS["V5_VOLTAGE"] = array();
		}
		if(in_array("v12.voltage", $to_show) || $monitor_voltage)
		{
			define("MONITOR_V12_VOLTAGE", 1);
			$GLOBALS["V12_VOLTAGE"] = array();
		}
		if(in_array("cpu.freq", $to_show) || $monitor_freq)
		{
			define("MONITOR_CPU_FREQ", 1);
			$GLOBALS["CPU_FREQ"] = array();
		}
		if(in_array("gpu.usage", $to_show) || $monitor_usage)
		{
			define("MONITOR_GPU_USAGE", 1);
			$GLOBALS["GPU_USAGE"] = array();
		}
	}
	public static function __shutdown($obj = NULL)
	{
		if(defined("PTS_EXIT"))
			return;

		define("PTS_END_TIME", time());

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

		if(isset($GLOBALS["GPU_TEMPERATURE"]))
		{
			$this_array = $GLOBALS["GPU_TEMPERATURE"];

			if(is_array($this_array) && !empty($this_array[0]))
			{
				array_push($device, "GPU");
				array_push($type, "Thermal");
				array_push($unit, "°C");
				array_push($m_array, $this_array);
				array_push($type_index["THERMAL"], count($m_array) - 1);
			}
		}
		if(isset($GLOBALS["CPU_TEMPERATURE"]))
		{
			$this_array = $GLOBALS["CPU_TEMPERATURE"];

			if(is_array($this_array) && !empty($this_array[0]))
			{
				array_push($device, "CPU");
				array_push($type, "Thermal");
				array_push($unit, "°C");
				array_push($m_array, $this_array);
				array_push($type_index["THERMAL"], count($m_array) - 1);
			}
		}
		if(isset($GLOBALS["SYS_TEMPERATURE"]))
		{
			$this_array = $GLOBALS["SYS_TEMPERATURE"];

			if(is_array($this_array) && !empty($this_array[0]))
			{
				array_push($device, "System");
				array_push($type, "Thermal");
				array_push($unit, "°C");
				array_push($m_array, $this_array);
				array_push($type_index["THERMAL"], count($m_array) - 1);
			}
		}
		if(isset($GLOBALS["BATTERY_POWER"]))
		{
			$this_array = $GLOBALS["BATTERY_POWER"];

			if(is_array($this_array) && !empty($this_array[0]))
			{
				array_push($device, "Battery");
				array_push($type, "Power");
				array_push($unit, "Milliwatts");
				array_push($m_array, $this_array);
				array_push($type_index["POWER"], count($m_array) - 1);
			}
		}
		if(isset($GLOBALS["CPU_VOLTAGE"]))
		{
			$this_array = $GLOBALS["CPU_VOLTAGE"];

			if(is_array($this_array) && !empty($this_array[0]))
			{
				array_push($device, "CPU");
				array_push($type, "Voltage");
				array_push($unit, "Volts");
				array_push($m_array, $this_array);
				array_push($type_index["VOLTAGE"], count($m_array) - 1);
			}
		}
		if(isset($GLOBALS["V3_VOLTAGE"]))
		{
			$this_array = $GLOBALS["V3_VOLTAGE"];

			if(is_array($this_array) && !empty($this_array[0]))
			{
				array_push($device, "+3.33V");
				array_push($type, "Voltage");
				array_push($unit, "Volts");
				array_push($m_array, $this_array);
				array_push($type_index["VOLTAGE"], count($m_array) - 1);
			}
		}
		if(isset($GLOBALS["V5_VOLTAGE"]))
		{
			$this_array = $GLOBALS["V5_VOLTAGE"];

			if(is_array($this_array) && !empty($this_array[0]))
			{
				array_push($device, "+5.00V");
				array_push($type, "Voltage");
				array_push($unit, "Volts");
				array_push($m_array, $this_array);
				array_push($type_index["VOLTAGE"], count($m_array) - 1);
			}
		}
		if(isset($GLOBALS["V12_VOLTAGE"]))
		{
			$this_array = $GLOBALS["V12_VOLTAGE"];

			if(is_array($this_array) && !empty($this_array[0]))
			{
				array_push($device, "+12.00V");
				array_push($type, "Voltage");
				array_push($unit, "Volts");
				array_push($m_array, $this_array);
				array_push($type_index["VOLTAGE"], count($m_array) - 1);
			}
		}
		if(isset($GLOBALS["CPU_FREQ"]))
		{
			$this_array = $GLOBALS["CPU_FREQ"];

			if(is_array($this_array) && !empty($this_array[0]))
			{
				array_push($device, "CPU");
				array_push($type, "Frequency");
				array_push($unit, "MHz");
				array_push($m_array, $this_array);
				array_push($type_index["FREQUENCY"], count($m_array) - 1);
			}
		}
		if(isset($GLOBALS["GPU_USAGE"]))
		{
			$this_array = $GLOBALS["GPU_USAGE"];

			if(is_array($this_array) && !empty($this_array[0]))
			{
				array_push($device, "GPU");
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
					if($temp < $low || $low == 0)
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
					pts_save_user_file();
					pts_save_user_file(null, null, "/pts-monitor-viewer/");
					pts_copy(RESULTS_VIEWER_DIR . "pts-monitor-viewer.html", PTS_MONITOR_DIR . "pts-monitor-viewer.html");
					pts_copy(RESULTS_VIEWER_DIR . "pts.js", PTS_MONITOR_DIR . "pts-monitor-viewer/pts.js");
					pts_copy(RESULTS_VIEWER_DIR . "pts-viewer.css", PTS_MONITOR_DIR . "pts-monitor-viewer/pts-viewer.css");

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
							$t->save_graph(PTS_MONITOR_DIR . THIS_RUN_TIME . '-' . $image_count . ".png");
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
			file_put_contents(PTS_MONITOR_DIR . "link-latest.html", "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\"><html><head><title>Phoronix Test Suite</title><meta http-equiv=\"REFRESH\" content=\"0;url=pts-monitor-viewer.html#$url\"></HEAD><BODY></BODY></HTML>
	");
			display_web_browser(PTS_MONITOR_DIR . "link-latest.html");
		}
	}

	//
	// Installation Functions
	//

	public static function __pre_install_process($obj = NULL)
	{
		return;
	}
	public static function __pre_test_install($obj = NULL)
	{
		return;
	}
	public static function __post_test_install($obj = NULL)
	{
		return;
	}
	public static function __post_install_process($obj = NULL)
	{
		return;
	}

	//
	// Run Functions
	//

	public static function __pre_run_process($obj = NULL)
	{
		self::pts_monitor_update();
	}
	public static function __interim_test_run($obj = NULL)
	{
		self::pts_monitor_update();
	}
	public static function __post_run_process($obj = NULL)
	{
		self::pts_monitor_update();
	}

	//
	// Extra Functions
	//

	private function pts_monitor_update()
	{
		if(defined("MONITOR_GPU_TEMP"))
			pts_record_gpu_temperature();
		if(defined("MONITOR_CPU_TEMP"))
			pts_record_cpu_temperature();
		if(defined("MONITOR_SYS_TEMP"))
			pts_record_sys_temperature();
		if(defined("MONITOR_BATTERY_POWER"))
			pts_record_battery_power();
		if(defined("MONITOR_CPU_VOLTAGE"))
			pts_record_cpu_voltage();
		if(defined("MONITOR_V3_VOLTAGE"))
			pts_record_v3_voltage();
		if(defined("MONITOR_V5_VOLTAGE"))
			pts_record_v5_voltage();
		if(defined("MONITOR_V12_VOLTAGE"))
			pts_record_v12_voltage();
		if(defined("MONITOR_CPU_FREQ"))
			pts_record_cpu_frequency();
		if(defined("MONITOR_GPU_USAGE"))
			pts_record_gpu_usage();
	}
	private function pts_monitor_arguments()
	{
		return array("all", "all.temp", "all.power", "all.voltage", "all.freq", "all.usage", "gpu.temp", "cpu.temp", "sys.temp", "battery.power", "cpu.voltage", "v3.voltage", "v5.voltage", "v12.voltage", "cpu.freq", "gpu.usage");
	}
}

?>
