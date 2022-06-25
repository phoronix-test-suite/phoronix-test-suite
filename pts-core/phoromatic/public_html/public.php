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

include('../phoromatic_functions.php');
phoromatic_init_web_page_setup();
phoromatic_server::prepare_database();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<script src="/phoromatic.js?<?php echo date('Ymd') . PTS_CORE_VERSION; ?>" type="text/javascript"></script>
<title>Phoronix Test Suite - Phoromatic </title>
<link href="/phoromatic.css?<?php echo date('Ymd') . PTS_CORE_VERSION; ?>" rel="stylesheet" type="text/css" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="keywords" content="Phoronix Test Suite, open-source benchmarking, Linux benchmarking, automated testing" />
<meta name="Description" content="Phoronix Test Suite local control server." />
<link rel="shortcut icon" href="favicon.ico" />
<!-- PHXCMS-7.2 (phoronix.com) -->
</head>
<body>

<?php

echo phoromatic_webui_header(array(''), '');
$result_ids = isset($_GET['ut']) ? explode(',', $_GET['ut']) : false;
if(!$result_ids)
{
	if(($x = strpos($_SERVER['QUERY_STRING'], 'result/')) !== false)
	{
		$x = substr($_SERVER['QUERY_STRING'], $x + strlen('result/'));
		$result_ids = explode(',', $x);
	}
}
$account_id = false;

$main = null;
if(!empty($result_ids))
{
	$result_files = array();
	$display_rows = array();
	$system_types = array();
	$schedule_types = array();
	$trigger_types = array();

	foreach($result_ids as $upload_id)
	{
		$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_results WHERE PPRID = :pprid LIMIT 1');
		$stmt->bindValue(':pprid', $upload_id);
		$result = $stmt->execute();
		$row = $result->fetchArray();

		$composite_xml = phoromatic_server::phoromatic_account_result_path($row['AccountID'], $row['UploadID']) . 'composite.xml';
		if(!is_file($composite_xml))
		{
			echo 'File Not Found: ' . $composite_xml;
			return false;
		}
		$display_rows[$composite_xml] = $row;
		pts_arrays::unique_push($system_types, $row['SystemID']);
		pts_arrays::unique_push($schedule_types, $row['ScheduleID']);
		pts_arrays::unique_push($trigger_types, $row['Trigger']);

		// Update view counter
		$stmt_view = phoromatic_server::$db->prepare('UPDATE phoromatic_results SET TimesViewed = (TimesViewed + 1) WHERE AccountID = :account_id AND UploadID = :upload_id');
		$stmt_view->bindValue(':account_id', $account_id);
		$stmt_view->bindValue(':upload_id', $upload_id);
		$stmt_view->execute();
	}

	$result_file_title = null;
	if(empty($schedule_types[0]))
	{
		$system_name_format = 'ORIGINAL_DATA';
	}
	else if(count($display_rows) == 1)
	{
		$system_name_format = 'SYSTEM_NAME';
	}
	else if(count($schedule_types) == 1 && count($system_types) == 1)
	{
		$system_name_format = 'TRIGGER';
		$result_file_title = phoromatic_server::schedule_id_to_name($schedule_types[0]);
	}
	else if(count($schedule_types) == 1)
	{
		$system_name_format = 'TRIGGER_AND_SYSTEM';
	}
	else if(false && count($trigger_types) == 1)
	{
		// TODO XXX: this approach yields garbage strings generally without refining the selector
		// i.e. first make sure all the schedules match or are comparable
		$system_name_format = 'SYSTEM_AND_SCHEDULE';
	}
	else
	{
		$system_name_format = null;
	}
	if(count($schedule_types) == 1 && $schedule_types[0] != 0)
	{
		$schedule_id = $schedule_types[0];
	}

	foreach($display_rows as $composite_xml => $row)
	{
		//  $row['SystemID'] . ' ' . $row['ScheduleID'] . ' ' . $row['Trigger']
		switch($system_name_format)
		{
			case 'ORIGINAL_DATA':
				$system_name = null;
				break;
			case 'SYSTEM_NAME':
				$system_name = phoromatic_server::system_id_to_name($row['SystemID'], $row['AccountID']);
				break;
			case 'TRIGGER':
				$system_name = $row['Trigger'];
				break;
			case 'TRIGGER_AND_SYSTEM':
				$system_name = phoromatic_server::system_id_to_name($row['SystemID'], $row['AccountID']) . ': ' . $row['Trigger'];
				break;
			case 'SYSTEM_AND_SCHEDULE':
				$system_name = phoromatic_server::schedule_id_to_name($row['ScheduleID']) . ': ' . $row['Trigger'];
				break;
			default:
				$system_name = phoromatic_server::system_id_to_name($row['SystemID'], $row['AccountID']) . ' - ' . phoromatic_server::schedule_id_to_name($row['ScheduleID']) . ' - ' . $row['Trigger'];
		}

		$rf = new pts_result_file($composite_xml);
		$rf->rename_run(null, $system_name);
		$result_files[] = $rf;
	}

	$attributes = array('new_result_file_title' => $result_file_title);
	$result_file = new pts_result_file(null, true);
	$result_file->merge($result_files, $attributes);
	$extra_attributes = array();

	$embed = new pts_result_viewer_embed($result_file);
	$embed->show_html_result_table(false);
	$embed->show_test_metadata_helper(false);
	$embed->include_page_print_only_helpers(false);
	$main .= $embed->get_html();
}
else
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
	$main .= '<br /><br /><br />';
	$main .= '<form method="post"><div style="text-align: left; font-weight: bold;">Show Results For <select id="result_time_limit" name="time">';
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
	$main .= '<a onclick="javascript:phoromatic_generate_comparison(\'public.php?ut=\');"><div id="phoromatic_result_compare_info_box" style="background: #1976d2; border: 1px solid #000;"></div></a>';
	$main .= '<h1>Publicly Accessible Test Results</h1>';
	$main .= '<p><em>Results where the accounts on this server have opted for the settings page item of making results public.</em></p>';
	$main .= '<div class="pts_phoromatic_info_box_area">';
	$search_for = (!isset($_POST['search']) || empty($_POST['search']) ? null : 'AND (Title LIKE :search OR Description LIKE :search OR UploadID IN (SELECT UploadID FROM phoromatic_results_systems WHERE AccountID = :account_id AND (Software LIKE :search OR Hardware LIKE :search)))');
	$main .= '<div style="margin: 0 5%;"><ul style="max-height: 100%;"><li><h1>Recent Test Results</h1></li>';
	$account_limit = ' AccountID IN (SELECT AccountID FROM phoromatic_account_settings WHERE LetPublicViewResults = 1) ';

	if(isset($PATH[1]) && $PATH[0] == 'hash')
	{
		// Find matching comparison hashes
		$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, ScheduleID, PPRID, UploadTime, TimesViewed, AccountID FROM phoromatic_results WHERE ' . $account_limit . ' ' . $search_for. ' AND ComparisonHash = :comparison_hash ORDER BY UploadTime DESC LIMIT ' . $result_limit);
		$stmt->bindValue(':comparison_hash', $PATH[1]);
	}
	else if(isset($PATH[1]) && $PATH[0] == 'ticket')
	{
		// Find matching ticket results
		$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, ScheduleID, PPRID, UploadTime, TimesViewed, AccountID FROM phoromatic_results WHERE ' . $account_limit . $search_for. ' AND BenchmarkTicketID = :ticket_id ORDER BY UploadTime DESC LIMIT ' . $result_limit);
		$stmt->bindValue(':ticket_id', $PATH[1]);
	}
	else
	{
		$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, ScheduleID, PPRID, UploadTime, TimesViewed, AccountID FROM phoromatic_results WHERE ' . $account_limit . ' ' . $search_for. ' ORDER BY UploadTime DESC LIMIT ' . $result_limit);
	}

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

		$main .= '<a onclick=""><li id="result_select_' . $test_result_row['PPRID'] . '"><input type="checkbox" id="result_compare_checkbox_' . $test_result_row['PPRID'] . '" onclick="javascript:phoromatic_checkbox_toggle_result_comparison(\'' . $test_result_row['PPRID'] . '\');" onchange="return false;"></input> <span onclick="javascript:phoromatic_window_redirect(\'public.php?ut=' . $test_result_row['PPRID'] . '\');">' . $test_result_row['Title'] . '</span><br /><table><tr><td>' . phoromatic_server::system_id_to_name($test_result_row['SystemID'], $test_result_row['AccountID']) . '</td><td>' . phoromatic_server::user_friendly_timedate($test_result_row['UploadTime']) .  '</td><td>' . $test_result_row['TimesViewed'] . ' Times Viewed</td></table></li></a>';
		$results++;
	}
	if($results == 0)
	{
		$main .= '<li class="light" style="text-align: center;">No Results Found</li>';
	}
	$main .= '</ul></div>';
	$main .= '</div>';
}


echo phoromatic_webui_main($main);

echo phoromatic_webui_footer();
?>
</body>
</html>
