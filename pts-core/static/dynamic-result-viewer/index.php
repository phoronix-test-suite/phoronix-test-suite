<?php
error_reporting(E_ALL);
session_start();

define('CURRENT_URI', $_SERVER['REQUEST_URI']);

if(!is_file('result_viewer_config.php'))
{
	echo '<p>You must configure result_viewer_config.php!</p>';
	exit;
}
require('result_viewer_config.php');

define('PHOROMATIC_EXPORT_VIEWER', true);
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
		if(!isset($_GET['result']) || !is_file(VIEWER_RESULTS_DIRECTORY_PATH . '/' . $_GET['result'] . '/composite.xml'))
		{
			$PAGE = 'Could not find result file!';
		}
		else
		{
			$extra_attributes = null;
			$result_file = new pts_result_file(VIEWER_RESULTS_DIRECTORY_PATH . '/' . $_GET['result'] . '/composite.xml');
			define('TITLE', $result_file->get_title());
			$PAGE .= '<h1>' . $result_file->get_title() . '</h1>';
			$PAGE .= '<p>' . $result_file->get_description() . '</p>';
			$PAGE .= '<p align="center"><strong>Export As: </strong> <a href="' . CURRENT_URI . '&export=pdf">PDF</a>, <a href="' . CURRENT_URI . '&export=csv">CSV</a>, <a href="' . CURRENT_URI . '&export=csv-all">CSV Individual Data</a> </p>';
			switch(isset($_GET['export']) ? $_GET['export'] : null)
			{
				case 'pdf':
					header('Content-Type: application/pdf');
					$pdf_output = pts_result_file_output::result_file_to_pdf($result_file, $_GET['result'] . '.pdf', 'D', $extra_attributes);
					exit;
				case 'csv':
					$result_csv = pts_result_file_output::result_file_to_csv($result_file);
					header('Content-Description: File Transfer');
					header('Content-Type: application/csv');
					header('Content-Disposition: attachment; filename=' . $_GET['result']. '.csv');
					header('Expires: 0');
					header('Cache-Control: must-revalidate');
					header('Pragma: public');
					header('Content-Length: ' . strlen($result_csv));
					echo $result_csv;
					exit;
				case 'csv-all':
					$result_csv = pts_result_file_output::result_file_raw_to_csv($result_file);
					header('Content-Description: File Transfer');
					header('Content-Type: application/csv');
					header('Content-Disposition: attachment; filename=' . $_GET['result']. '.csv');
					header('Expires: 0');
					header('Cache-Control: must-revalidate');
					header('Pragma: public');
					header('Content-Length: ' . strlen($result_csv));
					echo $result_csv;
					exit;
			}
			$table = new pts_ResultFileSystemsTable($result_file);
			$PAGE .= '<p style="text-align: center; overflow: auto;" class="result_object">' . pts_render::render_graph_inline_embed($table, $result_file, $extra_attributes) . '</p>';
			$intent = null;
			$PAGE .= '<div style="display:flex; align-items: center; justify-content: center;">' . pts_result_file_output::result_file_to_detailed_html_table($result_file, 'grid') . '</div>';
			$table = new pts_ResultFileTable($result_file, $intent);
			$PAGE .= '<p style="text-align: center; overflow: auto;" class="result_object">' . pts_render::render_graph_inline_embed($table, $result_file, $extra_attributes) . '</p>';

			foreach($result_file->get_result_objects() as $i => &$result_object)
			{
				$res = pts_render::render_graph_inline_embed($result_object, $result_file, $extra_attributes);

				if($res == false)
				{
					continue;
				}

				$PAGE .= '<p align="center">';
				$PAGE .= $res;
				$PAGE .= '</p>';
				unset($result_object);
			}
		}
		break;
	case 'index':
	default:
		define('TITLE', 'Result Viewer');
		$PAGE .= '<form name="search_results" id="search_results" action="' . CURRENT_URI . '" method="post"><input type="text" name="search" id="u_search" required placeholder="Search Results" value="' . (isset($_POST['search']) ? $_POST['search'] : null) . '" /> <input type="submit" value="Search" />
</form>';
		function sort_by_date($a, $b)
		{
			$a = strtotime($a->get_last_modified());
			$b = strtotime($b->get_last_modified());
			if($a == $b)
				return 0;
			return $a > $b ? -1 : 1;
		}
		$results = array();
		foreach(pts_file_io::glob(VIEWER_RESULTS_DIRECTORY_PATH . '/*/composite.xml') as $composite_xml)
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
		uasort($results, 'sort_by_date');
		foreach($results as $id => $result_file)
		{
			$PAGE .= '<h2><a href="?page=result&result=' . $id . '">' . $result_file->get_title() . '</a></h2>';
			$PAGE .= '<div class="sub">' . $result_file->get_test_count() . ' Tests &nbsp; &nbsp; ' . $result_file->get_system_count() . ' Systems &nbsp; &nbsp; ' . date('j F H:i', strtotime($result_file->get_last_modified())) . '</div>';
		}
		break;

}

define('PAGE', $PAGE);

?>
<!doctype html>
<html lang="en">
<head>
  <title><?php echo TITLE; ?></title>
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
	padding: 10px;
	color: #fff;
	overflow: hidden;
	font-size: 14pt;
	font-weight: 600;
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
div#header ul li a
{
	font-weight: 400;
	color: #FFF;
	text-decoration: none;
}
div#header ul li a:hover
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
div#main_area input, div#main_area textarea
{
	margin: 10px 0;
	background: #ddd;
	color: #000;
	font-size: 15pt;
	border: 1px solid #eee;
	padding: 5px 10px;
	font-weight: 600;
}
div#main_area input::placeholder, div#main_area textarea::placeholder
{
	color: #000;
	opacity: 0.7;
	font-weight: 400;
}
div#main_area div.sub
{
	margin: 2px 0 8px;
	padding: 0;
	font-size: 12pt;
	text-transform: uppercase;
}
hr
{
	color: #098BEF;
	opacity: 0.3;
	margin: 0 10%;
}
div#footer
{
	font-size: 9pt;
	color: #aaa;
	text-align: center;
	padding: 0 50px;
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
svg
{
	min-width: 50%;
	height: auto;
}
</style>
</head>
<body>
<div id="header">
Result Viewer
<ul>
<li><a href="?page=index">Results</a></li>
</ul>
</div>

<div id="main_area">
<?php echo PAGE; ?>
</div>
<div id="footer"><hr /><br />Phoronix Test Suite - Generated <?php echo date('j F Y H:i:s'); ?> - Developed by Phoronix Media</div>
</body>
<?php }
session_write_close();
?>
</html>

