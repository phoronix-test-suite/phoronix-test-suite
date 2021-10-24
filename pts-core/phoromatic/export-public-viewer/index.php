<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2013 - 2019, Phoronix Media
	Copyright (C) 2013 - 2019, Michael Larabel

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

if(!is_file('phoromatic-export-viewer-config.php'))
{
	echo '<p>You must first configure the <em>phoromatic-export-viewer-config.php.config</em> file and rename it to <em>phoromatic-export-viewer-config.php</em> within this directory.</p>';
	return;
}
require('phoromatic-export-viewer-config.php');

if(!is_file(PATH_TO_PHORONIX_TEST_SUITE . 'pts-core/pts-core.php'))
{
	echo '<p>You must first set the <em>PATH_TO_PHORONIX_TEST_SUITE</em> define within the <em>phoromatic-export-viewer-config.php</em> file.</p>';
	return;
}

if(!is_file(PATH_TO_EXPORTED_PHOROMATIC_DATA . 'export-index.json'))
{
	echo '<p>You must first set the <em>PATH_TO_EXPORTED_PHOROMATIC_DATA</em> define within the <em>phoromatic-export-viewer-config.php</em> file. No <em>export-index.json</em> found.</p>';
	return;
}


define('PHOROMATIC_EXPORT_VIEWER', true);
define('PTS_MODE', 'LIB');
define('PTS_AUTO_LOAD_OBJECTS', true);
require(PATH_TO_PHORONIX_TEST_SUITE . 'pts-core/pts-core.php');
pts_define_directories();

//set_time_limit(0);
ini_set('memory_limit','2048M');
error_reporting(E_ALL);

$export_index_json = file_get_contents(PATH_TO_EXPORTED_PHOROMATIC_DATA . 'export-index.json');
$export_index_json = json_decode($export_index_json, true);

if(!isset($export_index_json['phoromatic']) || empty($export_index_json['phoromatic']))
{
	echo '<p>Error decoding the Phoromatic export JSON file.</p>';
	return;
}

if(strpos($_SERVER['REQUEST_URI'], '?') === false && isset($_SERVER['QUERY_STRING']))
{
	$_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
}
$URI = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], '?') + 1);
if(($uc = strpos($URI, '&')) !== false)
{
	$URI = substr($URI, 0, $uc);
}
$PATH = explode('/', $URI);
$REQUESTED = str_replace('.', '', array_shift($PATH));

if(empty($REQUESTED) || !isset($export_index_json['phoromatic'][$REQUESTED]))
{
	$keys = array_keys($export_index_json['phoromatic']);
	$REQUESTED = array_shift($keys);
	$title = PHOROMATIC_VIEWER_TITLE;
	$meta_desc = 'Phoronix Test Suite\'s open-source Phoromatic result viewer for automated performance benchmark results.';
}
else
{
	$title = $export_index_json['phoromatic'][$REQUESTED]['title'];
	$meta_desc = substr($export_index_json['phoromatic'][$REQUESTED]['description'], 0, (strpos($export_index_json['phoromatic'][$REQUESTED]['description'], '. ') + 1));
}

$tracker = &$export_index_json['phoromatic'][$REQUESTED];
$length = count($tracker['triggers']);

//
// EMAIL NOTIFICATIONS
//

if(defined('PATH_TO_PHOROMATIC_ML_DB') && PATH_TO_PHOROMATIC_ML_DB != null)
{
	function phoromatic_mailing_list_db_init()
	{
		$db_file = PATH_TO_PHOROMATIC_ML_DB;
		$db_flags = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE;
		$db = new SQLite3($db_file, $db_flags);
		$db->busyTimeout(10000);
		$result = $db->query('PRAGMA user_version');
		$result = $result->fetchArray();
		$user_version = isset($result['user_version']) && is_numeric($result['user_version']) ? $result['user_version'] : 0;

		switch($user_version)
		{
			case 0:
				// Account Database
				$db->exec('CREATE TABLE phoromatic_notifications_emails (EmailAddress TEXT, TestSchedule TEXT NOT NULL, NotifyOnNewResults INTEGER, NotifyOnRegressions INTEGER, UNIQUE(EmailAddress, TestSchedule) ON CONFLICT IGNORE)');
				$db->exec('PRAGMA user_version = 1');
				break;
		}
		chmod($db_file, 0600);
		return $db;
	}
	function send_email($to, $subject, $from, $body)
	{
		$msg = '<html><body>' . $body . '<br /><br /><br /><a href="' . PHOROMATIC_BASE_URL . '">' . PHOROMATIC_VIEWER_TITLE . '</a>
		<hr />
		<p><img src="http://www.phoronix-test-suite.com/web/pts-logo-60.png" /></p>
		<h6><em>The <a href="http://www.phoronix-test-suite.com/">Phoronix Test Suite</a>, <a href="http://www.phoromatic.com/">Phoromatic</a>, and <a href="http://openbenchmarking.org/">OpenBenchmarking.org</a> are products of <a href="http://www.phoronix-media.com/">Phoronix Media</a>.<br />The Phoronix Test Suite is open-source under terms of the GNU GPL. Commercial support, custom engineering, and other services are available by contacting Phoronix Media.<br />&copy; ' . date('Y') . ' Phoronix Media.</em></h6>
		</body></html>';
		$headers = "MIME-Version: 1.0\r\n";
		$headers .= "Content-type:text/html;charset=UTF-8\r\n";
		$headers .= "From: Phoromatic - Phoronix Test Suite <no-reply@phoromatic.com>\r\n";
		$headers .= "Reply-To: " . $from . " <" . $from . ">\r\n";

		mail($to, $subject, $msg, $headers);
	}

	if(isset($_POST['join_email']) && !empty($_POST['join_email']) && filter_var($_POST['join_email'], FILTER_VALIDATE_EMAIL) && (isset($_POST['notify_new_results']) || isset($_POST['notify_new_regressions'])))
	{
		// ENTER EMAIL
		$db = phoromatic_mailing_list_db_init();
		$stmt = $db->prepare('INSERT INTO phoromatic_notifications_emails (EmailAddress, TestSchedule, NotifyOnNewResults, NotifyOnRegressions) VALUES (:email, :test_schedule, :notify_results, :notify_regressions)');
		$stmt->bindValue(':email', strtolower($_POST['join_email']));
		$stmt->bindValue(':test_schedule', $REQUESTED);
		$stmt->bindValue(':notify_results', (isset($_POST['notify_new_results']) && $_POST['notify_new_results'] ? time() : 0));
		$stmt->bindValue(':notify_regressions', (isset($_POST['notify_new_regressions']) && $_POST['notify_new_regressions'] ? time() : 0));
		$result = $stmt->execute();

		if($result)
		{
			send_email($_POST['join_email'], 'Email Join Notification', EMAIL_ADDRESS_SENDER, 'This email is to confirm you will now be receiving email notifications on events from the Phoromatic <em>' . $REQUESTED . '</em> tracker on ' . PHOROMATIC_VIEWER_TITLE . '.');
		}
	}
	else if(isset($_REQUEST['upload_event_completed']))
	{
		// Check for those that want new notifications just about new result uploads
		$db = phoromatic_mailing_list_db_init();
		foreach($export_index_json['phoromatic'] as $schedule => $data)
		{
			$stmt = $db->prepare('SELECT * FROM phoromatic_notifications_emails WHERE TestSchedule LIKE :test_schedule AND NotifyOnNewResults NOT LIKE 0 AND NotifyOnNewResults < :latest_result_time_for_schedule');
			$stmt->bindValue(':test_schedule', $schedule);
			$stmt->bindValue(':latest_result_time_for_schedule', $data['last_result_time']);
			$result = $stmt ? $stmt->execute() : false;

			while($result && ($row = $result->fetchArray()))
			{
				send_email($row['EmailAddress'], 'New Results Uploaded For: ' . $data['title'], EMAIL_ADDRESS_SENDER, '<p>This email is to notify you that new test results have been uploaded for the ' . $data['title'] . ' performance tracker on ' . PHOROMATIC_VIEWER_TITLE . '</p><p><strong>View the latest results: <a href="' . PHOROMATIC_BASE_URL . '?' . $schedule . '">' . PHOROMATIC_BASE_URL . '?' . $schedule . '</a></strong></p>');

				$stmt = $db->prepare('UPDATE phoromatic_notifications_emails SET NotifyOnNewResults = :latest_result_time_for_schedule WHERE EmailAddress = :email_address AND TestSchedule LIKE :test_schedule');
				$stmt->bindValue(':email_address', $row['EmailAddress']);
				$stmt->bindValue(':test_schedule', $schedule);
				$stmt->bindValue(':latest_result_time_for_schedule', $data['last_result_time']);
				$stmt->execute();
			}
		}

		// Check for those that want new notifications just about potential regressions
		$db = phoromatic_mailing_list_db_init();
		foreach($export_index_json['phoromatic'] as $schedule => $data)
		{
			$result_files = array();
			$triggers = array_splice($data['triggers'], 0, 2);

			foreach($triggers as $trigger)
			{
				$results_for_trigger = glob(PATH_TO_EXPORTED_PHOROMATIC_DATA . '/' . $schedule . '/' . $trigger . '/*/composite.xml');

				if($results_for_trigger == false)
					continue;

				foreach($results_for_trigger as $composite_xml)
				{
					// Add to result file
					$system_name = basename(dirname($composite_xml)) . ': ' . $trigger;
					$rf = new pts_result_file($composite_xml);
					$rf->rename_run(null, $system_name);
					$result_files[] = $rf;
				}
			}

			$attributes = array();
			$result_file = new pts_result_file(null, true);
			$result_file->merge($result_files);
			//$result_file->set_title();
			$extra_attributes = array('reverse_result_buffer' => true, 'force_simple_keys' => true, 'force_line_graph_compact' => true, 'force_tracking_line_graph' => true);
			$has_flagged_results = false;
			$regression_text = null;
			$did_hit_a_regression = false;
			foreach($result_file->get_result_objects() as $i => $result_object)
			{
				if(!$has_flagged_results)
				{
					$regression_text.= '<hr /><h2>Flagged Results</h2>';
					$regression_text.= '<p>Displayed are results for each system of each scheduled test where there is a measurable change when comparing the most recent result to the previous result for that system for that test.</p>';
					$has_flagged_results = true;
				}
				$poi = $result_object->points_of_possible_interest(0.02, true);

				if(!empty($poi))
				{
					$did_hit_a_regression = true;
					$regression_text.= '<h4>' . $result_object->test_profile->get_title() . '<br /><em>' . $result_object->get_arguments_description() . '</em></h4><p>';
					foreach($poi as $text)
					{
						$regression_text.= '<a href="' . PHOROMATIC_BASE_URL . '?' . $schedule . '#r-' . $i . '">' . $text . '</a><br />';
					}
					$regression_text.= '</p>';
				}
			}


			$stmt = $db->prepare('SELECT * FROM phoromatic_notifications_emails WHERE TestSchedule LIKE :test_schedule AND NotifyOnRegressions NOT LIKE 0 AND NotifyOnRegressions < :latest_result_time_for_schedule');
			$stmt->bindValue(':test_schedule', $schedule);
			$stmt->bindValue(':latest_result_time_for_schedule', $data['last_result_time']);
			$result = $stmt ? $stmt->execute() : false;

			while($result && ($row = $result->fetchArray()))
			{
				// EMAIL OUT REGRESSION
				if($did_hit_a_regression)
				{
					send_email($row['EmailAddress'], 'Potential Regressions For: ' . $data['title'], EMAIL_ADDRESS_SENDER, '<p>This email is to notify you that there is a new potential regression or other change in performance for the ' . $data['title'] . ' performance tracker on ' . PHOROMATIC_VIEWER_TITLE . '</p>' . $regression_text . ' <p><br /><br /><br /><strong>View the latest results: <a href="' . PHOROMATIC_BASE_URL . '?' . $schedule . '">' . PHOROMATIC_BASE_URL . '?' . $schedule . '</a></strong></p>');
				}

				// REPORT BUILD / RUNTIME ERRORS
				$export_errors = file_get_contents(PATH_TO_EXPORTED_PHOROMATIC_DATA . 'export-test-errors.json');
				$export_errors  = json_decode($export_errors, true);

				if(isset($export_errors['phoromatic'][$schedule]))
				{
					$error_report = null;
					foreach($export_errors['phoromatic'][$schedule] as &$error)
					{
						if($error['error_time'] > $row['NotifyOnRegressions'])
						{
							$error_report .= '<p><strong style="font-weight: 600;">' . $error['system'] . ' - ' . $error['trigger'] . ' - ' . $error['test'] . ' - ' . $error['test_description'] . ':</strong> ' . $error['error'] . '</p>';
						}
					}

					if(!empty($error_report))
					{
						send_email($row['EmailAddress'], 'Reported Build/Runtime Errors: ' . $data['title'], EMAIL_ADDRESS_SENDER, '<p>This email is to notify you that there have been some reported test build or test run-time errors reported for the ' . $data['title'] . ' performance tracker on ' . PHOROMATIC_VIEWER_TITLE . '</p>' . $error_report . ' <p><br /><br /><br /><strong>View the latest results: <a href="' . PHOROMATIC_BASE_URL . '?' . $schedule . '">' . PHOROMATIC_BASE_URL . '?' . $schedule . '</a></strong></p>');
					}
				}
				// UPDATE META_DATA
				$stmt = $db->prepare('UPDATE phoromatic_notifications_emails SET NotifyOnRegressions = :latest_result_time_for_schedule WHERE EmailAddress = :email_address AND TestSchedule LIKE :test_schedule');
				$stmt->bindValue(':email_address', $row['EmailAddress']);
				$stmt->bindValue(':test_schedule', $schedule);
				$stmt->bindValue(':latest_result_time_for_schedule', $data['last_result_time']);
				$stmt->execute();
			}
		}
	}

}

?>
<!DOCTYPE html>
<html>
<head>
<title>Phoronix Test Suite Phoromatic - Benchmark Viewer - <?php echo $title; ?></title>
<link href="phoromatic-export-viewer.css" rel="stylesheet" type="text/css" />
<meta name="keywords" content="Linux benchmarks, open-source benchmarks, benchmark viewer, Phoronix Test Suite, Phoromatic, Phoromatic viewer" />
<meta name="Description" content="<?php echo $meta_desc; ?>" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link href='https://fonts.googleapis.com/css?family=Quicksand:700' rel='stylesheet' type='text/css'/>
<link href='https://fonts.googleapis.com/css?family=Raleway' rel='stylesheet' type='text/css'/>
<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<div id="top_list">
<ul>
<li><?php echo PHOROMATIC_VIEWER_TITLE ?></li>
<?php

foreach($export_index_json['phoromatic'] as &$schedule)
{
	if($schedule['id'] === $REQUESTED)
	{
		echo '<li id="alt"><a href="?' . $schedule['id'] . '">' . $schedule['title'] . '</a></li>';
	}
	else
	{
		echo '<li><a href="?' . $schedule['id'] . '">' . $schedule['title'] . '</a></li>';
	}
}

?>
</ul>
</div>
<hr />
<h1><?php echo $tracker['title']; ?></h1>
<p id="phoromatic_descriptor"><?php echo $tracker['description'] ?><br /><br /><strong style="font-weight: 800;">Tracker History:</strong> <em><?php echo date('j F Y', $export_index_json['phoromatic'][$REQUESTED]['first_result_time']) . ' - ' . date('j F Y H:i', $export_index_json['phoromatic'][$REQUESTED]['last_result_time']); ?></em></p>
<div id="config_option_line">
<form action="<?php $_SERVER['REQUEST_URI']; ?>" name="update_result_view" method="post">
Show Results For The Past <select name="view_results_limit" id="view_results_limit">
<?php

foreach(array(14 => 'Two Weeks', 21 => 'Three Weeks', 30 => 'One Month',  60 => 'Two Months', 90 => 'Three Months', 120 => 'Four Months', 180 => 'Six Months', 270 => 'Nine Months', 365 => 'One Year') as $days => $st)
{
	if($days > $length)
	{
		break;
	}

	echo '<option value="' . $days . '" ' . (isset($_REQUEST['view_results_limit']) && $_REQUEST['view_results_limit'] == $days ? 'selected="selected"' : null) . ' >' . $st . '</option>';
}
echo '<option value="' . count($tracker['triggers']) . '">All Results</option>';
?>
</select> Days.<br /><br />

<input type="checkbox" name="normalize_results" value="1" <?php echo (isset($_REQUEST['normalize_results']) && $_REQUEST['normalize_results'] == 1 ? 'checked="checked"' : null); ?> /> Normalize Results?

<input type="checkbox" name="clear_unchanged_results" value="1" <?php echo (isset($_REQUEST['clear_unchanged_results']) && $_REQUEST['clear_unchanged_results'] == 1 ? 'checked="checked"' : null); ?> /> Clear Unchanged Results?

<input type="checkbox" name="clear_noisy_results" value="1" <?php echo (isset($_REQUEST['clear_noisy_results']) && $_REQUEST['clear_noisy_results'] == 1 ? 'checked="checked"' : null); ?> /> Clear Noisy Results?

<input type="checkbox" name="system_table" value="1" <?php echo (isset($_REQUEST['system_table']) && $_REQUEST['system_table'] == 1 ? 'checked="checked"' : null); ?> /> Show System Information Table?

<input type="checkbox" name="regression_detector" value="1" <?php echo (isset($_REQUEST['regression_detector']) && $_REQUEST['regression_detector'] == 1 ? 'checked="checked"' : null); ?> /> Attempt To Show Results Of Interest?

<input type="checkbox" name="show_errors" value="1" <?php echo (isset($_REQUEST['show_errors']) && $_REQUEST['show_errors'] == 1 ? 'checked="checked"' : null); ?> /> Show Build / Runtime Errors?

<input type="checkbox" name="result_overview_table" value="1" <?php echo (isset($_REQUEST['result_overview_table']) && $_REQUEST['result_overview_table'] == 1 ? 'checked="checked"' : null); ?> /> Show Result Overview Table?

<br /><br /><input type="submit" value="Refresh Results">

</form>
</div>
<?php if(defined('PATH_TO_PHOROMATIC_ML_DB') && PATH_TO_PHOROMATIC_ML_DB != null) { ?>
<hr />
<h2>Email Notifications - <?php echo $tracker['title']; ?></h2>
<form action="<?php $_SERVER['REQUEST_URI']; ?>" name="update_result_view" method="post">
<p align="center">Email Address: <input type="text" name="join_email" /></p>
<p align="center"><input type="checkbox" name="notify_new_results" value="1" /> Notify When New Results Uploaded? <input type="checkbox" name="notify_new_regressions" value="1" /> Notify When Potential Regressions Spotted? </p>
<p align="center"><input type="submit" value="Add Email Notification"></p>
<?php } ?>
<blockquote>
<?php if(isset($welcome_msg) && !empty($welcome_msg)) { echo '<p>' . str_replace(PHP_EOL, '<br />', $welcome_msg) . '</p><hr />'; } ?>
<p>This service is powered by the <a href="http://www.phoronix-test-suite.com/">Phoronix Test Suite</a>'s built-in <a href="http://www.phoromatic.com/">Phoromatic</a> test orchestration and centralized performance management software. The tests are hosted by <a href="http://openbenchmarking.org/">OpenBenchmarking.org</a>. The public code is <a href="http://github.com/phoronix-test-suite/phoronix-test-suite/">hosted on GitHub</a>.</p>
<p><a href="http://www.phoronix-test-suite.com/"><img src="images/pts.png" /></a> &nbsp; &nbsp; &nbsp; <a href="http://www.phoromatic.com/"><img src="images/phoromatic.png" /></a> &nbsp; &nbsp; &nbsp; <a href="http://openbenchmarking.org/"><img src="images/ob.png" /></a></p></blockquote>

<?php

ini_set('memory_limit', '4G');
if(isset($_REQUEST['view_results_since']) && ($st = strtotime($_REQUEST['view_results_since'])) != false)
{
	$cut_duration = ceil((time() - $st) / 86400);
}
else if(isset($_REQUEST['view_results_limit']) && is_numeric($_REQUEST['view_results_limit']) && $_REQUEST['view_results_limit'] > 7)
{
	$cut_duration = $_REQUEST['view_results_limit'];
}
else
{
	$cut_duration = 30;
}

$result_files = array();
$triggers = array_splice($tracker['triggers'], 0, $cut_duration);

foreach($triggers as $trigger)
{
	$results_for_trigger = glob(PATH_TO_EXPORTED_PHOROMATIC_DATA . '/' . $REQUESTED . '/' . $trigger . '/*/composite.xml');

	if($results_for_trigger == false)
		continue;

	foreach($results_for_trigger as $composite_xml)
	{
		// Add to result file
		$system_name = basename(dirname($composite_xml)) . ': ' . $trigger;
		$rf = new pts_result_file($composite_xml);
		$rf->rename_run(null, $system_name);
		$result_files[] = $rf;
	}
}

$attributes = array();
$result_file = new pts_result_file(null, true);
$result_file->merge($result_files);
$result_file->set_title($tracker['title']);
$extra_attributes = array('reverse_result_buffer' => true, 'force_simple_keys' => true, 'force_line_graph_compact' => true, 'force_tracking_line_graph' => true);

if(isset($_REQUEST['normalize_results']) && $_REQUEST['normalize_results'])
{
	$extra_attributes['normalize_result_buffer'] = true;
}
if(isset($_REQUEST['clear_unchanged_results']) && $_REQUEST['clear_unchanged_results'])
{
	$extra_attributes['clear_unchanged_results'] = true;
}
if(isset($_REQUEST['clear_noisy_results']) && $_REQUEST['clear_noisy_results'])
{
	$extra_attributes['clear_noisy_results'] = true;
}

if(isset($_REQUEST['regression_detector']))
{
	$has_flagged_results = false;
	foreach($result_file->get_result_objects() as $i => $result_object)
	{
		if(!$has_flagged_results)
		{
			echo '<hr /><h2>Flagged Results</h2>';
			echo '<p>Displayed are results for each system of each scheduled test where there is a measurable change when comparing the most recent result to the previous result for that system for that test.</p>';
			$has_flagged_results = true;
		}
		$poi = $result_object->points_of_possible_interest(isset($_REQUEST['regression_threshold']) ? $_REQUEST['regression_threshold'] : 0.05);

		if(!empty($poi))
		{
			echo '<h4>' . $result_object->test_profile->get_title() . '<br />' . $result_object->get_arguments_description() . '</h4><p>';
			foreach($poi as $text)
			{
				echo '<a href="#r-' . $i . '">' . $text . '</a><br />';
			}
			echo '</p>';
		}
	}
}
if(isset($_REQUEST['show_errors']))
{
	$export_errors = file_get_contents(PATH_TO_EXPORTED_PHOROMATIC_DATA . 'export-test-errors.json');
	$export_errors  = json_decode($export_errors, true);

	if(isset($export_errors['phoromatic'][$REQUESTED]))
	{
		echo '<hr /><h2>Build / Runtime Errors</h2>';
		foreach($export_errors['phoromatic'][$REQUESTED] as &$error)
		{
			echo '<p><strong style="font-weight: 600;">' . $error['system'] . ' - ' . $error['trigger'] . ' - ' . $error['test'] . ' - ' . $error['test_description'] . ':</strong> ' . $error['error'] . '</p>';
		}
	}
}
if(isset($_REQUEST['result_overview_table']) || $result_file->get_test_count() < 10)
{
	$intent = null;
	$table = new pts_ResultFileTable($result_file, $intent);
	echo '<p style="text-align: center; overflow: auto;" class="result_object">' . pts_render::render_graph_inline_embed($table, $result_file, $extra_attributes) . '</p>';
}

echo '<div id="pts_results_area">';
foreach($result_file->get_result_objects((isset($_REQUEST['show_only_changed_results']) ? 'ONLY_CHANGED_RESULTS' : -1)) as $i => $result_object)
{
	if(stripos($result_object->get_arguments_description(), 'frame time') !== false)
		continue;
	$res = pts_render::render_graph_inline_embed($result_object, $result_file, $extra_attributes);

	if($res == false)
	{
		continue;
	}

	echo '<h2><a name="r-' . $i . '"></a>' . $result_object->test_profile->get_title() . '</h2>';
	//echo '<h3>' . $result_object->get_arguments_description() . '</h3>';
	echo '<p class="result_object">';
	echo $res;
	echo '</p>';
	unset($result_object);
	flush();
}
echo '</div>';

if(isset($_REQUEST['system_table']) && $_REQUEST['system_table'])
{
	$table = new pts_ResultFileSystemsTable($result_file);
	echo '<p style="text-align: center; overflow: auto;" class="result_object">' . pts_render::render_graph_inline_embed($table, $result_file, $extra_attributes) . '</p>';
}

?>

<p id="footer"><em><?php echo pts_core::program_title(true); ?></em><br />Phoronix Test Suite, Phoromatic, and OpenBenchmarking.org are copyright &copy; 2004 - <?php echo date('Y'); ?> by Phoronix Media.<br />The Phoronix Test Suite / Phoromatic is open-source under the GNU GPL.<br />For more information, visit <a href="http://www.phoronix-test-suite.com/">Phoronix-Test-Suite.com</a> or contact <a href="http://www.phoronix-media.com/">Phoronix Media</a>.</p>
</body>
</html>
