<?php
/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2019, Phoronix Media
	Copyright (C) 2019, Michael Larabel

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
session_start();

define('CURRENT_URI', $_SERVER['REQUEST_URI']);
define('WEB_URL_PATH', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/');
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
	define('VIEWER_RESULTS_DIRECTORY_PATH', getenv('PTS_VIEWER_RESULT_PATH'));
	define('VIEWER_PHORONIX_TEST_SUITE_PATH', getenv('PTS_VIEWER_PTS_PATH'));
}
else
{
	if(!is_file('result_viewer_config.php'))
	{
		echo '<p>You must configure result_viewer_config.php!</p>';
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

// Authenticate user and set session variables
if(isset($_POST['access_key']))
{
	$_SESSION['AccessKey'] = trim(hash('sha256', trim($_POST['access_key'])));
}

if(VIEWER_ACCESS_KEY != null && (!isset($_SESSION['AccessKey']) || $_SESSION['AccessKey'] != VIEWER_ACCESS_KEY)) { ?>
<!doctype html>
<html lang="en">
<head>
  <title>Phoronix Test Suite - Local Result Viewer</title>
<link href="//fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
<style>
body
{
	margin: 0;
	padding: 0;
	font-family: 'Roboto', sans-serif;


}
hr
{
	color: #098BEF;
	opacity: 0.3;
	margin: 0 10%;
}
div#login_box
{
	margin-top: 20%;
	background-image: linear-gradient(#098BEF, #0367B4);
	border: 1px solid #eee;
	border-width: 1px 0 1px 0;
	padding: 30px 0;
	color: #fff;
	overflow: hidden;
}
div#login_box input
{
	margin: 10px 0;
	background: #098BEF;
	color: #fff;
	font-size: 15pt;
	border: 1px solid #eee;
	padding: 5px 10px;
}
div#login_box input::placeholder
{
	color: #fff;
}
div#login_box h1
{
	font-weight: 500;
	text-transform: uppercase;
}
div#login_box h2
{
	font-weight: 400;
	text-transform: uppercase;
}
div#login_box_left
{
	float: left;
	width: 50%;
	padding: 12px 30px 0 0;
	text-align: right;
	border: 1px solid #eee;
	border-width: 0 1px 0 0;
	min-height: 250px;
}
div#login_box_right
{
	border-width: 0 0 0 1px;
	float: left;
	padding-left: 30px;
	text-align: left;
}
</style>
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

		foreach($possible_results as $rid)
		{
			if(is_file(VIEWER_RESULTS_DIRECTORY_PATH . '/' . $rid . '/composite.xml'))
			{
				if($result_file == null)
				{
					$result_file = new pts_result_file(VIEWER_RESULTS_DIRECTORY_PATH . '/' . $rid . '/composite.xml');
					if(count($possible_results) > 1)
					{
						$result_file->rename_run('PREFIX', $result_file->get_title());
					}
				}
				else
				{
					$rf = new pts_result_file(VIEWER_RESULTS_DIRECTORY_PATH . '/' . $rid . '/composite.xml');
					$result_file->merge(array(new pts_result_merge_select($rf)), 0, $rf->get_title(), true);
					$result_merges++;
				}
			}
		}
		if($result_file == null)
		{
			break;
		}
		if($result_merges > 0)
		{
			$result_file->avoid_duplicate_identifiers();
		}

		$extra_attributes = null;
		pts_result_viewer_settings::process_request_to_attributes($_REQUEST, $result_file, $extra_attributes);
		define('TITLE', $result_file->get_title());
		$PAGE .= pts_result_viewer_settings::get_html_sort_bar($result_file, $_REQUEST);
		$PAGE .= '<h1>' . $result_file->get_title() . '</h1>';
		$PAGE .= '<p>' . str_replace(PHP_EOL, '<br />', $result_file->get_description()) . '</p>';
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
		foreach($result_file->get_result_objects() as $i => &$result_object)
		{
			$res = pts_render::render_graph_inline_embed($result_object, $result_file, $extra_attributes);
			if($res == false)
			{
				continue;
			}
			$PAGE .= '<a name="r-' . $i . '"></a><p align="center">';
			$PAGE .= $res;
			$PAGE .= '</p>';
			unset($result_object);
		}
		$PAGE .= '</div>';
		break;
	case 'index':
	default:
		define('TITLE', 'Phoronix Test Suite ' . PTS_VERSION . ' Result Viewer');
		$PAGE .= '<form name="search_results" id="search_results" action="' . CURRENT_URI . '" method="post"><input type="text" name="search" id="u_search" placeholder="Search Test Results" value="' . (isset($_POST['search']) ? $_POST['search'] : null) . '" /> <select name="sort_results_by"><option value="date">Date</option><option value="test_count">Test Count</option><option value="system_count">System Count</option></select> <input class="primary-button" type="submit" value="Update" />
</form>';
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
		$all_results = pts_file_io::glob(VIEWER_RESULTS_DIRECTORY_PATH . '/*/composite.xml');
		foreach($all_results as $composite_xml)
		{
			$id = basename(dirname($composite_xml));
			$rf = new pts_result_file($composite_xml);

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
			$PAGE .= '<div class="sub"><input type="checkbox" name="checkbox_compare_results[]" value="' . $id . '" id="cr_checkbox_' . $i . '" /> <label for="cr_checkbox_' . $i . '"><span onclick="javascript:document.getElementById(\'compare_results_id\').submit(); return false;">Compare Results</span></label> ' . $result_file->get_test_count() . ' Tests &nbsp; &nbsp; ' . $result_file->get_system_count() . ' Systems &nbsp; &nbsp; ' . date('l j F H:i', strtotime($result_file->get_last_modified())) . '</div>';
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
<link href="//fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
<style>
body
{
	margin: 0;
	padding: 0;
	font-family: 'Roboto', sans-serif;


}
div#header
{
	background-image: linear-gradient(#098BEF, #0367B4);
	border: 1px solid #eee;
	border-width: 0 0 1px 0;
	padding: 2px 10px 0;
	color: #fff;
	overflow: hidden;
	font-size: 23pt;
	font-weight: 500;
}
div#header ul
{
	float: right;
	list-style-type: none;
	margin: 0;
	padding: 0;
}
div#header ul li
{
	padding: 0 30px;
	float: left;
}
div#header ul li a, div#header a
{
	font-weight: 400;
	color: #FFF;
	text-decoration: none;
}
div#header ul li a:hover, div#header a:hover
{
	color: #eee;
}
div#main_area
{
	font-size: 15pt;
	color: #222;
	padding: 50px;
}
div#main_area a
{
	color: #0367B4;
	text-decoration: none;
}
div#main_area a:hover
{
	color: #4BABF4;
}
div#main_area h1
{
	color: #098BEF;
	font-weight: 500;
	text-transform: uppercase;
}
div#main_area h3
{
	color: #098BEF;
	font-weight: 500;
	font-size: 90%;
}
div#main_area h2
{
	color: #098BEF;
	font-weight: 500;
	padding: 0;
	margin: 2px 0;
}
div#main_area input, div#main_area textarea, div#main_area select
{
	margin: 10px 0;
	background: #ddd;
	color: #000;
	font-size: 15pt;
	border: 1px solid #eee;
	padding: 5px 10px;
	font-weight: 600;
}
div#main_area input::placeholder, div#main_area textarea::placeholder, div#main_area select::placeholder
{
	color: #000;
	opacity: 0.7;
	font-weight: 400;
}
div#main_area ul
{
	list-style: none;
	position: relative;
	float: left;
	margin: 0;
	padding: 0;
}
div#main_area ul:hover a
{
	color: #000;
}
div#main_area ul a
{
	display: block;
	color: #333;
	text-decoration: none;
	font-weight: 600;
	line-height: 26px;
	padding: 4px 8px;
	text-transform: uppercase;
}
div#main_area ul li
{
	position: relative;
	float: left;
	margin: 0;
	padding: 0;
	border-right: 1px solid #aaa;
}
div#main_area ul li:last-of-type{
	border-right:none;
}
div#main_area ul li:hover
{
	background: #eee;

}
div#main_area ul ul a:hover, div#main_area ul li a:hover{
	color:#03b450;
}

div#main_area ul ul a
{
	padding: 0 8px;
	font-weight: 300;
	font-size: 10pt;
}
div#main_area ul ul
{
	display: none;
	position: absolute;
	top: 100%;
	font-weight: 300;
	left: 0;
	background: #fff;
	padding: 0
}
div#main_area ul ul li
{
	border: 1px solid #eee;
	border-top: 1px;
	float: none;
}
div#main_area ul li:hover > ul
{
	display: block
}
div#main_area div.sub
{
	margin: -5px 0 8px;
	padding: 0;
	font-size: 10pt;
	text-transform: uppercase;
	color: #ef5e09;
}
div#main_area div.desc
{
	margin: 0;
	padding: 0;
	font-size: 10pt;
}
div#main_area span.sub_header
{
	text-transform: uppercase;
	font-size: 8pt;
	font-weight: bold;
}
hr
{
	color: #098BEF;
	opacity: 0.3;
	margin: 0 10%;
}
div#footer, div#footer a
{
	font-size: 9pt;
	color: #aaa;
	text-align: center;
	padding: 0;
	text-decoration: none;
}
.grid
{
	font-size: 10pt;
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(50px, 1fr));
	grid-template-rows: auto;
	border-left: 1px solid #ccc;
	margin: 20px auto;
}
.grid > span
{
	padding: 2px 4px;
	border-right: 1px solid #ccc;
	border-bottom: 1px solid #ccc;
}
.grid > span strong
{
	font-size: 12pt;
}
div#main_area svg
{
	min-width: 30%;
	height: auto;
}
div#results svg
{
	min-width: 50%;
	height: auto;
}
div.geo_bg_graph
{
	background: #CCC;
	font-size: 10pt;
	font-weight: 500;
	margin: 0;
	padding: 1px 4px;
	border: #BBB 2px solid;
	border-width: 1px;
}
input#compare_results_submit
{
	display: none;
}
input[type="checkbox"] + label
{
	display: none;
	background: #03b450;
	color: #FFF;
	padding: 4px;
	border: 1px solid #000;
	font-weight: 10pt;
}
input[type="submit"], .primay-button{
	background: #03b450 !important;
	color: #FFF !important;
	padding: 4px;
	border: 1px solid #000;
	font-weight: 10pt;
}
input[type="checkbox"]:checked + label
{
	font-weight: bold;
	display: inline;
}
</style>
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

<div id="main_area">
<?php echo PAGE; ?>
</div>
<div id="footer"><hr /><br /><a href="https://www.phoronix-test-suite.com/">Phoronix Test Suite</a> <?php echo PTS_VERSION; ?> - Generated <?php echo date('j F Y H:i:s'); ?> - Developed by Phoronix Media</div>
</body>
<?php }
session_write_close();
?>
</html>
