<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2014 - 2022, Phoronix Media
	Copyright (C) 2014 - 2022, Michael Larabel

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

class phoromatic_sched implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Test Scheduling';
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
		if(PHOROMATIC_USER_IS_VIEWER)
			return;

		$is_new = true;
		$env_var_edit = array();
		if(!empty($PATH[0]) && is_numeric($PATH[0]))
		{
			$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_schedules WHERE AccountID = :account_id AND ScheduleID = :schedule_id');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$stmt->bindValue(':schedule_id', $PATH[0]);
			$result = $stmt->execute();
			$e_schedule = $result->fetchArray();

			if(!empty($e_schedule))
			{
				$is_new = false;
			}
			if(!empty($e_schedule['EnvironmentVariables']))
			{
				$env_var_edit = pts_strings::parse_value_string_vars($e_schedule['EnvironmentVariables']);
			}
		}

		if(isset($_POST['schedule_title']) && !empty($_POST['schedule_title']))
		{
			$title = phoromatic_get_posted_var('schedule_title');
			$description = phoromatic_get_posted_var('schedule_description');
			$pre_install_set_context = phoromatic_get_posted_var('pre_install_set_context');
			$post_install_set_context = phoromatic_get_posted_var('post_install_set_context');
			$pre_run_set_context = phoromatic_get_posted_var('pre_run_set_context');
			$post_run_set_context = phoromatic_get_posted_var('post_run_set_context');

			$system_all = phoromatic_get_posted_var('system_all');
			$run_target_systems = phoromatic_get_posted_var('run_on_systems', array());
			$run_target_groups = phoromatic_get_posted_var('run_on_groups', array());
			if(!is_array($run_target_systems)) $run_target_systems = array();
			if(!is_array($run_target_groups)) $run_target_groups = array();
			$run_target_systems = implode(',', $run_target_systems);
			$run_target_groups = implode(',', $run_target_groups);
			$run_priority = phoromatic_get_posted_var('run_priority');
			$run_priority = is_numeric($run_priority) && $run_priority >= 0 ? $run_priority : 100;

			$schedule_hour = phoromatic_get_posted_var('schedule_hour');
			$schedule_minute = phoromatic_get_posted_var('schedule_minute');
			$days_active = phoromatic_get_posted_var('days_active');

			$context_files = array('SetContextPreInstall' => 'pre_install_set_context', 'SetContextPostInstall' => 'post_install_set_context', 'SetContextPreRun' => 'pre_run_set_context', 'SetContextPostRun' => 'post_run_set_context');
			foreach($context_files as $i => $context)
				$$context = $is_new ? null : $e_schedule[$i];
			foreach($context_files as $context)
			{
				$$context = null;

				if($_FILES[$context]['error'] == 0 && $_FILES[$context]['size'] > 0)
				{
					$sha1_hash = sha1_file($_FILES[$context]['tmp_name']);

					if(!is_file(phoromatic_server::phoromatic_account_path($_SESSION['AccountID']) . 'context_' . $sha1_hash))
					{
						move_uploaded_file($_FILES[$context]['tmp_name'], phoromatic_server::phoromatic_account_path($_SESSION['AccountID']) . 'context_' . $sha1_hash);
					}

					$$context = $sha1_hash;
				}
			}

			// Need a unique schedule ID
			if($is_new)
			{
				do
				{
					$schedule_id = rand(10, 9999);
					$matching_schedules = phoromatic_server::$db->querySingle('SELECT ScheduleID FROM phoromatic_schedules WHERE AccountID = \'' . $_SESSION['AccountID'] . '\' AND ScheduleID = \'' . $schedule_id . '\'');
				}
				while(!empty($matching_schedules));

				// Need a unique public ID
				do
				{
					$public_key = pts_strings::random_characters(12, true);;
					$matching_schedules = phoromatic_server::$db->querySingle('SELECT ScheduleID FROM phoromatic_schedules WHERE AccountID = \'' . $_SESSION['AccountID'] . '\' AND PublicKey = \'' . $public_key . '\'');
				}
				while(!empty($matching_schedules));
			}
			else
			{
				$schedule_id = $e_schedule['ScheduleID'];
				$public_key = $e_schedule['PublicKey'];
			}

			$env_vars = array();

			foreach(pts_env::get_posted_options('phoromatic') as $ei => $ev)
			{
				array_push($env_vars, $ei . '=' . $ev);
			}
			$env_vars = implode(';', $env_vars);

			// Add schedule
			$stmt = phoromatic_server::$db->prepare('INSERT OR REPLACE INTO phoromatic_schedules (AccountID, ScheduleID, Title, Description, State, ActiveOn, RunAt, SetContextPreInstall, SetContextPostInstall, SetContextPreRun, SetContextPostRun, LastModifiedBy, LastModifiedOn, PublicKey, RunTargetGroups, RunTargetSystems, RunPriority, EnvironmentVariables) VALUES (:account_id, :schedule_id, :title, :description, :state, :active_on, :run_at, :context_pre_install, :context_post_install, :context_pre_run, :context_post_run, :modified_by, :modified_on, :public_key, :run_target_groups, :run_target_systems, :run_priority, :environment_variables)');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$stmt->bindValue(':schedule_id', $schedule_id);
			$stmt->bindValue(':title', $title);
			$stmt->bindValue(':description', $description);
			$stmt->bindValue(':state', 1);
			$stmt->bindValue(':active_on', (is_array($days_active) ? implode(',', $days_active) : $days_active));
			$stmt->bindValue(':run_at', $schedule_hour . '.' . $schedule_minute);
			$stmt->bindValue(':context_pre_install', $pre_install_set_context);
			$stmt->bindValue(':context_post_install', $post_install_set_context);
			$stmt->bindValue(':context_pre_run', $pre_run_set_context);
			$stmt->bindValue(':context_post_run', $post_run_set_context);
			$stmt->bindValue(':modified_by', $_SESSION['UserName']);
			$stmt->bindValue(':modified_on', phoromatic_server::current_time());
			$stmt->bindValue(':public_key', $public_key);
			$stmt->bindValue(':run_target_groups', $run_target_groups);
			$stmt->bindValue(':run_target_systems', $run_target_systems);
			$stmt->bindValue(':run_priority', $run_priority);
			$stmt->bindValue(':environment_variables', $env_vars);
			$result = $stmt->execute();
			phoromatic_add_activity_stream_event('schedule', $schedule_id, ($is_new ? 'added' : 'modified'));

			if($result)
			{
				header('Location: ?schedules/' . $schedule_id);
			}
		}

		echo phoromatic_webui_header_logged_in();
		$main = '<h2>' . ($is_new ? 'Create' : 'Edit') . ' A Schedule</h2>
		<p>A test schedule is used to facilitate automatically running a set of test(s) or suite(s) on either a routine timed basis or whenever triggered by an external script or process, e.g. Git/VCS commit, manually triggered, etc.</p>';

		$main .= '<form action="' . $_SERVER['REQUEST_URI'] . '" name="add_test" id="add_test" method="post" enctype="multipart/form-data" onsubmit="return validate_schedule();">
		<h3>Title:<span style="color:red;">*</span></h3>
		<p><input type="text" name="schedule_title" value="' . (!$is_new ? $e_schedule['Title'] : null) . '" /></p>
		<h3>Pre-Install Set Context Script: <span style="font-size:12px;">(optional)</span></h3>
		<p><input type="file" name="pre_install_set_context" /></p>
		<h3>Post-Install Set Context Script: <span style="font-size:12px;">(optional)</span></h3>
		<p><input type="file" name="post_install_set_context" /></p>
		<h3>Pre-Run Set Context Script: <span style="font-size:12px;">(optional)</span></h3>
		<p><input type="file" name="pre_run_set_context" /></p>
		<h3>Post-Run Set Context Script: <span style="font-size:12px;">(optional)</span></h3>
		<p><input type="file" name="post_run_set_context" /></p>
		<h3>System Targets:</h3>
		<p>';

		$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID FROM phoromatic_systems WHERE AccountID = :account_id AND State >= 0 ORDER BY Title ASC');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$result = $stmt->execute();


		if(!$is_new)
		{
			$e_schedule['RunTargetSystems'] = explode(',', $e_schedule['RunTargetSystems']);
			$e_schedule['RunTargetGroups'] = explode(',', $e_schedule['RunTargetGroups']);
		}

		if($row = $result->fetchArray())
		{
			$main .= '<h4>Systems: ';
			do
			{
				$main .= '<input type="checkbox" name="run_on_systems[]" value="' . $row['SystemID'] . '" ' . (!$is_new && in_array($row['SystemID'], $e_schedule['RunTargetSystems']) ? 'checked="checked" ' : null) . '/> ' . $row['Title'] . ' ';
			}
			while($row = $result->fetchArray());
			$main .= '</h4>';
		}

		$stmt = phoromatic_server::$db->prepare('SELECT GroupName FROM phoromatic_groups WHERE AccountID = :account_id ORDER BY GroupName ASC');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$result = $stmt->execute();

		if($row = $result->fetchArray())
		{
			$main .= '<h4>Groups: ';
			do
			{
				$main .= '<input type="checkbox" name="run_on_groups[]" value="' . $row['GroupName'] . '" ' . (!$is_new && in_array($row['GroupName'], $e_schedule['RunTargetGroups']) ? 'checked="checked" ' : null) . '/> ' . $row['GroupName'] . ' ';
			}
			while($row = $result->fetchArray());
			$main .= '</h4>';
		}

		$main .= '</p>
		<h3>Description:<span style="color:red;">*</span></h3>
		<p><textarea name="schedule_description" id="schedule_description" cols="50" rows="3">' . (!$is_new ? $e_schedule['Description'] : null) . '</textarea></p>
		<h3>Run Priority:</h3>
		<p>The run priority is used for determining which tests to execute first should there be multiple test schedules set to run on a given system at the same time. Additionally, test schedules of low-priority will not attempt to power-on a system if needed for running the test, thus delaying it\'s execution until the next time the system is otherwise online.</p>
		<p><select name="run_priority" id="run_priority">';
		$prios = array(1 => 'Low Priority', 100 => 'Default Priority', 200 => 'High Priority');
		foreach($prios as $lvl => $lvl_str)
		{
			$main .= '<option value="' . $lvl . '"' . (((!$is_new && ($e_schedule['RunPriority'] == $lvl)) || $lvl == 100) ? 'selected="selected" ' : null) . '>' . $lvl_str . '</option>';
		}
		$main .='</select></p><table class="pts_phoromatic_schedule_type">
<tr>
  <td><h3>Time-Based Testing</h3><em>Time-based testing allows tests to automatically commence at a given time on a defined cycle each day/week. This option is primarly aimed for those wishing to run a set of benchmarks every morning or night or at another defined period.</em></td>
  <td><h3>Run Time:</h3>
		<p><select name="schedule_hour" id="schedule_hour">';

		if(!$is_new)
		{
			$run_at = explode('.', $e_schedule['RunAt']);
			$days_active = !empty($e_schedule['ActiveOn']) ? explode(',', $e_schedule['ActiveOn']) : array();
		}

		for($i = 0; $i <= 23; $i++)
		{
			$i_f = (strlen($i) == 1 ? '0' . $i : $i);
			$main .= '<option value="' . $i_f . '"' . (!$is_new && $run_at[0] == $i ? 'selected="selected" ' : null) . '>' . $i_f . '</option>';
		}

		$main .= '</select> <select name="schedule_minute" id="schedule_minute">';

		for($i = 0; $i < 60; $i += 10)
		{
			$i_f = (strlen($i) == 1 ? '0' . $i : $i);
			$main .= '<option value="' . $i_f . '"' . (!$is_new && $run_at[1] == $i ? 'selected="selected" ' : null) . '>' . $i_f . '</option>';
		}

		$main .= '</select><h3>Active On:</h3><p>';
		$week = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
		foreach($week as $index => $day)
		{
			$main .= '<input type="checkbox" name="days_active[]" value="' . $index . '"' . (!$is_new && in_array($index, $days_active) ? 'checked="checked" ' : null) . '/> ' . $day;
		}

		$main .= '</p></td>
			</tr>
			<tr>
			  <td><h3>Trigger-Based Testing</h3><em>To carry out trigger-based testing, you can simply have an external process/script trigger (&quot;ping&quot;) a specialized URL whenever an event occurs to commence a new round of testing. This is the most customizable approach to having Phoromatic run tests on a system if you wish to have it occur whenever a Git/SVN commit takes place or other operations.</em></td>
			  <td><h3>Once creating the test schedule there will be a specialized URL you can use for &quot;pinging&quot; where you can pass it a Git commit hash, SVN revision number, date, or other unique identifiers to externally trigger the test schedules and systems to begin testing. This custom trigger is passed to any of the used context scripts for setting up the system in an appropriate state.</h3></td>
			</tr>
			<tr>
			  <td><h3>One-Time / Manual Testing</h3><em>Carrying out Phoromatic-controlled benchmark on no routine schedule, similar to the trigger-based testing.</em></td>
			  <td><h3>If you wish to only run a set of tests once on a given system or to do so seldom with the same set of tests, simply proceed with creating the test schedule without setting any run time / active days. When going to the web page for this test schedule there will be a button to trigger the tests to run on all affected systems. One-time benchmarking can also be setup via the <a href="?benchmark">Run A Benchmark</a> page.</h3></td>
			</tr>
			</table>';

		$main .= (empty($env_var_edit) ? '<p><a id="env_var_options_show" onclick="javascript:document.getElementById(\'env_var_options\').style.display = \'block\'; javascript:document.getElementById(\'env_var_options_show\').style.display = \'none\'; ">Advanced Options</a></p> <div id="env_var_options" style="display: none;">' : '<div id="env_var_options">') . '<p>The advanced options require the Phoromatic clients be on the latest Phoronix Test Suite (10.8 or newer / Git). See the Phoronix Test Suite documentation for more information on these environment variables / advanced options.</p>' . pts_env::get_html_options('phoromatic', $env_var_edit) . '</div>';

		$main .= '<p align="right"><input name="submit" value="' . ($is_new ? 'Create' : 'Edit') . ' Schedule" type="submit" onclick="return pts_rmm_validate_schedule();" /></p>
			</form>';
		echo phoromatic_webui_main($main);
		echo phoromatic_webui_footer();
	}
}

?>
