<?php

function pts_monitor_update()
{
	if(defined("MONITOR_GPU_TEMP"))
		pts_record_gpu_temperature();
	if(defined("MONITOR_CPU_TEMP"))
		pts_record_cpu_temperature();
	if(defined("MONITOR_SYS_TEMP"))
		pts_record_sys_temperature();
}
function pts_monitor_statistics()
{
	$device = array();
	$type = array();
	$unit = array();
	$m_array = array();
	$type_index = array();
	$type_index["THERMAL"] = array();

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

	$info_report = "";
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

		$info_report .= $device[$i] . " " . $type[$i] . " Statistics:\n\nLow: " . pts_trim_double($low) . $unit[$i] . "\nHigh: " . pts_trim_double($high) . $unit[$i] . "\nAverage: " . pts_trim_double($avg) . $unit[$i];
	}

	if(trim($info_report) != "")
	{
		if(pts_gd_available())
		{
			pts_save_user_file();
			$image_count = 0;
			foreach($type_index as $key => $sub_array)
			{
				$graph_title = $type[$sub_array[0]] . " Monitor";
				$graph_unit = $unit[$sub_array[0]];
				$graph_unit = str_replace("°C", "Degrees Celsius", $graph_unit);
				$sub_title = date("F j, Y - g:i A");

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
				$t->save_graph(PTS_USER_DIR . strtolower(PTS_CODENAME) . '/' . THIS_RUN_TIME . '-' . $image_count . ".png");
				$t->renderGraph();
				//display_web_browser(PTS_USER_DIR . strtolower(PTS_CODENAME) . '/' . THIS_RUN_TIME . '-' . $image_count . ".png");
				$image_count++;
			}
		}

		// terminal output
		echo pts_string_header($info_report);
	}
}

?>
