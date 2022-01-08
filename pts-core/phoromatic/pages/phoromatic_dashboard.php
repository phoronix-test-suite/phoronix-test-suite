<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2014 - 2016, Phoronix Media
	Copyright (C) 2014 - 2016, Michael Larabel

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

class phoromatic_dashboard implements pts_webui_interface
{
	public static function page_title()
	{
		return 'System Dashboard';
	}
	public static function page_header()
	{
		return null;
	}
	public static function preload($PAGE)
	{
		return true;
	}
	public static function render_page_process($PATH)
	{
		echo phoromatic_webui_header_logged_in();
		$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_systems WHERE AccountID = :account_id AND State >= 0 ORDER BY LastCommunication DESC');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$result = $stmt->execute();
		echo '<script type="text/javascript"> setInterval(function() { window.location.reload(); }, 79000); </script>';
		echo '<div style="margin: 10px 0 30px; clear: both; padding-bottom: 40px;">';
		while($row = $result->fetchArray())
		{
			$opacity = null;
			// stripos($row['CurrentTask'], 'idling') !== false ||
			if(phoromatic_server::system_check_if_down($_SESSION['AccountID'], $row['SystemID'], $row['LastCommunication'], $row['CurrentTask']) || stripos($row['CurrentTask'], 'Unknown') !== false)
			{
				$not_testing = false;
				$opacity = ' style="background: #f44336; color: #FFF;"';
			}
			else if(stripos($row['CurrentTask'], 'idling') !== false)
			{
				$not_testing = true;
			//	continue;
			}
			else if(stripos($row['CurrentTask'], 'waiting') !== false || stripos($row['CurrentTask'], 'shutdown') !== false)
			{
				$not_testing = true;
				$opacity = ' style="opacity: 0.3;"';
			}
			else
			{
				$not_testing = false;
			}

			echo '<a href="?systems/' . $row['SystemID'] . '"><div class="phoromatic_dashboard_block"' . $opacity . '>';
			echo '<div style="float: left; width: 30%;">';
			echo '<h1>' . $row['Title'] . '</h1>';

			$components = array_merge(pts_result_file_analyzer::system_component_string_to_array($row['Hardware'], array('Processor', 'Motherboard')), pts_result_file_analyzer::system_component_string_to_array($row['Software'], array('OS', 'Kernel')));
			foreach($components as &$c)
			{
				if(($x = stripos($c, ' @')) !== false)
					$c = substr($c, 0, $x);
				if(($x = stripos($c, ' (')) !== false)
					$c = substr($c, 0, $x);
			}
			echo '<p><em>' . implode(' - ', $components) . '</em></p>';
			echo '<h2>' . $row['CurrentTask'] . '</h2>';
			if(!empty($row['CurrentProcessSchedule']))
			{
				echo '<h2><a href="/?schedules/' . $row['CurrentProcessSchedule'] . '">' . phoromatic_server::schedule_id_to_name($row['CurrentProcessSchedule']) . '</a></h2>';
			}
			else if(!empty($row['CurrentProcessTicket']))
			{
				echo '<h2><a href="/?benchmark/' . $row['CurrentProcessTicket'] . '/&view_log=' . $row['SystemID'] . '">' . phoromatic_server::ticket_id_to_name($row['CurrentProcessTicket']) . '</a></h2>';
			}
			echo '</div>';

			echo '<div style="float: left;">';
			echo '<h2>' . $row['LastIP'] . '</h2>';
			echo '</div>';

			$time_remaining = phoromatic_server::estimated_time_remaining_diff($row['EstimatedTimeForTask'], $row['LastCommunication']);
			if($time_remaining > 0)
			{
				echo '<div style="float: left; text-align: center; margin: 0 6px;">';
				echo '<h2>~ ' . $time_remaining . ' <sub>mins</sub></h2>';
				echo '<p style="font-size: 90%; color: #FFF;"><em>Estimated Time Remaining</em></p>';
				if(!empty($row['TimeToNextCommunication']))
				{
					echo '<pstyle="color: #FFF;"><em>' . phoromatic_server::estimated_time_remaining_string($row['TimeToNextCommunication'], $row['LastCommunication'], 'To Next Communication') . '</em></p>';
				}
				echo '</div>';
			}

			if($not_testing)
			{
				$next_job_in = phoromatic_server::time_to_next_scheduled_job($_SESSION['AccountID'], $row['SystemID']);
				if($next_job_in > 0)
				{
					if($next_job_in > 240)
					{
						$next_job_in = round($next_job_in / 60);
						$next_unit = 'hours';
					}
					else
					{
						$next_unit = 'mins';
					}

					echo '<div style="float: left; margin: 0 0 0 10px; text-align: center;">';
					echo '<h2>' . $next_job_in . ' <sub>' . $next_unit . '</sub></h2>';
					echo '<p style="font-size: 90%; color: #FFF;"><em>Time To Next Scheduled Task</em></p>';
					echo '</div>';
				}
			}

			$system_path = phoromatic_server::phoromatic_account_system_path($_SESSION['AccountID'], $row['SystemID']);
			if(is_file($system_path . 'sensors-pool.json'))
			{
				$sensors = file_get_contents($system_path . 'sensors-pool.json');
				$sensors = json_decode($sensors, true);

				echo '<div style="float: right; margin: 0 10px 0 10px;">';
				$g_count = 0;
				foreach(array('CPU Usage', 'Memory Usage', 'CPU Temperature', 'System Temperature', 'GPU Temperature', 'Swap Usage', 'System Iowait', 'CPU Frequency') as $s)
				{
					if(!isset($sensors[$s]) || !isset($sensors[$s]['values']) || count($sensors[$s]['values']) < 5)
					{
						continue;
					}
					$g_count++;

					if($g_count <= 3)
					{
						$graph = new pts_sys_graph(array('title' => $s, 'x_scale' => 'm', 'y_scale' => $sensors[$s]['unit'], 'text_size' => 10, 'reverse_x_direction' => false, 'width' => 300, 'height' => 120, 'text_color' => '#000000', 'paint_color' => '#D95D04', 'background_color' => '#ffffff', 'shade_color' => '#ffffff'));
						$graph->render_base();
						$svg_dom = $graph->render_graph_data($sensors[$s]['values']);
						if($svg_dom === false)
						{
							continue;
						}
						$output_type = 'SVG';
						$graph = $svg_dom->output(null, $output_type);
						echo substr($graph, strpos($graph, '<svg'));
					}
					else
					{
						break;
					}
				}
				echo '</div>';
			}

			echo '<hr style="width: ' . $row['TaskPercentComplete'] . '%;" />';
			echo '</div></a>';

		}
		echo '</div>';
		echo phoromatic_webui_footer();
	}
}

?>
