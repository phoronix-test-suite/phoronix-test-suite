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
$result_ids = array();
$account_id = false;

if(isset($_GET['t']) && $_GET['t'] == 'result' && isset($_GET['h']) && isset($_GET['ut']))
{
	$stmt = phoromatic_server::$db->prepare('SELECT UploadID, AccountID FROM phoromatic_results WHERE XmlUploadHash = :hash AND UploadTime = :time');
	$stmt->bindValue(':hash', $_GET['h']);
	$stmt->bindValue(':time', base64_decode($_GET['ut']));
	$test_result_result = $stmt->execute();

	while($test_result_row = $test_result_result->fetchArray())
	{
		array_push($result_ids, $test_result_row['UploadID']);
		$account_id = $test_result_row['AccountID'];
	}
}

$main = null;
if(!empty($result_ids))
{
	$result_file = array();
	$display_rows = array();
	$system_types = array();
	$schedule_types = array();
	$trigger_types = array();

	foreach($result_ids as $upload_id)
	{
		$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_results WHERE AccountID = :account_id AND UploadID = :upload_id LIMIT 1');
		$stmt->bindValue(':account_id', $account_id);
		$stmt->bindValue(':upload_id', $upload_id);
		$result = $stmt->execute();
		$row = $result->fetchArray();

		$composite_xml = phoromatic_server::phoromatic_account_result_path($account_id, $upload_id) . 'composite.xml';
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
	if(count($system_types) == 1)
	{
		$result_file_title = phoromatic_system_id_to_name($system_types[0]) . ' Tests';
	}
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
		$result_file_title = phoromatic_schedule_id_to_name($schedule_types[0]);
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
				$system_name = phoromatic_system_id_to_name($row['SystemID']);
				break;
			case 'TRIGGER':
				$system_name = $row['Trigger'];
				break;
			case 'TRIGGER_AND_SYSTEM':
				$system_name = phoromatic_system_id_to_name($row['SystemID']) . ': ' . $row['Trigger'];
				break;
			case 'SYSTEM_AND_SCHEDULE':
				$system_name = phoromatic_schedule_id_to_name($row['ScheduleID']) . ': ' . $row['Trigger'];
				break;
			default:
				$system_name = phoromatic_system_id_to_name($row['SystemID']) . ' - ' . phoromatic_schedule_id_to_name($row['ScheduleID']) . ' - ' . $row['Trigger'];
		}

		array_push($result_file, new pts_result_merge_select($composite_xml, null, $system_name));
		}

		$writer = new pts_result_file_writer(null);
		$attributes = array('new_result_file_title' => $result_file_title);
		pts_merge::merge_test_results_process($writer, $result_file, $attributes);
		$result_file = new pts_result_file($writer->get_xml());
		$extra_attributes = array();

		$attribute_options = array(
			'normalize_results' => 'normalize_result_buffer',
			'sort_by_performance' => 'sort_result_buffer_values',
			'sort_by_reverse' => 'reverse_result_buffer',
			'sort_by_name' => 'sort_result_buffer',
			'condense_comparison' => 'condense_multi_way',
			);
		foreach($attribute_options as $web_var => $attr_var)
		{
			if(isset($_POST[$web_var]))
			{
				$extra_attributes[$attr_var] = true;
			}
		}

		if(isset($_POST['transpose_comparison']))
		{
			$result_file->invert_multi_way_invert();
		}
		$intent = null;
		$main .= '<h1>' . $result_file->get_title() . '</h1>';
		$main .= '<p>' . $result_file->get_description() . '</p>';

		if($result_file->get_system_count() == 1 || ($intent = pts_result_file_analyzer::analyze_result_file_intent($result_file, $intent, true)))
		{
			$table = new pts_ResultFileCompactSystemsTable($result_file, $intent);
		}
		else
		{
			$table = new pts_ResultFileSystemsTable($result_file);
		}

		$main .= '<p style="text-align: center; overflow: auto;" class="result_object">' . pts_render::render_graph_inline_embed($table, $result_file, $extra_attributes) . '</p>';

		$table = new pts_ResultFileTable($result_file, $intent);
		$main .= '<p style="text-align: center; overflow: auto;" class="result_object">' . pts_render::render_graph_inline_embed($table, $result_file, $extra_attributes) . '</p>';

		foreach($result_file->get_result_objects((isset($_POST['show_only_changed_results']) ? 'ONLY_CHANGED_RESULTS' : -1)) as $i => $result_object)
		{
			$main .= '<h2><a name="r-' . $i . '"></a><a name="' . $result_object->get_comparison_hash(true, false) . '"></a>' . $result_object->test_profile->get_title() . '</h2>';
			$main .= '<p style="text-align: center; overflow: auto;">';
			$main .= pts_render::render_graph_inline_embed($result_object, $result_file, $extra_attributes);
			$main .= '</p>';
		}
	}

echo $main;

echo phoromatic_webui_footer();
?>
</body>
</html>
