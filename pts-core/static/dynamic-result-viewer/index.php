<?php
/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2019 - 2021, Phoronix Media
	Copyright (C) 2019 - 2021, Michael Larabel

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
define('WEB_URL_PATH', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/');
define('RESULT_VIEWER_VERSION', 3);
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
	foreach($_REQUEST['checkbox_compare_results'] as &$inp)
	{
		// Remove any possible garbage since the result identifiers should just have alpha num and dashes anyhow...
		$inp = preg_replace('/[^\w-]/', '', $inp);
	}

	echo '<script> window.location.href = "http://' . $_SERVER['HTTP_HOST'] . WEB_URL_PATH . 'result/' . implode(',', $_REQUEST['checkbox_compare_results']) . '"; </script>';
	exit;
}
$uri_segments = explode('/', trim((WEB_URL_PATH == '/' ? $uri_stripped : str_replace(WEB_URL_PATH, '', $uri_stripped)), '/'));
switch((isset($uri_segments[0]) ? $uri_segments[0] : null))
{
	case 'result':
		$_GET['page'] = 'result';
		$_GET['result'] = $uri_segments[1];
		break;
	case 'test':
		$_GET['page'] = 'test';
		$_GET['test'] = base64_decode($uri_segments[1]);
		break;
	case 'suite':
		$_GET['page'] = 'suite';
		$_GET['suite'] = base64_decode($uri_segments[1]);
		break;
	case 'tests':
	case 'suites':
		$_GET['page'] = $uri_segments[0];
		break;
}

if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && isset($_ENV['TEMP']) && is_file($_ENV['TEMP'] . '\pts-env-web'))
{
	foreach(explode(PHP_EOL,  file_get_contents($_ENV['TEMP'] . '\pts-env-web')) as $line)
	{
		if(!empty($line))
		{
			putenv($line);
		}
	}
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

set_time_limit(60);
ini_set('memory_limit','2048M');

if(isset($_GET['PTS']))
{
	// Test for client to see if resolving properly
	echo 'PTS';
	exit;
}

define('CSS_RESULT_VIEWER_PATH', str_replace('//', '/', WEB_URL_PATH . '/result-viewer.css?' . PTS_CORE_VERSION));

if(!defined('PTS_CORE_STORAGE') && getenv('PTS_CORE_STORAGE') && is_file(getenv('PTS_CORE_STORAGE')))
{
	define('PTS_CORE_STORAGE',  getenv('PTS_CORE_STORAGE'));
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
	unset($_POST['access_key']);
}

if(VIEWER_ACCESS_KEY != null && (!isset($_SESSION['AccessKey']) || $_SESSION['AccessKey'] != VIEWER_ACCESS_KEY)) { ?>
<!doctype html>
<html lang="en">
<head>
  <title>Phoronix Test Suite - Result Portal</title>
<link rel="stylesheet" href="<?php echo CSS_RESULT_VIEWER_PATH; ?>">
<script type="text/javascript" src="<?php echo WEB_URL_PATH; ?>/result-viewer.js?<?php echo PTS_CORE_VERSION; ?>"></script>
<link href="//fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
<link rel="icon" type="image/png" href="<?php echo WEB_URL_PATH; ?>favicon.png">
</head>
<body>
<div id="login_box">
<div id="login_box_left">
<h1>Phoronix Test Suite</h1>
<h2>Result Portal</h2>
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
$call_get_result_html = false;
switch(isset($_GET['page']) ? $_GET['page'] : null)
{
	case 'test':
		$o = new pts_test_profile($_GET['test']);
		$PAGE = pts_web_embed::test_profile_overview($o);

		$o_identifier = $o->get_identifier(false);
		$table = array();
		$i = 0;
		$found_result = false;
		foreach(pts_results::saved_test_results() as $id)
		{
			$result_file = new pts_result_file($id);
			foreach($result_file->get_result_objects() as $result_object)
			{
				if($result_object->test_profile->get_identifier(false) == $o_identifier)
				{
					if(!$found_result)
					{
						$found_result = true;
						$PAGE .= '<br /><br /><h2>Results Containing This Test</h2><br />';
					}
					$PAGE .= '<h2><a href="' . WEB_URL_PATH . 'result/' . $id . '">' . $result_file->get_title() . '</a></h2>';
					$PAGE .= '<div class="sub"><label for="cr_checkbox_' . $i . '"></label> ' . $result_file->get_test_count() . ' Tests &nbsp; &nbsp; ' . $result_file->get_system_count() . ' Systems &nbsp; &nbsp; ' . date('l j F H:i', strtotime($result_file->get_last_modified())) . ' </div>';
					$PAGE .= '<div class="desc">' . $result_file->get_description() . '</div>';
					break;
					$i++;
				}
			}
		}
		break;
	case 'suite':
		$o = new pts_test_suite($_GET['suite']);
		$PAGE = pts_web_embed::test_suite_overview($o);
		break;
	case 'tests':
		$PAGE .= pts_web_embed::tests_list();
		break;
	case 'suites':
		$PAGE .= pts_web_embed::test_suites_list();
		break;
	case 'result':
		if(isset($_POST) && !empty($_POST) && !isset($_POST['log_select']) && !isset($_REQUEST['modify']))
		{
			$result_link = null;
			foreach(array_keys($_POST) as $key)
			{
				if($_REQUEST[$key] != null && $_REQUEST[$key] != '0' && $key != 'submit')
				{
					if(is_array($_REQUEST[$key]))
					{
						$_REQUEST[$key] = implode(',', $_REQUEST[$key]);
					}
					$result_link .= '&' . $key . '=' . urlencode(str_replace('.', '_DD_', $_REQUEST[$key]));
				}
			}
			$server_uri = $_SERVER['REQUEST_URI'];
			if(($x = strpos($server_uri, '&')) !== false)
			{
				$server_uri = substr($server_uri, 0, $x);
			}

			header('Location: ' . $server_uri . $result_link);
		}
		$result_file = null;
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
					/*if(count($possible_results) > 1)
					{
						$result_file->rename_run('PREFIX', $result_file->get_title());
					}*/
				}
				else
				{
					$rf = new pts_result_file($rid);
					$result_file->merge(array($rf), 0, $rf->get_title(), true, true);
				}
			}
		}
		if($result_file == null)
		{
			break;
		}
		define('TITLE', $result_file->get_title() . ' - Phoronix Test Suite');
		$embed = new pts_result_viewer_embed($result_file, $results_viewing[0]);
		$embed->allow_modifying_results(VIEWER_CAN_MODIFY_RESULTS && count($results_viewing) == 1);
		$embed->allow_deleting_results(VIEWER_CAN_DELETE_RESULTS && count($results_viewing) == 1);
		if(!isset($_REQUEST['export']))
		{
			$call_get_result_html = true;
		}
		else
		{
			$PAGE = $embed->get_html();
		}
		break;
	case 'index':
	default:
		if(isset($uri_segments[0]) && is_file($uri_segments[0] . '.html'))
		{
			define('TITLE', 'Phoronix Test Suite ' . PTS_VERSION);
			$PAGE = file_get_contents($uri_segments[0] . '.html');
		}
		else
		{
			define('TITLE', 'Phoronix Test Suite ' . PTS_VERSION . ' Result Portal');
			$search_query = isset($_POST['search']) ? pts_strings::simple($_POST['search']) : null;
			$PAGE .= '<form name="search_results" id="search_results" action="' . CURRENT_URI . '" method="post"><input type="text" name="search" id="u_search" placeholder="Search Test Results" value="' . $search_query . '" /> <select name="sort_results_by"><option value="date">Sort By Date</option><option value="title">Sort By Title</option><option value="test_count">Sort By Test Count</option><option value="system_count">Sort By System Count</option></select> <input class="primary-button" type="submit" value="Update" />
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

			$results = pts_results::query_saved_result_files($search_query, (isset($_REQUEST['sort_results_by']) ? $_REQUEST['sort_results_by'] : null));

			$total_result_points = 0;
			foreach($results as $id => $result_file)
			{
				$total_result_points += $result_file->get_test_count();
			}

			$PAGE .= '<div class="sub" style="margin: 6px 0 30px">' . ($result_file_count = count($results)) . ' Result Files Containing A Combined ' . $total_result_points . ' Test Results</div>';
			$PAGE .= '<form name="compare_results" id="compare_results_id" action="' . CURRENT_URI . '" method="post"><input type="submit" value="Compare Results" id="compare_results_submit" />';
			$i = 0;
			foreach($results as $id => $result_file)
			{
				$i++;
				$PAGE .= '<h2><a href="' . WEB_URL_PATH . 'result/' . $id . '">' . $result_file->get_title() . '</a></h2>';
				$PAGE .= '<div class="sub"><input type="checkbox" name="checkbox_compare_results[]" value="' . $id . '" id="cr_checkbox_' . $i . '" /> <label for="cr_checkbox_' . $i . '"><span onclick="javascript:document.getElementById(\'compare_results_id\').submit(); return false;">Compare Results</span></label> ' . $result_file->get_test_count() . ' Tests &nbsp; &nbsp; ' . $result_file->get_system_count() . ' Systems &nbsp; &nbsp; ' . date('l j F H:i', strtotime($result_file->get_last_modified())) . ' ' . (VIEWER_CAN_DELETE_RESULTS ? ' &nbsp; &nbsp; <span onclick="javascript:delete_result_file(\'' . $id . '\'); return false;">DELETE RESULT FILE</span>' : null) . '</div>';
				$PAGE .= '<div class="desc">' . $result_file->get_description() . '</div>';

				// Avoid showing geo mean for every result file due to too computationally intensive
				$geometric_mean = $result_file_count > 40 ? false : pts_result_file_analyzer::generate_geometric_mean_result($result_file);
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
							$bg = pts_render::identifier_to_brand_color($buffer_item->get_result_identifier(), '');
							if($bg)
							{
								$bg = 'background: ' . $bg . '; color: #FFF';
							}
							$geo_display .=  '<div class="geo_bg_graph" style="margin-right: ' . round(100 - $percentage, 1) . '%; ' . $bg . '"><strong>' . $buffer_item->get_result_identifier() . ':</strong> ' . $v . ' (' . round($percentage, 2) . '%)</div>';
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
		}
		break;
}

?>
<!doctype html>
<html lang="en">
<head>
  <title><?php echo defined('TITLE') ? TITLE : ''; ?></title>
<link rel="stylesheet" href="<?php echo CSS_RESULT_VIEWER_PATH; ?>">
<link rel="icon" type="image/png" href="<?php echo WEB_URL_PATH; ?>favicon.png">
<script type="text/javascript" src="<?php echo WEB_URL_PATH; ?>result-viewer.js?<?php echo PTS_CORE_VERSION; ?>"></script>
<script>
var WEB_URL_PATH = "<?php echo WEB_URL_PATH; ?>";
</script>
<link href="//fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
<?php
if(defined('PTS_RESULT_VIEWER_WATERMARK') && PTS_RESULT_VIEWER_WATERMARK != '') { ?>
<style>
body:before{
	content: "<?php echo PTS_RESULT_VIEWER_WATERMARK; ?>";
	position: fixed;
	top: 0;
	bottom: 0;
	left: 0;
	right: 0;
	z-index: -1;
	color: #3e3e3e;
	font-size: 89px;
	font-weight: 500;
	display: grid;
	justify-content: center;
	align-content: center;
	opacity: 0.1;
	transform: rotate(-35deg);
}
</style>
<?php } ?>
</head>
<body>
<div id="header">
<div style="float: left; margin-top: 2px;">
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewbox="0 0 76 41" width="76" height="41" preserveAspectRatio="xMinYMin meet">
  <path d="m74 22v9m-5-16v16m-5-28v28m-23-2h12.5c2.485281 0 4.5-2.014719 4.5-4.5s-2.014719-4.5-4.5-4.5h-8c-2.485281 0-4.5-2.014719-4.5-4.5s2.014719-4.5 4.5-4.5h12.5m-21 5h-11m11 13h-2c-4.970563 0-9-4.029437-9-9v-20m-24 40v-20c0-4.970563 4.0294373-9 9-9 4.970563 0 9 4.029437 9 9s-4.029437 9-9 9h-9" stroke="#696969" stroke-width="4" fill="none" />
</svg></div> <div style="float: left; margin: 5px 0 0 10px;"> <a href="<?php echo WEB_URL_PATH; ?>">Result Viewer</a></div>
<ul>
<?php if(defined('PTS_OPENBENCHMARKING_SCRATCH_PATH') && PTS_OPENBENCHMARKING_SCRATCH_PATH != null) { ?>
<li><a href="<?php echo WEB_URL_PATH; ?>tests/">Test Profiles</a></li>
<li><a href="<?php echo WEB_URL_PATH; ?>suites/">Test Suites</a></li>
<?php } ?>
<li><a href="<?php echo WEB_URL_PATH; ?>">Results</a></li>
</ul>
</div>
<?php
if((!isset($leading_msg) || empty($leading_msg)) && defined('PTS_CORE_STORAGE') && ($motd = pts_storage_object::read_from_file(PTS_CORE_STORAGE, 'MOTD_HTML')) != null)
{
	$leading_msg = '<em>' . $motd . '</em>';
}
if(isset($leading_msg) && $leading_msg) { echo '<div id="leading_message">' . $leading_msg . '</div>'; } ?>
<div id="main_area">
<?php echo $call_get_result_html ? $embed->get_html() : $PAGE; ?>
</div>
<div id="footer"><hr /><br /><a href="https://www.phoronix-test-suite.com/">Phoronix Test Suite</a> <?php echo PTS_VERSION; ?> - Generated <?php echo date('j F Y H:i:s'); ?></div>
</body>
<?php }
if(function_exists('session_start'))
{
	session_write_close();
}
?>
</html>
