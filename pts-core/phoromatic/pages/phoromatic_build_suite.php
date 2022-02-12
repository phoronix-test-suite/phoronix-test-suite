<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2015 - 2022, Phoronix Media
	Copyright (C) 2015 - 2022, Michael Larabel

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

class phoromatic_build_suite implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Build Custom Test Suite';
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
		if(isset($_POST['suite_title']))
		{
			phoromatic_quit_if_invalid_input_found(array('suite_title', 'test_add', 'suite_version', 'suite_description'));
			$proceed = true;

			if(strlen($_POST['suite_title']) < 3 || pts_strings::keep_in_string($_POST['suite_title'], pts_strings::CHAR_LETTER | pts_strings::CHAR_NUMERIC | pts_strings::CHAR_DASH) != $_POST['suite_title'])
			{
				echo '<h2>Suite title must be at least three characters and contain just alpha-numeric characters and dashes allowed.</h2>';
				$proceed = false;
			}
			if(!isset($_POST['suite_version']) || empty($_POST['suite_version']) || !pts_strings::is_version($_POST['suite_version']))
			{
				echo '<h2>Suite version must be valid numeric version format X.Y.Z.</h2>';
				$proceed = false;
			}

			$tests = array();

			foreach($_POST['test_add'] as $i => $test_identifier)
			{
				$test_prefix = $_POST['test_prefix'][$i];
				$args = array();
				$args_name = array();

				foreach($_POST as $i => $v)
				{
					if(strpos($i, $test_prefix) !== false && substr($i, -9) != '_selected')
					{
						phoromatic_quit_if_invalid_input_found(array($i, $i . '_selected'));
						if(strpos($v, '||') !== false)
						{
							$opts = explode('||', $v);
							$a = array();
							$d = array();
							foreach($opts as $opt)
							{
								$t = explode('::', $opt);
								$a[] = $t[1];
								$d[] = $t[0];
							}
							$args[] = $a;
							$args_name[] = $d;
						}
						else
						{
							$args[] = array($v);
							$args_name[] = array($_POST[$i . '_selected']);
						}
					}
				}

				$test_args = array();
				$test_args_description = array();
				pts_test_run_options::compute_all_combinations($test_args, null, $args, 0);
				pts_test_run_options::compute_all_combinations($test_args_description, null, $args_name, 0, ' - ');

				foreach(array_keys($test_args) as $i)
				{
					$tests[] = array('test' => $test_identifier, 'description' => $test_args_description[$i], 'args' => $test_args[$i]);
				}
			}

			if(count($tests) < 1)
			{
				echo '<h2>You must add at least one test to the suite.</h2>';
				$proceed = false;
			}

			if($proceed)
			{
				$new_suite = new pts_test_suite();
				$version_bump = 0;

			//	do
			//	{
					//$suite_version = '1.' . $version_bump . '.0';
					$suite_version = $_POST['suite_version'];
					$suite_id = $new_suite->clean_save_name_string($_POST['suite_title']) . '-' . $suite_version;
					$suite_dir = phoromatic_server::phoromatic_account_suite_path($_SESSION['AccountID'], $suite_id);
			//		$version_bump++;
			//	}
			//	while(is_dir($suite_dir));
				pts_file_io::mkdir($suite_dir);
				$save_to = $suite_dir . '/suite-definition.xml';

				$new_suite->set_title($_POST['suite_title']);
				$new_suite->set_version($suite_version); // $suite_version
				$new_suite->set_maintainer($_SESSION['UserName']);
				$new_suite->set_suite_type('System');
				$new_suite->set_description($_POST['suite_description']);

				foreach($tests as $m)
				{
					$new_suite->add_to_suite($m['test'], $m['args'], $m['description']);
				}

				$new_suite->save_xml(null, $save_to);
				echo '<h2>Saved As ' . $suite_id . '</h2>';
				phoromatic_add_activity_stream_event('suite', $suite_id, 'added');
			}
		}
		echo phoromatic_webui_header_logged_in();
		$main = '<h1>Local Suites</h1><p>Find already created local test suites by your account/group via the <a href="/?local_suites">local suites</a> page.</p>';

		if(!PHOROMATIC_USER_IS_VIEWER)
		{
			$suite = null;
			if(isset($PATH[0]))
			{
				$suite = phoromatic_server::phoromatic_account_suite_path($_SESSION['AccountID'], $PATH[0]) . '/suite-definition.xml';
				if(!is_file($suite))
				{
					$suite = null;
				}
			}
			$suite = new pts_test_suite($suite);

			$main .= '<h1>Build Suite</h1><p>A test suite in the realm of the Phoronix Test Suite, OpenBenchmarking.org, and Phoromatic is <strong>a collection of test profiles with predefined settings</strong>. Establishing a test suite makes it easy to run repetitive testing on the same set of test profiles by simply referencing the test suite name.</p>';
			$main .= '<form action="' . $_SERVER['REQUEST_URI'] . '" name="build_suite" id="build_suite" method="post" onsubmit="return validate_suite();">
			<h3>Title:</h3>
			<p><input type="text" name="suite_title" value="' . $suite->get_title() . '" /></p>
			<h3>Suite Version:</h3>
			<p><input type="text" name="suite_version" value="' . ($suite->get_version() == null ? '1.0.0' : $suite->get_version()) . '" /></p>
			<h3>Description:</h3>
			<p><textarea name="suite_description" id="suite_description" cols="60" rows="2">' . $suite->get_description() . '</textarea></p>
			<h3>Tests In Schedule:</h3>
			<p><div id="test_details"></div></p>
			<script type="text/javascript">';

			foreach($suite->get_contained_test_result_objects() as $obj)
			{
				$main .= 'phoromatic_ajax_append_element("r_add_test_build_suite_details/&tp=' . $obj->test_profile->get_identifier() . '&tpa=' . $obj->get_arguments_description() . '", "test_details");' . PHP_EOL;
			}
			$main .= '</script>
			<h3>Add Another Test</h3>';
			$main .= '<select name="add_to_suite_select_test" id="add_to_suite_select_test" onchange="phoromatic_build_suite_test_details();"><option value=""></option>';
			$dc = pts_client::download_cache_path();
			$dc_exists = is_file($dc . 'pts-download-cache.json');
			if($dc_exists)
			{
				$cache_json = file_get_contents($dc . 'pts-download-cache.json');
				$cache_json = json_decode($cache_json, true);
			}
			foreach(array_merge(pts_tests::local_tests(), pts_openbenchmarking::available_tests(false, isset($_COOKIE['list_show_all_test_versions']) && $_COOKIE['list_show_all_test_versions'])) as $test)
			{
				$cache_checked = false;
				if(phoromatic_server::read_setting('show_local_tests_only'))
				{
					if($dc_exists)
					{
						if($cache_json && isset($cache_json['phoronix-test-suite']['cached-tests']))
						{
							$cache_checked = true;
							if(!in_array($test, $cache_json['phoronix-test-suite']['cached-tests']))
							{
								continue;
							}
						}
					}
					if(!$cache_checked && phoromatic_server::read_setting('show_local_tests_only') && pts_test_install_request::test_files_available_on_local_system($test) == false)
					{
						continue;
					}
				}
				$main .= '<option value="' . $test . '">' . $test . '</option>';
			}
			$main .= '</select>';
			$main .= pts_web_embed::cookie_checkbox_option_helper('list_show_all_test_versions', 'Show all available test profile versions.');
			$main .= '<p align="right"><input name="submit" value="' . ($suite->get_title() != null ? 'Edit' : 'Create') .' Suite" type="submit" onclick="return pts_rmm_validate_suite();" /></p>';
		}
		echo '<div id="pts_phoromatic_main_area">' . $main . '</div>';
		echo phoromatic_webui_footer();
	}
}

?>
