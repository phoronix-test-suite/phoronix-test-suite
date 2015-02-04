<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2015, Phoronix Media
	Copyright (C) 2008 - 2015, Michael Larabel

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


class phoromatic_results implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Test Schedules';
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
			$main = null;

			if(!PHOROMATIC_USER_IS_VIEWER && isset($PATH[0]) && $PATH[0] == 'delete')
			{
				$pprids = explode(',', $PATH[1]);

				foreach($pprids as $pprid)
				{
					$stmt = phoromatic_server::$db->prepare('DELETE FROM phoromatic_results WHERE AccountID = :account_id AND PPRID = :pprid');
					$stmt->bindValue(':account_id', $_SESSION['AccountID']);
					$stmt->bindValue(':pprid', $pprid);
					$result = $stmt->execute();

					// TODO XXX fix below
					//$upload_dir = phoromatic_server::phoromatic_account_result_path($_SESSION['AccountID'], $upload_id);
					//pts_file_io::delete($upload_dir);
				}
			}

			if($main == null)
			{
				$time_limit = false;
				$time_str = false;
				if(isset($_GET['time']))
				{
					$time_str = $_GET['time'];
					$time_limit = strtotime('- ' . $time_str);
				}
				if($time_limit == false)
				{
					$time_str = '2 weeks';
					$time_limit = strtotime('- ' . $time_str);
				}

				$main = '<h1>Test Results For The Past ' . ucwords($time_str) . '</h1>';
				$main .= '<div class="pts_phoromatic_info_box_area">';
				$main .= '<div style="margin: 0 5%;"><ul style="max-height: 100%;"><li><h1>Recent Test Results</h1></li>';
				$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, ScheduleID, PPRID, UploadTime, TimesViewed FROM phoromatic_results WHERE AccountID = :account_id ORDER BY UploadTime DESC');
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$test_result_result = $stmt->execute();
				$results = 0;
				while($test_result_row = $test_result_result->fetchArray())
				{
					if(strtotime($test_result_row['UploadTime']) < $time_limit)
					{
						break;
					}
					if($results > 100)
					{
						break;
					}
					$main .= '<a href="?result/' . $test_result_row['PPRID'] . '"><li id="result_select_' . $test_result_row['PPRID'] . '">' . $test_result_row['Title'] . '<br /><table><tr><td>' . phoromatic_system_id_to_name($test_result_row['SystemID']) . '</td><td>' . phoromatic_user_friendly_timedate($test_result_row['UploadTime']) .  '</td><td>' . $test_result_row['TimesViewed'] . ' Times Viewed</tr>


<tr class="tb_compare_bar"><td><a id="result_compare_link_' . $test_result_row['PPRID'] . '" onclick="javascript:phoromatic_add_to_result_comparison(\'' . $test_result_row['PPRID'] . '\'); return false;">Add To Comparison</a> &nbsp; &nbsp; <a id="result_run_compare_link_' . $test_result_row['PPRID'] . '" onclick="javascript:phoromatic_generate_comparison(); return false;" style="visibility: hidden;">Compare Results</a> &nbsp; &nbsp; <a id="result_delete_link_' . $test_result_row['PPRID'] . '" onclick="javascript:phoromatic_delete_result(\'' . $test_result_row['PPRID'] . '\'); return false;">Delete Result</a></td></tr>

</table></li></a>';
					$results++;

				}
				if($results == 0)
				{
					$main .= '<li class="light" style="text-align: center;">No Results Found</li>';
				}
				$main .= '</ul></div>';
				$main .= '</div>';
				$main .= '<div style="text-align: right;">Show Results For: <select id="result_time_limit" onchange="javascript:document.location.href = \'/?results/&time=\' + document.getElementById(\'result_time_limit\').options[document.getElementById(\'result_time_limit\').options.selectedIndex].value;">
				<option value=""></option>
				<option value="24 hours">Past 24 Hours</option>
				<option value="3 days">Past Three Days</option>
				<option value="1 week">Past Week</option>
				<option value="2 week">Past Two Weeks</option>
				<option value="1 month">Past Month</option>
				<option value="2 months">Past Two Months</option>
				<option value="3 months">Past Quarter</option>
				<option value="6 months">Past Six Months</option>
				<option value="1 year">Past Year</option>
				</select></div>';

				$result_share_opt = phoromatic_server::read_setting('force_result_sharing') ? '1 = 1' : 'AccountID IN (SELECT AccountID FROM phoromatic_account_settings WHERE LetOtherGroupsViewResults = "1")';
				$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, ScheduleID, PPRID, UploadTime, TimesViewed, AccountID FROM phoromatic_results WHERE ' . $result_share_opt . ' AND AccountID != :account_id ORDER BY UploadTime DESC');
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$test_result_result = $stmt->execute();
				if(!empty($test_result_result) && ($test_result_row = $test_result_result->fetchArray()))
				{
					$main .= '<div class="pts_phoromatic_info_box_area">';
					$main .= '<div style="margin: 0 5%;"><ul style="max-height: 100%;"><li><h1>Results Shared By Other Groups</h1></li>';
					$results = 0;
					do
					{
						if(strtotime($test_result_row['UploadTime']) < $time_limit)
						{
							break;
						}
						if($results > 100)
						{
							break;
						}

						$main .= '<a href="?result/' . $test_result_row['PPRID'] . '"><li id="result_select_' . $test_result_row['PPRID'] . '">' . $test_result_row['Title'] . '<br /><table><tr><td><strong>' . phoromatic_account_id_to_group_name($test_result_row['AccountID']) . '</strong></td><td>' . phoromatic_system_id_to_name($test_result_row['SystemID'], $test_result_row['AccountID']) . '</td><td>' . phoromatic_user_friendly_timedate($test_result_row['UploadTime']) .  '</td></tr>


<tr class="tb_compare_bar"><td><a id="result_compare_link_' . $test_result_row['PPRID'] . '" onclick="javascript:phoromatic_add_to_result_comparison(\'' . $test_result_row['PPRID'] . '\'); return false;">Add To Comparison</a> &nbsp; &nbsp; <a id="result_run_compare_link_' . $test_result_row['PPRID'] . '" onclick="javascript:phoromatic_generate_comparison(); return false;" style="visibility: hidden;">Compare Results</a></td></tr>

</table></li></a>';
						$results++;
					}
					while($test_result_row = $test_result_result->fetchArray());
					$main .= '</ul></div>';
				}
			}

			echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in());
			echo phoromatic_webui_footer();
	}
}

?>
