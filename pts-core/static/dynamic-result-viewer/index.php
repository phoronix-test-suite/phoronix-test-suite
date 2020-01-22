<?php
/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2019 - 2020, Phoronix Media
	Copyright (C) 2019 - 2020, Michael Larabel

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


error_reporting(E_ALL);
if(function_exists('session_start'))
{
	session_start();
}

define('CURRENT_URI', $_SERVER['REQUEST_URI']);
define('WEB_URL_PATH', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/');
define('RESULT_VIEWER_VERSION', 2);
define('PTS_AUTO_LOAD_ALL_OBJECTS', true);

$uri_stripped = CURRENT_URI;
if(($x = strpos($uri_stripped, '&')) !== false)
{
	$args = substr($uri_stripped, $x + 1);
	$r = array();
	parse_str($args, $r);
	$_REQUEST = array_merge($r, $_REQUEST);
	$uri_stripped = substr($uri_stripped, 0, $x);
}

if(isset($_REQUEST['checkbox_compare_results']))
{
	echo '<script> window.location.href = "http://' . $_SERVER['HTTP_HOST'] . WEB_URL_PATH . 'result/' . implode(',', $_REQUEST['checkbox_compare_results']) . '"; </script>';
	exit;
}
$uri_segments = explode('/', trim((WEB_URL_PATH == '/' ? $uri_stripped : str_replace(WEB_URL_PATH, null, $uri_stripped)), '/'));
switch((isset($uri_segments[0]) ? $uri_segments[0] : null))
{
	case 'result':
		$_GET['page'] = 'result';
		$_GET['result'] = $uri_segments[1];
		break;
}

if(getenv('PTS_VIEWER_RESULT_PATH') && getenv('PTS_VIEWER_PTS_PATH'))
{
	define('VIEWER_ACCESS_KEY', getenv('PTS_VIEWER_ACCESS_KEY'));
	define('PTS_SAVE_RESULTS_PATH', getenv('PTS_VIEWER_RESULT_PATH'));
	define('VIEWER_PHORONIX_TEST_SUITE_PATH', getenv('PTS_VIEWER_PTS_PATH'));
}
else
{
	if(!is_file('result_viewer_config.php'))
	{
		echo '<p>You must configure result_viewer_config.php!</p>';
		echo '<p>Current debug values: PTS_VIEWER_RESULT_PATH = ' . getenv('PTS_VIEWER_RESULT_PATH') . ' PTS_VIEWER_PTS_PATH = ' . getenv('PTS_VIEWER_PTS_PATH') . '</p>';
		exit;
	}
	require('result_viewer_config.php');
}

define('PTS_MODE', 'LIB');
define('PTS_AUTO_LOAD_OBJECTS', true);

if(!is_file(VIEWER_PHORONIX_TEST_SUITE_PATH . '/pts-core/pts-core.php'))
{
	echo '<p>Could not find: ' . VIEWER_PHORONIX_TEST_SUITE_PATH . '/pts-core/pts-core.php</p>';
	exit;
}
require(VIEWER_PHORONIX_TEST_SUITE_PATH . '/pts-core/pts-core.php');
pts_define_directories();

set_time_limit(0);
ini_set('memory_limit','2048M');

if(isset($_GET['PTS']))
{
	// Test for client to see if resolving properly
	echo 'PTS';
	exit;
}

pts_config::set_override_default_config(getenv('PTS_VIEWER_CONFIG_FILE'));
if(PTS_SAVE_RESULTS_PATH && is_writable(PTS_SAVE_RESULTS_PATH) && getenv('PTS_VIEWER_CONFIG_FILE'))
{
	define('VIEWER_CAN_MODIFY_RESULTS', pts_config::read_bool_config('PhoronixTestSuite/Options/ResultViewer/AllowSavingResultChanges', 'FALSE'));
	define('VIEWER_CAN_DELETE_RESULTS', pts_config::read_bool_config('PhoronixTestSuite/Options/ResultViewer/AllowDeletingResults', 'FALSE'));
}
else
{
	define('VIEWER_CAN_MODIFY_RESULTS', false);
	define('VIEWER_CAN_DELETE_RESULTS', false);
}


// Authenticate user and set session variables
if(isset($_POST['access_key']))
{
	if(function_exists('hash'))
	{
		$_SESSION['AccessKey'] = trim(hash('sha256', trim($_POST['access_key'])));
	}
	else
	{
		$_SESSION['AccessKey'] = trim(sha1(trim($_POST['access_key'])));
	}
}

if(VIEWER_ACCESS_KEY != null && (!isset($_SESSION['AccessKey']) || $_SESSION['AccessKey'] != VIEWER_ACCESS_KEY)) { ?>
<!doctype html>
<html lang="en">
<head>
  <title>Phoronix Test Suite - Local Result Viewer</title>
<link rel="stylesheet" href="/result-viewer.css">
<script type="text/javascript" src="/result-viewer.js"></script>
<link href="//fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
</head>
<body>

<div id="login_box">
<div id="login_box_left">
<h1>Phoronix Test Suite</h1>
<h2>Local Result Viewer</h2>
</div>
<div id="login_box_right">
<form name="login_form" id="login_form" action="<?php echo CURRENT_URI; ?>" method="post"><br />
<input type="password" name="access_key" id="u_access_key" required placeholder="Access Key" /><br />
<input type="submit" value="Login" />
</form>
</div>
</div>
</body>
<?php } else {
$PAGE = null;
switch(isset($_GET['page']) ? $_GET['page'] : null)
{
	case 'update-result-file-meta':
		if(VIEWER_CAN_MODIFY_RESULTS && isset($_REQUEST['result_file_id']) && isset($_REQUEST['result_title']) && isset($_REQUEST['result_desc']))
		{
			$result_file = new pts_result_file($_REQUEST['result_file_id']);
			$result_file->set_title($_REQUEST['result_title']);
			$result_file->set_description($_REQUEST['result_desc']);
			$result_file->save();
		}
		exit;
	case 'remove-result-object':
		if(VIEWER_CAN_DELETE_RESULTS && isset($_REQUEST['result_file_id']) && isset($_REQUEST['result_object']))
		{
			$result_file = new pts_result_file($_REQUEST['result_file_id']);
			$result_file->remove_result_object_by_id($_REQUEST['result_object']);
			$result_file->save();
		}
		exit;
	case 'add-annotation-to-result-object':
		if(VIEWER_CAN_MODIFY_RESULTS && isset($_REQUEST['result_file_id']) && isset($_REQUEST['result_object']) && isset($_REQUEST['annotation']))
		{
			$result_file = new pts_result_file($_REQUEST['result_file_id']);
			$result_file->update_annotation_for_result_object_by_id($_REQUEST['result_object'], $_REQUEST['annotation']);
			$result_file->save();
		}
		exit;
	case 'result':
		if(false && isset($_POST) && !empty($_POST))
		{
			$req = $_REQUEST;
			unset($req['PHPSESSID']);
			header('Location: ?' . http_build_query($req));
		}
		$result_file = null;
		$result_merges = 0;
		$possible_results = explode(',', $_GET['result']);
		$results_viewing = array();
		foreach($possible_results as $rid)
		{
			if(pts_results::is_saved_result_file($rid))
			{
				if($result_file == null)
				{
					$result_file = new pts_result_file($rid);
					$results_viewing[] = $rid;
					if(count($possible_results) > 1)
					{
						$result_file->rename_run('PREFIX', $result_file->get_title());
					}
				}
				else
				{
					$rf = new pts_result_file($rid);
					$result_file->merge(array(new pts_result_merge_select($rf)), 0, $rf->get_title(), true);
					$result_merges++;
				}
			}
		}
		if($result_file == null)
		{
			break;
		}
		define('RESULTS_VIEWING_COUNT', count($results_viewing));
		define('RESULTS_VIEWING_ID', $results_viewing[0]);
		if($result_merges > 0)
		{
			$result_file->avoid_duplicate_identifiers();
		}

		$extra_attributes = null;
		pts_result_viewer_settings::process_request_to_attributes($_REQUEST, $result_file, $extra_attributes);
		define('TITLE', $result_file->get_title());
		$PAGE .= pts_result_viewer_settings::get_html_sort_bar($result_file, $_REQUEST);
		$PAGE .= '<h1 id="result_file_title" placeholder="Title">' . $result_file->get_title() . '</h1>';
		$PAGE .= '<p id="result_file_desc" placeholder="Description">' . str_replace(PHP_EOL, '<br />', $result_file->get_description()) . '</p>';
		if(VIEWER_CAN_MODIFY_RESULTS && RESULTS_VIEWING_COUNT == 1)
		{
			$PAGE .= ' <input type="submit" id="save_result_file_meta_button" value="Save" onclick="javascript:save_result_file_meta(\'' . RESULTS_VIEWING_ID . '\'); return false;" style="display: none;">';
			$PAGE .= ' <input type="submit" id="edit_result_file_meta_button" value="Edit" onclick="javascript:edit_result_file_meta(); return false;">';
		}
		if(VIEWER_CAN_DELETE_RESULTS && RESULTS_VIEWING_COUNT == 1)
		{
			$PAGE .= ' <input type="submit" value="Delete Result File" onclick="javascript:delete_result_file(\'' . RESULTS_VIEWING_ID . '\'); return false;">';
		}
		//$PAGE .= '<p align="center"><strong>Export As: </strong> <a href="' . CURRENT_URI . '&export=pdf">PDF</a>, <a href="' . CURRENT_URI . '&export=csv">CSV</a>, <a href="' . CURRENT_URI . '&export=csv-all">CSV Individual Data</a> </p>';
		$PAGE .= '<hr /><div style="font-size: 12pt;">' . pts_result_viewer_settings::get_html_options_markup($result_file, $_REQUEST) . '</div><hr />';
		$PAGE .= pts_result_viewer_settings::process_helper_html($_REQUEST, $result_file, $extra_attributes);

		$intent = -1;
		if($result_file->get_system_count() == 1 || ($intent = pts_result_file_analyzer::analyze_result_file_intent($result_file, $intent, true)))
		{
			$table = new pts_ResultFileCompactSystemsTable($result_file, $intent);
		}
		else
		{
			$table = new pts_ResultFileSystemsTable($result_file);
		}
		$PAGE .= '<p style="text-align: center; overflow: auto;" class="result_object">' . pts_render::render_graph_inline_embed($table, $result_file, $extra_attributes) . '</p>';

		if($result_file->get_system_count() == 2)
		{
			$graph = new pts_graph_run_vs_run($result_file);

			if($graph->renderGraph())
			{
				$PAGE .= '<p style="text-align: center; overflow: auto;" class="result_object">' . pts_render::render_graph_inline_embed($graph, $result_file, $extra_attributes) . '</p>';
			}
		}
		else if(!$result_file->is_multi_way_comparison())
		{
			foreach(array('', 'Per Watt', 'Per Dollar') as $selector)
			{
				$graph = new pts_graph_radar_chart($result_file, $selector);

				if($graph->renderGraph())
				{
					$PAGE .= '<p style="text-align: center; overflow: auto;" class="result_object">' . pts_render::render_graph_inline_embed($graph, $result_file, $extra_attributes) . '</p>';
				}
			}
		}

		if(!$result_file->is_multi_way_comparison())
		{
			$PAGE .= '<div style="display:flex; align-items: center; justify-content: center;">' . pts_result_file_output::result_file_to_detailed_html_table($result_file, 'grid', $extra_attributes) . '</div>';
		}
		else
		{
			$intent = null;
			$table = new pts_ResultFileTable($result_file, $intent);
			$PAGE .= '<p style="text-align: center; overflow: auto;" class="result_object">' . pts_render::render_graph_inline_embed($table, $result_file, $extra_attributes) . '</p>';
		}
		$PAGE .= '<div id="results">';
		$prev_title = null;
		foreach($result_file->get_result_objects() as $i => $result_object)
		{
			$res = pts_render::render_graph_inline_embed($result_object, $result_file, $extra_attributes);
			if($res == false)
			{
				continue;
			}
			$PAGE .= '<a name="r-' . $i . '"></a><div style="text-align: center;" id="result-' . $i . '">';
			if($result_object->test_profile->get_title() != $prev_title)
			{
				$PAGE .= '<h2>' . $result_object->test_profile->get_title() . '</h2>';
				$prev_title = $result_object->test_profile->get_title();
			}
			$PAGE .= $res . '<br />';
			if(VIEWER_CAN_DELETE_RESULTS && RESULTS_VIEWING_COUNT == 1)
			{
				$PAGE .= '<a class="mini" href="#" onclick="javascript:delete_result_from_result_file(\'' . RESULTS_VIEWING_ID . '\', \'' . $i . '\'); return false;">(Delete Result)</a>';
			}
			if(VIEWER_CAN_MODIFY_RESULTS && RESULTS_VIEWING_COUNT == 1)
			{
				if($result_object->get_annotation() == null)
				{
					$PAGE .= ' <a class="mini" href="#" onclick="javascript:display_add_annotation_for_result_object(\'' . RESULTS_VIEWING_ID . '\', \'' . $i . '\', this); return false;">(Add Annotation)</a>';
					$PAGE .= ' <div id="annotation_area_' . $i . '" style="display: none;"> <form action="#" onsubmit="javascript:add_annotation_for_result_object(\'' . RESULTS_VIEWING_ID . '\', \'' . $i . '\', this); return false;"><textarea rows="4" cols="50" placeholder="Add Annotation..." name="annotation"></textarea><br /><input type="submit" value="Add Annotation"></form></div>';
				}
				else
				{
					$PAGE .= '<br /><div id="update_annotation_' . $i . '" contentEditable="true">' . $result_object->get_annotation() . '</div> <input type="submit" value="Update Annotation" onclick="javascript:update_annotation_for_result_object(\'' . RESULTS_VIEWING_ID . '\', \'' . $i . '\'); return false;">';
				}
			}
			else
			{
				$PAGE .= $result_object->get_annotation();
			}
			$PAGE .= '</div>';
			unset($result_object);
		}
		$PAGE .= '</div>';
		break;
	case 'index':
	default:
		define('TITLE', 'Phoronix Test Suite ' . PTS_VERSION . ' Result Viewer');
		$PAGE .= '<form name="search_results" id="search_results" action="' . CURRENT_URI . '" method="post"><input type="text" name="search" id="u_search" placeholder="Search Test Results" value="' . (isset($_POST['search']) ? $_POST['search'] : null) . '" /> <select name="sort_results_by"><option value="date">Date</option><option value="test_count">Test Count</option><option value="system_count">System Count</option></select> <input class="primary-button" type="submit" value="Update" />
</form>';
		$leading_msg = null;
		if(VIEWER_CAN_DELETE_RESULTS && isset($_GET['remove_result']) && $_GET['remove_result'] && pts_results::is_saved_result_file($_GET['remove_result']))
		{
			$deleted = pts_results::remove_saved_result_file($_GET['remove_result']);
			if($deleted)
			{
				$leading_msg = 'Deleted the <em>' . $_GET['remove_result'] . '</em> result file.';
			}
		}
		function sort_by_date($a, $b)
		{
			$a = strtotime($a->get_last_modified());
			$b = strtotime($b->get_last_modified());
			if($a == $b)
				return 0;
			return $a > $b ? -1 : 1;
		}
		function sort_by_test_count($a, $b)
		{
			$a = $a->get_test_count();
			$b = $b->get_test_count();
			if($a == $b)
				return 0;
			return $a > $b ? -1 : 1;
		}
		function sort_by_system_count($a, $b)
		{
			$a = $a->get_system_count();
			$b = $b->get_system_count();
			if($a == $b)
				return 0;
			return $a > $b ? -1 : 1;
		}
		$results = array();
		$all_results = pts_results::saved_test_results();
		foreach($all_results as $id)
		{
			$rf = new pts_result_file($id);

			if(isset($_POST['search']) && !empty($_POST['search']))
			{
				if(pts_search::search_in_result_file($rf, $_POST['search']) == false)
				{
					continue;
				}
			}

			$results[$id] = $rf;
		}
		switch((isset($_REQUEST['sort_results_by']) ? $_REQUEST['sort_results_by'] : 'date'))
		{
			case 'test_count':
				uasort($results, 'sort_by_test_count');
				break;
			case 'system_count':
				uasort($results, 'sort_by_system_count');
				break;
			case 'date':
			default:
				uasort($results, 'sort_by_date');
				break;
		}

		$total_result_points = 0;
		foreach($results as $id => $result_file)
		{
			$total_result_points += $result_file->get_test_count();
		}

		$PAGE .= '<div class="sub" style="margin-bottom: 30px">' . (count($all_results) != count($results) ? count($results) . ' of ' : null) . count($all_results) . ' Result Files Containing A Combined ' . $total_result_points . ' Test Results</div>';
		$PAGE .= '<form name="compare_results" id="compare_results_id" action="' . CURRENT_URI . '" method="post"><input type="submit" value="Compare Results" id="compare_results_submit" />';
		$i = 0;
		foreach($results as $id => $result_file)
		{
			$i++;
			$PAGE .= '<h2><a href="' . WEB_URL_PATH . 'result/' . $id . '">' . $result_file->get_title() . '</a></h2>';
			$PAGE .= '<div class="sub"><input type="checkbox" name="checkbox_compare_results[]" value="' . $id . '" id="cr_checkbox_' . $i . '" /> <label for="cr_checkbox_' . $i . '"><span onclick="javascript:document.getElementById(\'compare_results_id\').submit(); return false;">Compare Results</span></label> ' . $result_file->get_test_count() . ' Tests &nbsp; &nbsp; ' . $result_file->get_system_count() . ' Systems &nbsp; &nbsp; ' . date('l j F H:i', strtotime($result_file->get_last_modified())) . ' ' . (VIEWER_CAN_DELETE_RESULTS ? ' &nbsp; &nbsp; <span onclick="javascript:delete_result_file(\'' . $id . '\'); return false;">DELETE RESULT FILE</span>' : null) . '</div>';
			$PAGE .= '<div class="desc">' . $result_file->get_description() . '</div>';

			$geometric_mean = pts_result_file_analyzer::generate_geometric_mean_result($result_file);
			if($geometric_mean)
			{
				$geo_display = null;
				$geo_display_count = 0;
				$best_result = $geometric_mean->test_result_buffer->get_max_value(false);
				foreach($geometric_mean->test_result_buffer as &$buffers)
				{
					if(empty($buffers))
						continue;

					$max_value = 0;
					foreach($buffers as &$buffer_item)
					{
						$v = $buffer_item->get_result_value();
						if(!is_numeric($v)) continue;
						$percentage = ($v / $best_result) * 100;
						$geo_display .=  '<div class="geo_bg_graph" style="margin-right: ' . round(100 - $percentage, 1) . '%"><strong>' . $buffer_item->get_result_identifier() . ':</strong> ' . $v . ' (' . round($percentage, 2) . '%)</div>';
						$geo_display_count++;
					}
				}
				if($geo_display_count > 1)
				{
					$PAGE .= '<span class="sub_header">Geometric Mean</span>' . $geo_display;
				}
			}
			$PAGE .= '<br />';
		}
		$PAGE .= '</form>';
		break;

}

define('PAGE', $PAGE);

?>
<!doctype html>
<html lang="en">
<head>
  <title><?php echo defined('TITLE') ? TITLE : ''; ?></title>
<link rel="stylesheet" href="/result-viewer.css">
<script type="text/javascript" src="/result-viewer.js"></script>
<link href="//fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
</head>
<body>
<div id="header">
<div style="float: left; margin-top: 2px;">
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewbox="0 0 76 41" width="76" height="41" preserveAspectRatio="xMinYMin meet">
  <path d="m74 22v9m-5-16v16m-5-28v28m-23-2h12.5c2.485281 0 4.5-2.014719 4.5-4.5s-2.014719-4.5-4.5-4.5h-8c-2.485281 0-4.5-2.014719-4.5-4.5s2.014719-4.5 4.5-4.5h12.5m-21 5h-11m11 13h-2c-4.970563 0-9-4.029437-9-9v-20m-24 40v-20c0-4.970563 4.0294373-9 9-9 4.970563 0 9 4.029437 9 9s-4.029437 9-9 9h-9" stroke="#fff" stroke-width="4" fill="none" />
</svg></div> <div style="float: left; margin-left: 10px;"> <a href="<?php echo WEB_URL_PATH; ?>">Result Viewer</a></div>
<ul>
<li><a href="<?php echo WEB_URL_PATH; ?>">Results</a></li>
</ul>
</div>
<?php if(isset($leading_msg) && $leading_msg) { echo '<div id="leading_message">' . $leading_msg . '</div>'; } ?>
<div id="main_area">
<?php echo PAGE; ?>
</div>
<div id="footer"><hr /><br /><a href="https://www.phoronix-test-suite.com/">Phoronix Test Suite</a> <?php echo PTS_VERSION; ?> - Generated <?php echo date('j F Y H:i:s'); ?> - Developed by Phoronix Media</div>
</body>
<?php }
if(function_exists('session_start'))
{
	session_write_close();
}
?>
</html>
