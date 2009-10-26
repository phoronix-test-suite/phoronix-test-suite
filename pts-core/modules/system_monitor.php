<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
	system_monitor.php: System sensor monitoring module for PTS

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
	const module_version = "1.5.0";
	const module_description = "This module contains sensor monitoring support.";
	const module_author = "Michael Larabel";

	static $to_monitor = array();

	public static function module_info()
	{
		$info = "";

		$info .= "\nMonitoring these sensors are as easy as running your normal Phoronix Test Suite commands but at the beginning of the command add: MONITOR=<selected sensors> (example: MONITOR=cpu.temp,cpu.voltage phoronix-test-suite benchmark universe). Below are all of the sensors supported by this version of the Phoronix Test Suite.\n\n";
		$info .= "Supported Options:\n";
		foreach(self::monitor_arguments() as $arg)
		{
			$info .= "  - " . $arg . "\n";
		}

		return $info;
	}

	//
	// General Functions
	//

	public static function __pre_option_process($obj = NULL)
	{
		self::$to_monitor = array();
		$to_show = explode(",", getenv("MONITOR"));
		$monitor_all = in_array("all", $to_show);

		foreach(pts_available_sensors() as $pts_sensor)
		{
			if($monitor_all || in_array($pts_sensor->get_identifier(), $to_show) || in_array("all." . $pts_sensor->get_sensor_type(), $to_show))
			{
				if($pts_sensor->read_sensor() != -1)
				{
					// Sensor supported
					array_push(self::$to_monitor, $pts_sensor);
					pts_module::save_file("logs/" . $pts_sensor->get_identifier());
				}
			}
		}
	}
	public static function __pre_run_process()
	{
		pts_module::pts_timed_function(9, "pts_monitor_update");
	}
	public static function pts_monitor_update()
	{
		foreach(self::$to_monitor as $pts_sensor)
		{
			$sensor_value = $pts_sensor->read_sensor();

			if($sensor_value != -1)
			{
				pts_module::save_file("logs/" . $pts_sensor->get_identifier(), $sensor_value, true);
			}
		}
	}
	public static function __post_option_process($obj = NULL)
	{
		if(defined("PTS_EXIT"))
			return;

		// Elapsed time

		$device = array();
		$type = array();
		$unit = array();
		$m_array = array();
		$type_index = array();

		foreach(self::$to_monitor as $pts_sensor)
		{
			$sensor_results = self::parse_monitor_log("logs/" . $pts_sensor->get_identifier());

			if(count($sensor_results) > 0)
			{
				if(!isset($type_index[$pts_sensor->get_sensor_string()]))
				{
					$type_index[$pts_sensor->get_sensor_string()] = array();
				}

				array_push($device, $pts_sensor->get_formatted_hardware_type());
				array_push($type, $pts_sensor->get_sensor_string());
				array_push($unit, $pts_sensor->get_sensor_unit());
				array_push($m_array, $sensor_results);
				array_push($type_index[$pts_sensor->get_sensor_string()], count($m_array) - 1);
			}
		}

		$info_report = "";
		if(isset($m_array[0]) && count($m_array[0]) == 1)
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

				$low = false;
				$high = 0;
				$total = 0;

				foreach($m_array[$i] as $temp)
				{
					if($low == false)
						$low = $temp;

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
				$image_list = array();
				pts_module::copy_file(RESULTS_VIEWER_DIR . "pts-monitor-viewer.html", "pts-monitor-viewer.html");
				pts_module::copy_file(RESULTS_VIEWER_DIR . "pts.js", "pts-monitor-viewer/pts.js");
				pts_module::copy_file(RESULTS_VIEWER_DIR . "pts-viewer.css", "pts-monitor-viewer/pts-viewer.css");

				$image_count = 0;
				foreach($type_index as $key => $sub_array)
				{
					if(count($sub_array) > 0)
					{
						$time_minutes = floor(pts_time_elapsed() / 60);
						if($time_minutes == 0)
							$time_minutes = 1;

						$graph_title = $type[$sub_array[0]] . " Monitor";
						$graph_unit = $unit[$sub_array[0]];
						$graph_unit = str_replace("°C", "Degrees Celsius", $graph_unit);
						$sub_title = "Elapsed Time: " . $time_minutes . " Minutes - ";
						$sub_title .= implode(" ", pts_read_assignment("TO_RUN_IDENTIFIERS"));
						// $sub_title .= date("g:i A");

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
						$save_filename_base = pts_unique_runtime_identifier() . '-' . $image_count;
						$t->loadGraphVersion("Phoronix Test Suite " . PTS_VERSION);
						$t->saveGraphToFile(pts_module::save_dir() . $save_filename_base . ".BILDE_EXTENSION");
						$t->renderGraph();
						$save_filename = $save_filename_base . "." . strtolower($t->getRenderer());

						array_push($image_list, $save_filename);
						$image_count++;
					}
				}
				$url = implode($image_list, ",");
			}
		}

		if(count($m_array) > 0)
			$info_report .= "\n\nElapsed Time: " . pts_format_time_string(pts_time_elapsed());

		// terminal output
		if(!empty($info_report))
			echo pts_string_header($info_report);

		if(isset($m_array[0]) && count($m_array[0]) > 1 && !empty($url))
		{
			$file = pts_module::save_file("link-latest.html", "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\"><html><head><title>Phoronix Test Suite</title><meta http-equiv=\"REFRESH\" content=\"0;url=pts-monitor-viewer.html#$url\"></HEAD><BODY></BODY></HTML>");
			if($file != FALSE)
				pts_display_web_browser($file);
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
		$args = array("all");

		foreach(pts_available_sensors() as $pts_sensor)
		{
			if(!in_array("all." . $pts_sensor->get_sensor_type(), $args))
			{
				array_push($args, "all." . $pts_sensor->get_sensor_type());
			}

			array_push($args, $pts_sensor->get_hardware_type() . "." . $pts_sensor->get_sensor_type());
		}

		return $args;
	}
}

?>
