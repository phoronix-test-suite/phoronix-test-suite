<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2022, Phoronix Media
	Copyright (C) 2008 - 2022, Michael Larabel

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
			if(isset($_POST) && !empty($_POST) && !verify_submission_token())
			{
				echo '<h2>Invalid Form Submission.</h2>';
				exit;
			}

			phoromatic_quit_if_invalid_input_found(array('result_limit', 'containing_tests', 'time_end', 'time_start', 'search', 'containing_hardware', 'containing_software'));
			if(isset($_POST['result_limit']))
			{
				if(is_numeric($_POST['result_limit']) && $_POST['result_limit'] > 9)
				{
					$result_limit = $_POST['result_limit'];
				}
				else
				{
					$result_limit = 0;
				}
			}
			else
			{
				$result_limit = 100;
			}
			$min_date = strtotime(phoromatic_server::account_created_on($_SESSION['AccountID']));
			$default_start_date = max($min_date, strtotime('-1 year'));
			$min_date = date('Y-m-d', $min_date);
			$time_start = strtotime(isset($_POST['time_start']) && !empty($_POST['time_start']) ? $_POST['time_start'] : $min_date);
			if(empty($time_start))
			{
				$time_start = strtotime($min_date);
			}
			$time_end = strtotime((isset($_POST['time_end']) && !empty($_POST['time_end']) ? $_POST['time_end'] : date('Y-m-d')) . ' 23:59:59');
			if(empty($time_end))
			{
				$time_end = strtotime(date('Y-m-d') . ' 23:59:59');
			}
			$main .= '<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">' . write_token_in_form() . '<div style="text-align: left; font-weight: bold;">Results From <input id="time_start" name="time_start" type="date" required pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" min="' . $min_date . '" value="' . (isset($_POST['time_start']) ? $_POST['time_start'] : date('Y-m-d', $default_start_date)) . '" max="' . date('Y-m-d') . '" /> To  <input id="time_end" name="time_end" type="date" required pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" min="' . $min_date . '" value="' . (isset($_POST['time_end']) ? $_POST['time_end'] : date('Y-m-d')) . '" max="' . date('Y-m-d') . '" /> &nbsp; With Tests: <input type="text" name="containing_tests" id="containing_tests" value="' . (isset($_POST['containing_tests']) ? $_POST['containing_tests'] : null) . '" /> With Hardware: <input type="text" name="containing_hardware" id="containing_hardware" value="' . (isset($_POST['containing_hardware']) ? $_POST['containing_hardware'] : null) . '" /> With System Software: <input type="text" name="containing_software" id="containing_software" value="' . (isset($_POST['containing_software']) ? $_POST['containing_software'] : null) . '" /> Search For <input type="text" name="search" id="search_for" value="' . (isset($_POST['search']) ? $_POST['search'] : null) . '" /> Limit Results To <select id="result_limit" name="result_limit">';
			for($i = 100; $i <= 500; $i += 100)
			{
				$main .= '<option value="' . $i . '"' . ($result_limit == $i ? ' selected="selected"' : null) . '>' . $i . '</option>';
			}
			$main .= '<option value=""' . (isset($_POST['result_limit']) && empty($result_limit) ? ' selected="selected"' : null) . '>No Limit</option>';
			$main .= '</select> &nbsp; <input type="button" value="Reset" onclick="phoromatic_clear_results_search_fields();" />';
			$main .= ' &nbsp; <input type="submit" value="Update" /></div></form>';
			$main .= '<p style="font-size: 90%;">** <em>AND</em>, <em>OR</em>, and <em>NOT</em> search operators supported for tests/hardware/software search fields. **</p>';
			$main .= '<h1>Account Test Results</h1>';
			$main .= '<div class="pts_phoromatic_info_box_area">';
			$search_for = (!isset($_POST['search']) || empty($_POST['search']) ? null : 'AND (Title LIKE :search OR Description LIKE :search OR UploadID IN (SELECT UploadID FROM phoromatic_results_systems WHERE AccountID = :account_id AND (Software LIKE :search OR Hardware LIKE :search)))');
			if(isset($_POST['containing_hardware']) && !empty($_POST['containing_hardware']))
			{
				$hw_advanced_query = stripos($_POST['containing_hardware'], ' AND ') !== false || stripos($_POST['containing_hardware'], ' OR ') !== false || stripos($_POST['containing_hardware'], ' NOT ') !== false;
				if($hw_advanced_query || true)
				{
					$hw_advanced_query = pts_phoroql::search_query_to_tree($_POST['containing_hardware']);
				}
				else
				{
					$search_for .= ' AND UploadID IN (SELECT UploadID FROM phoromatic_results_systems WHERE AccountID = :account_id AND Hardware LIKE :containing_hardware)';
				}
			}
			if(isset($_POST['containing_software']) && !empty($_POST['containing_software']))
			{
				$sw_advanced_query = strpos($_POST['containing_software'], ' AND ') !== false || strpos($_POST['containing_software'], ' OR ') !== false || strpos($_POST['containing_software'], ' NOT ') !== false;
				if($sw_advanced_query || true)
				{
					$sw_advanced_query = pts_phoroql::search_query_to_tree($_POST['containing_software']);
				}
				else
				{
					$search_for .= ' AND UploadID IN (SELECT UploadID FROM phoromatic_results_systems WHERE AccountID = :account_id AND Software LIKE :containing_software)';
				}
			}
			$main .= '<div style="margin: 0 5%;"><ul style="max-height: 100%;"><li><h1>Recent Test Results</h1></li>';

			if(isset($PATH[1]) && $PATH[0] == 'hash')
			{
				// Find matching comparison hashes
				$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, ScheduleID, PPRID, UploadTime, TimesViewed, AccountID, UploadID FROM phoromatic_results WHERE AccountID = :account_id ' . $search_for. ' AND ComparisonHash = :comparison_hash ORDER BY UploadTime DESC');
				$stmt->bindValue(':comparison_hash', $PATH[1]);
			}
			else if(isset($PATH[1]) && $PATH[0] == 'ticket')
			{
				// Find matching ticket results
				$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, ScheduleID, PPRID, UploadTime, TimesViewed, AccountID, UploadID FROM phoromatic_results WHERE AccountID = :account_id ' . $search_for. ' AND BenchmarkTicketID = :ticket_id ORDER BY UploadTime DESC');
				$stmt->bindValue(':ticket_id', $PATH[1]);
			}
			else
			{
				$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, ScheduleID, PPRID, UploadTime, TimesViewed, AccountID, UploadID FROM phoromatic_results WHERE AccountID = :account_id ' . $search_for. ' ORDER BY UploadTime DESC');
			}

			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$stmt->bindValue(':search', (isset($_POST['search']) ? '%' . $_POST['search'] . '%' : null));
			$stmt->bindValue(':containing_hardware', (isset($_POST['containing_hardware']) ? '%' . $_POST['containing_hardware'] . '%' : null));
			$stmt->bindValue(':containing_software', (isset($_POST['containing_software']) ? '%' . $_POST['containing_software'] . '%' : null));
			$test_result_result = $stmt->execute();
			$results = 0;
			$containing_tests = isset($_POST['containing_tests']) ? $_POST['containing_tests'] : null;
			if(!empty($containing_tests))
			{
				$containing_tests = pts_phoroql::search_query_to_tree($containing_tests);
			}

			while($test_result_row = $test_result_result->fetchArray())
			{
				if(strtotime($test_result_row['UploadTime']) > $time_end)
				{
					continue;
				}
				if(strtotime($test_result_row['UploadTime']) < $time_start)
				{
					//break;
				}
				if(!empty($result_limit) && $result_limit > 1 && $result_limit == $results)
				{
					break;
				}

				$composite_xml = phoromatic_server::phoromatic_account_result_path($test_result_row['AccountID'], $test_result_row['UploadID']) . 'composite.xml';
				$result_file = new pts_result_file($composite_xml);

				if(isset($_POST['containing_hardware']) && !empty($_POST['containing_hardware']) && $hw_advanced_query)
				{
					//if(!$result_file->contains_system_hardware($_POST['containing_hardware']))
					if(!pts_phoroql::evaluate_search_tree($hw_advanced_query, 'AND', array($result_file, 'contains_system_hardware')))
					{
						continue;
					}
				}
				if(isset($_POST['containing_software']) && !empty($_POST['containing_software']) && $sw_advanced_query)
				{
					if(!pts_phoroql::evaluate_search_tree($sw_advanced_query, 'AND', array($result_file, 'contains_system_software')))
					{
						continue;
					}
				}
				if(!empty($containing_tests))
				{
					if(!pts_phoroql::evaluate_search_tree($containing_tests, 'AND', array($result_file, 'contains_test')))
					{
						continue;
					}
				}

				$main .= '<a onclick=""><li id="result_select_' . $test_result_row['PPRID'] . '"><input type="checkbox" id="result_compare_checkbox_' . $test_result_row['PPRID'] . '" onclick="javascript:phoromatic_checkbox_toggle_result_comparison(\'' . $test_result_row['PPRID'] . '\');" onchange="return false;"></input> <span onclick="javascript:phoromatic_window_redirect(\'?result/' . $test_result_row['PPRID'] . '\');">' . $test_result_row['Title'] . '</span><br /><table><tr><td>' . phoromatic_server::system_id_to_name($test_result_row['SystemID']) . '</td><td>' . phoromatic_server::user_friendly_timedate($test_result_row['UploadTime']) .  '</td><td>' . $test_result_row['TimesViewed'] . ' Times Viewed</td></table></li></a>';
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
			$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, ScheduleID, PPRID, UploadTime, TimesViewed, AccountID, UploadID FROM phoromatic_results WHERE ' . $result_share_opt . ' AND AccountID != :account_id ' . $search_for. ' ORDER BY UploadTime DESC');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$stmt->bindValue(':search', (isset($_POST['search']) ? '%' . $_POST['search'] . '%' : null));
			$stmt->bindValue(':containing_hardware', (isset($_POST['containing_hardware']) ? '%' . $_POST['containing_hardware'] . '%' : null));
			$stmt->bindValue(':containing_software', (isset($_POST['containing_software']) ? '%' . $_POST['containing_software'] . '%' : null));
			$test_result_result = $stmt->execute();
			if(!empty($test_result_result) && ($test_result_row = $test_result_result->fetchArray()))
			{
				$main .= '<div class="pts_phoromatic_info_box_area">';
				$main .= '<ul style="max-height: 100%;"><li><h1>Results Shared By Other Groups</h1></li>';
				$results = 0;
				do
				{
					if(strtotime($test_result_row['UploadTime']) > $time_end)
					{
						continue;
					}
					if(strtotime($test_result_row['UploadTime']) < $time_start)
					{
						//break;
					}
					if(!empty($result_limit) && $result_limit > 1 && $result_limit == $results)
					{
						break;
					}

					$composite_xml = phoromatic_server::phoromatic_account_result_path($test_result_row['AccountID'], $test_result_row['UploadID']) . 'composite.xml';
					$result_file = new pts_result_file($composite_xml);

					if(isset($_POST['containing_hardware']) && !empty($_POST['containing_hardware']) && $hw_advanced_query)
					{
						//if(!$result_file->contains_system_hardware($_POST['containing_hardware']))
						if(!pts_phoroql::evaluate_search_tree($hw_advanced_query, 'AND', array($result_file, 'contains_system_hardware')))
						{
							continue;
						}
					}
					if(isset($_POST['containing_software']) && !empty($_POST['containing_software']) && $sw_advanced_query)
					{
						if(!pts_phoroql::evaluate_search_tree($sw_advanced_query, 'AND', array($result_file, 'contains_system_software')))
						{
							continue;
						}
					}
					if(!empty($containing_tests))
					{
						if(!pts_phoroql::evaluate_search_tree($containing_tests, 'AND', array($result_file, 'contains_test')))
						{
							continue;
						}
					}

					$main .= '<a onclick=""><li id="result_select_' . $test_result_row['PPRID'] . '"><input type="checkbox" id="result_compare_checkbox_' . $test_result_row['PPRID'] . '" onclick="javascript:phoromatic_checkbox_toggle_result_comparison(\'' . $test_result_row['PPRID'] . '\');" onchange="return false;"></input> <span onclick="javascript:phoromatic_window_redirect(\'?result/' . $test_result_row['PPRID'] . '\');">' . $test_result_row['Title'] . '</span><br /><table><tr><td><strong>' . phoromatic_server::account_id_to_group_name($test_result_row['AccountID']) . '</strong></td><td>' . phoromatic_server::system_id_to_name($test_result_row['SystemID'], $test_result_row['AccountID']) . '</td><td>' . phoromatic_server::user_friendly_timedate($test_result_row['UploadTime']) .  '</td><td>' . $test_result_row['TimesViewed'] . ' Times Viewed</td></table></li></a>';
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
