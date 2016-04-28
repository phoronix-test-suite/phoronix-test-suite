<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2016, Phoronix Media
	Copyright (C) 2008 - 2016, Michael Larabel

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
					$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_results WHERE AccountID = :account_id AND PPRID = :pprid LIMIT 1');
					$stmt->bindValue(':account_id', $_SESSION['AccountID']);
					$stmt->bindValue(':pprid', $pprid);
					$result = $stmt->execute();
					if($result && ($row = $result->fetchArray()))
					{
						$composite_xml = phoromatic_server::phoromatic_account_result_path($_SESSION['AccountID'], $row['UploadID']) . 'composite.xml';
						if(is_file($composite_xml))
						{
							unlink($composite_xml);
						}

						pts_file_io::delete(phoromatic_server::phoromatic_account_result_path($_SESSION['AccountID'], $row['UploadID']), null, true);

						$stmt = phoromatic_server::$db->prepare('DELETE FROM phoromatic_results_results WHERE AccountID = :account_id AND UploadID = :upload_id');
						$stmt->bindValue(':account_id', $_SESSION['AccountID']);
						$stmt->bindValue(':upload_id', $row['UploadID']);
						$result = $stmt->execute();

						$stmt = phoromatic_server::$db->prepare('DELETE FROM phoromatic_results_systems WHERE AccountID = :account_id AND UploadID = :upload_id');
						$stmt->bindValue(':account_id', $_SESSION['AccountID']);
						$stmt->bindValue(':upload_id', $row['UploadID']);
						$result = $stmt->execute();


					}

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
				if(isset($_POST['time']))
				{
					$time_str = $_POST['time'];
					$time_limit = strtotime('- ' . $time_str);
				}
				if($time_limit == false)
				{
					$time_str = '1 month';
					$time_limit = strtotime('- ' . $time_str);
				}

				$result_limit = isset($_POST['result_limit']) && is_numeric($_POST['result_limit']) && $_POST['result_limit'] > 9 ? $_POST['result_limit'] : 50;

				$main .= '<form action="' . $_SERVER['REQUEST_URI'] . '" method="post"><div style="text-align: left; font-weight: bold;">Show Results For <select id="result_time_limit" name="time">';

				$results_for_length = array(
					'24 hours' => '24 Hours',
					'3 days' => '3 Days',
					'1 week' => 'Week',
					'2 week' => '2 Weeks',
					'1 month' => 'Month',
					'2 months' => '2 Months',
					'3 months' => 'Quarter',
					'6 months' => '6 Months',
					'1 year' => 'Year',
					'2 year' => 'Two Years',
					);

				foreach($results_for_length as $val => $str)
				{
					$main .= '<option value="' . $val . '"' . ($time_str == $val ? ' selected="selected"' : null) . '>Past ' . $str . '</option>';
				}

				$main .= '</select> Search For <input type="text" name="search" value="' . (isset($_POST['search']) ? $_POST['search'] : null) . '" /> &nbsp; Limit Results To <select id="result_limit" name="result_limit">';
				for($i = 25; $i <= 150; $i += 25)
				{
					$main .= '<option value="' . $i . '"' . ($result_limit == $i ? ' selected="selected"' : null) . '>' . $i . '</option>';
				}

				$main .= '</select> &nbsp; <input type="submit" value="Update" /></div></form>';

				$main .= '<h1>Account Test Results</h1>';
				$main .= '<div class="pts_phoromatic_info_box_area">';
				$search_for = (!isset($_POST['search']) || empty($_POST['search']) ? null : 'AND (Title LIKE :search OR Description LIKE :search OR UploadID IN (SELECT UploadID FROM phoromatic_results_systems WHERE AccountID = :account_id AND (Software LIKE :search OR Hardware LIKE :search)))');
				$main .= '<div style="margin: 0 5%;"><ul style="max-height: 100%;"><li><h1>Recent Test Results</h1></li>';

				if(isset($PATH[1]) && $PATH[0] == 'hash')
				{
					// Find matching comparison hashes
					$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, ScheduleID, PPRID, UploadTime, TimesViewed FROM phoromatic_results WHERE AccountID = :account_id ' . $search_for. ' AND ComparisonHash = :comparison_hash ORDER BY UploadTime DESC LIMIT ' . $result_limit);
					$stmt->bindValue(':comparison_hash', $PATH[1]);
				}
				else if(isset($PATH[1]) && $PATH[0] == 'ticket')
				{
					// Find matching ticket results
					$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, ScheduleID, PPRID, UploadTime, TimesViewed FROM phoromatic_results WHERE AccountID = :account_id ' . $search_for. ' AND BenchmarkTicketID = :ticket_id ORDER BY UploadTime DESC LIMIT ' . $result_limit);
					$stmt->bindValue(':ticket_id', $PATH[1]);
				}
				else
				{
					$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, ScheduleID, PPRID, UploadTime, TimesViewed FROM phoromatic_results WHERE AccountID = :account_id ' . $search_for. ' ORDER BY UploadTime DESC LIMIT ' . $result_limit);
				}

				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$stmt->bindValue(':search', (isset($_POST['search']) ? '%' . $_POST['search'] . '%' : null));
				$test_result_result = $stmt->execute();
				$results = 0;
				while($test_result_row = $test_result_result->fetchArray())
				{
					if(strtotime($test_result_row['UploadTime']) < $time_limit)
					{
						break;
					}
					if($results > 150)
					{
						break;
					}
					$main .= '<a onclick=""><li id="result_select_' . $test_result_row['PPRID'] . '"><input type="checkbox" id="result_compare_checkbox_' . $test_result_row['PPRID'] . '" onclick="javascript:phoromatic_checkbox_toggle_result_comparison(\'' . $test_result_row['PPRID'] . '\');" onchange="return false;"></input> <span onclick="javascript:phoromatic_window_redirect(\'?result/' . $test_result_row['PPRID'] . '\');">' . $test_result_row['Title'] . '</span><br /><table><tr><td>' . phoromatic_system_id_to_name($test_result_row['SystemID']) . '</td><td>' . phoromatic_user_friendly_timedate($test_result_row['UploadTime']) .  '</td><td>' . $test_result_row['TimesViewed'] . ' Times Viewed</td></table></li></a>';
					$results++;
				}
				if($results == 0)
				{
					$main .= '<li class="light" style="text-align: center;">No Results Found</li>';
				}
				else if($results > 3)
				{
					$main .= '<a onclick=""><li id="global_bottom_totals"><input type="checkbox" id="global_checkbox" onclick="javascript:phoromatic_toggle_checkboxes_on_page(this);" onchange="return false;"></input> <strong>' . $results . ' Results</strong></li></a>';
				}
				$main .= '</ul></div>';
				$main .= '</div>';

				$result_share_opt = phoromatic_server::read_setting('force_result_sharing') ? '1 = 1' : 'AccountID IN (SELECT AccountID FROM phoromatic_account_settings WHERE LetOtherGroupsViewResults = "1")';
				$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, ScheduleID, PPRID, UploadTime, TimesViewed, AccountID FROM phoromatic_results WHERE ' . $result_share_opt . ' AND AccountID != :account_id ' . $search_for. ' ORDER BY UploadTime DESC LIMIT ' . $result_limit);
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$stmt->bindValue(':search', (isset($_POST['search']) ? '%' . $_POST['search'] . '%' : null));
				$test_result_result = $stmt->execute();
				if(!empty($test_result_result) && ($test_result_row = $test_result_result->fetchArray()))
				{
					$main .= '<div class="pts_phoromatic_info_box_area">';
					$main .= '<ul style="max-height: 100%;"><li><h1>Results Shared By Other Groups</h1></li>';
					$results = 0;
					do
					{
						if(strtotime($test_result_row['UploadTime']) < $time_limit)
						{
							break;
						}
						if($results > 150)
						{
							break;
						}
						$main .= '<a onclick=""><li id="result_select_' . $test_result_row['PPRID'] . '"><input type="checkbox" id="result_compare_checkbox_' . $test_result_row['PPRID'] . '" onclick="javascript:phoromatic_checkbox_toggle_result_comparison(\'' . $test_result_row['PPRID'] . '\');" onchange="return false;"></input> <span onclick="javascript:phoromatic_window_redirect(\'?result/' . $test_result_row['PPRID'] . '\');">' . $test_result_row['Title'] . '</span><br /><table><tr><td><strong>' . phoromatic_account_id_to_group_name($test_result_row['AccountID']) . '</strong></td><td>' . phoromatic_system_id_to_name($test_result_row['SystemID'], $test_result_row['AccountID']) . '</td><td>' . phoromatic_user_friendly_timedate($test_result_row['UploadTime']) .  '</td><td>' . $test_result_row['TimesViewed'] . ' Times Viewed</td></table></li></a>';
						$results++;
					}
					while($test_result_row = $test_result_result->fetchArray());
					$main .= '</ul></div>';
				}
			}

			echo phoromatic_webui_main($main);
			echo phoromatic_webui_footer();
	}
}

?>
