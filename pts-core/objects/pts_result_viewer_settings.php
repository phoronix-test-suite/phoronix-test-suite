<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2021, Phoronix Media
	Copyright (C) 2008 - 2021, Michael Larabel

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

class pts_result_viewer_settings
{
	public static function get_html_sort_bar(&$result_file, &$request)
	{
		$analyze_options = null;
		$drop_down_menus = array('Export Benchmark Data' => array(
						'export=pdf' => 'Result File To PDF',
						'export=txt' => 'Result File To Text',
					//	'export=xml' => 'Result File To XML',
						'export=xml-suite' => 'Result File To Test Suite (XML)',
						'export=csv' => 'Result File To CSV/Excel',
						'export=csv-all' => 'Individual Run Data To CSV/Excel',
						),
					);
		if(count($result_file->get_system_identifiers()) > 1)
		{
			$drop_down_menus['Sort Result Order'] = array(
				'sro&rro' => 'By Identifier (DESC)',
				'sro' => 'By Identifier (ASC)',
				'sor' => 'By Performance (DESC)',
				'sor&rro' => 'By Performance (ASC)',
				);
		}
		if($result_file->get_test_count() > 1)
		{
			$drop_down_menus['Sort Graph Order'] = array(
				'grs' => 'By Result Spread',
				'gru' => 'By Result Unit',
				'grt' => 'By Test Title',
				'grr' => 'By Test Length/Time'
				);
		}

		$analyze_options .= '<div style="float: right;"><ul>';
		foreach(array_reverse($drop_down_menus, true) as $menu => $sub_menu)
		{
			$analyze_options .= '<li><a href="#">' . $menu . '</a><ul>';
			foreach($sub_menu as $option => $txt)
			{
				$uri = $_SERVER['REQUEST_URI'];
				foreach(array_reverse(array_keys($sub_menu)) as $rem)
				{
					$uri = str_replace('&' . $rem, null, $uri);
				}
				$analyze_options .= '<li><a href="' . $uri . '&' . $option . '">' . $txt . '</a></li>';
			}
			$analyze_options .= '</ul></li>';
		}
		$analyze_options .= '</ul></div>';
		return $analyze_options;
	}
	public static function get_html_options_markup(&$result_file, &$request, $public_id = null, $can_delete_results = false)
	{
		if($public_id == null && defined('RESULTS_VIEWING_ID'))
		{
			$public_id = RESULTS_VIEWING_ID;
		}
		$analyze_options = null;

		// CHECKS FOR DETERMINING OPTIONS TO DISPLAY
		$has_identifier_with_color_brand = false;
		$has_box_plot = false;
		$has_line_graph = false;
		$is_multi_way = $result_file->is_multi_way_comparison();
		$system_identifier_count = count($result_file->get_system_identifiers());

		foreach($result_file->get_system_identifiers() as $sys)
		{
			if(pts_render::identifier_to_brand_color($sys, null) != null)
			{
				$has_identifier_with_color_brand = true;
				break;
			}
		}

		$multi_test_run_options_tracking = array();
		$tests_with_multiple_versions = array();
		$has_test_with_multiple_options = false;
		$has_test_with_multiple_versions = false;
		foreach($result_file->get_result_objects() as $i => $result_object)
		{
			if(!$has_box_plot && $result_object->test_profile->get_display_format() == 'HORIZONTAL_BOX_PLOT')
			{
				$has_box_plot = true;
			}
			if(!$has_line_graph && $result_object->test_profile->get_display_format() == 'LINE_GRAPH')
			{
				$has_line_graph = true;
			}
			if(!$is_multi_way && !$has_test_with_multiple_options)
			{
				if(!isset($multi_test_run_options_tracking[$result_object->test_profile->get_identifier()]))
				{
					$multi_test_run_options_tracking[$result_object->test_profile->get_identifier()] = array();
				}
				$multi_test_run_options_tracking[$result_object->test_profile->get_identifier()][] = $result_object->get_arguments_description();
				if(count($multi_test_run_options_tracking[$result_object->test_profile->get_identifier()]) > 1)
				{
					$has_test_with_multiple_options = true;
					unset($multi_test_run_options_tracking);
				}
			}
			if(!$is_multi_way && !$has_test_with_multiple_versions)
			{
				$ti_no_version = $result_object->test_profile->get_identifier(false);
				if(!isset($tests_with_multiple_versions[$ti_no_version]))
				{
					$tests_with_multiple_versions[$ti_no_version] = array();
				}
				pts_arrays::unique_push($tests_with_multiple_versions[$ti_no_version], $result_object->test_profile->get_app_version());
				if(count($tests_with_multiple_versions[$ti_no_version]) > 1)
				{
					$has_test_with_multiple_versions = true;
					unset($tests_with_multiple_versions);
				}
			}

			// (optimization) if it has everything, break
			if($has_line_graph && $has_box_plot && $has_test_with_multiple_options && $has_test_with_multiple_versions)
			{
				break;
			}
		}
		$suites_in_result_file = $system_identifier_count > 1 ? pts_test_suites::suites_in_result_file($result_file, true, 0) : array();
		// END OF CHECKS

		$analyze_options .= '<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">';
		$analyze_checkboxes = array(
			'View' => array(),
			'Statistics' => array(),
			'Sorting' => array(),
			'Graph Settings' => array(),
			'Multi-Way Comparison' => array(),
			);

		if($system_identifier_count > 1)
		{
			$analyze_checkboxes['Statistics'][] = array('shm', 'Show Overall Harmonic Mean(s)');
			$analyze_checkboxes['Statistics'][] = array('sgm', 'Show Overall Geometric Mean');
			if(count($suites_in_result_file) > 1)
			{
				$analyze_checkboxes['Statistics'][] = array('sts', 'Show Geometric Means Per-Suite/Category');
			}
			$analyze_checkboxes['Statistics'][] = array('swl', 'Show Wins / Losses Counts (Pie Chart)');
			$analyze_checkboxes['Statistics'][] = array('nor', 'Normalize Results');
			$analyze_checkboxes['Graph Settings'][] = array('ftr', 'Force Line Graphs Where Applicable');
			$analyze_checkboxes['Graph Settings'][] = array('scalar', 'Convert To Scalar Where Applicable');
			$analyze_checkboxes['View'][] = array('hnr', 'Do Not Show Noisy Results');
			$analyze_checkboxes['View'][] = array('hni', 'Do Not Show Results With Incomplete Data');
			$analyze_checkboxes['View'][] = array('hlc', 'Do Not Show Results With Little Change/Spread');
			$analyze_checkboxes['View'][] = array('spr', 'List Notable Results');

			if($has_identifier_with_color_brand)
			{
				$analyze_checkboxes['Graph Settings'][] = array('ncb', 'Disable Color Branding');
			}
		}
		if(count($suites_in_result_file) > 1)
		{
			$suite_limit = '<h3>Limit displaying results to tests within:</h3>';
			$stis = self::check_request_for_var($request, 'stis');
			if(!is_array($stis))
			{
				$stis = explode(',', $stis);
			}
			ksort($suites_in_result_file);
			$suite_limit .= '<div style="max-height: 250px; overflow: scroll;">';
			foreach($suites_in_result_file as $suite_identifier => $s)
			{
				list($suite, $contained_tests) = $s;
				$id = rtrim(base64_encode($suite_identifier), '=');
				$suite_limit .= '<input type="checkbox" name="stis[]" value="' . $id . '"' . (is_array($stis) && in_array($id, $stis) ? ' checked="checked"' : null) . ' /> ' . $suite->get_title() . ' <sup><em>' . count($contained_tests) . ' Tests</em></sup><br />';
			}
			$suite_limit .= '</div>';
			$analyze_checkboxes['View'][] = array('', $suite_limit);
		}

		$analyze_checkboxes['Graph Settings'][] = array('vb', 'Prefer Vertical Bar Graphs');
		$analyze_checkboxes['Statistics'][] = array('rol', 'Remove Outliers Before Calculating Averages');
		//$analyze_checkboxes['Statistics'][] = array('gtb', 'Graph Values Of All Runs (Box Plot)');
		//$analyze_checkboxes['Statistics'][] = array('gtl', 'Graph Values Of All Runs (Line Graph)');

		if($has_box_plot || $has_line_graph)
		{
			$analyze_checkboxes['Graph Settings'][] = array('nbp', 'No Box Plots');
		}

		if($is_multi_way && $system_identifier_count > 1)
		{
			$analyze_checkboxes['Multi-Way Comparison'][] = array('cmw', 'Condense Comparison');
		}
		if(($is_multi_way && $system_identifier_count > 1) || self::check_request_for_var($request, 'cmv') || self::check_request_for_var($request, 'cts'))
		{
			$analyze_checkboxes['Multi-Way Comparison'][] = array('imw', 'Transpose Comparison');
		}
		if((!$is_multi_way && $has_test_with_multiple_options && !self::check_request_for_var($request, 'cmv')) || self::check_request_for_var($request, 'cts'))
		{
			$analyze_checkboxes['Multi-Way Comparison'][] = array('cts', 'Condense Multi-Option Tests Into Single Result Graphs');
		}
		if((!$is_multi_way && $has_test_with_multiple_versions && !self::check_request_for_var($request, 'cts')) || self::check_request_for_var($request, 'cmv'))
		{
			$analyze_checkboxes['Multi-Way Comparison'][] = array('cmv', 'Condense Test Profiles With Multiple Version Results Into Single Result Graphs');
		}

		$analyze_checkboxes['Table'][] = array('sdt', 'Show Detailed System Result Table');

		$t = null;
		foreach($analyze_checkboxes as $title => $group)
		{
			if(empty($group))
			{
				continue;
			}
			$t .= '<div class="pts_result_viewer_settings_box">';
			$t .= '<h2>' . $title . '</h2>';
			foreach($group as $key)
			{
				if($key[0] == null)
				{
					$t .= $key[1] . '<br />';
				}
				else
				{
					$t .= '<input type="checkbox" name="' . $key[0] . '" value="1"' . (self::check_request_for_var($request, $key[0]) ? ' checked="checked"' : null) . ' /> ' . $key[1] . '<br />';
				}
			}
			$t .= '</div>';
		}

		if($system_identifier_count > 1)
		{
			$has_system_logs = ($result_file->get_system_log_dir() && glob($result_file->get_system_log_dir() . '/*/*')) || is_file($result_file->get_result_dir() . 'system-logs.zip');
			$t .= '<div style="clear: both;"><h2>Run Management</h2>
<div class="div_table">
<div class="div_table_body">
<div class="div_table_first_row">
<div class="div_table_cell">Highlight<br />Result</div>
<div class="div_table_cell">Hide<br />Result</div>
<div class="div_table_cell">Result<br />Identifier</div>';

if($has_system_logs)
{
	$t .= '<div class="div_table_cell">View Logs</div>';
}

$t .= '<div class="div_table_cell">Performance Per<br />Dollar</div>
<div class="div_table_cell">Date<br />Triggered</div>
<div class="div_table_cell"> &nbsp; Test<br /> &nbsp; Duration</div>
<div class="div_table_cell"> </div>
</div>
';
$hgv = self::check_request_for_var($request, 'hgv');
if(!is_array($hgv))
{
	$hgv = explode(',', $hgv);
}
$rmm = self::check_request_for_var($request, 'rmm');
if(!is_array($rmm))
{
	$rmm = explode(',', $rmm);
}
$start_of_year = strtotime(date('Y-01-01'));
$test_run_times = $result_file->get_test_run_times();
foreach($result_file->get_systems() as $sys)
{
	$si = $sys->get_identifier();
	$ppdx = rtrim(base64_encode($si), '=');
	$ppd = self::check_request_for_var($request, 'ppd_' . $ppdx);
$t .= '
	<div id="table-line-' . $ppdx . '" class="div_table_row">
	<div class="div_table_cell"><input type="checkbox" name="hgv[]" value="' . $si . '"' . (is_array($hgv) && in_array($si, $hgv) ? ' checked="checked"' : null) . ' /></div>
	<div class="div_table_cell"><input type="checkbox" name="rmm[]" value="' . $si . '"' . (is_array($rmm) && in_array($si, $rmm) ? ' checked="checked"' : null) . ' /></div>
	<div class="div_table_cell"><strong>' . $si . '</strong></div>';

	if($has_system_logs)
	{
		$system_log_count = count($sys->log_files());
		$t .= '<div class="div_table_cell">' . ($system_log_count > 0 ? '<button type="button" onclick="javascript:display_system_logs_for_result(\'' . $public_id . '\', \'' . $si . '\'); return false;">View System Logs</button>' : ' ') . '</div>';
	}
	$stime = strtotime($sys->get_timestamp());
	$t .= '<div class="div_table_cell"><input type="number" min="0" step="0.001" name="ppd_' . $ppdx . '" value="' . ($ppd && $ppd !== true ? strip_tags($ppd) : '0') . '" /></div>
<div class="div_table_cell">' . date(($stime > $start_of_year ? 'F d' : 'F d Y'), $stime) . '</div>
<div class="div_table_cell"> &nbsp; ' . (isset($test_run_times[$si]) && $test_run_times[$si] > 0 ? pts_strings::format_time($test_run_times[$si], 'SECONDS', true, 60) : ' ') . '</div>';

	if($can_delete_results && !empty($public_id))
	{
		$t .= '<div class="div_table_cell"><button type="button" onclick="javascript:delete_run_from_result_file(\'' . $public_id . '\', \'' . $si . '\', \'' . $ppdx . '\'); return false;">Delete Run</button> <button type="button" onclick="javascript:rename_run_in_result_file(\'' . $public_id . '\', \'' . $si . '\'); return false;">Rename Run</button></div>';
	}
	$t .= '</div>';
}

if($result_file->get_system_count() > 1)
{
	$t .= '
	<div class="div_table_row">
	<div class="div_table_cell"> </div>
	<div class="div_table_cell"><input type="checkbox" onclick="javascript:invert_hide_all_results_checkboxes();" /></div>
	<div class="div_table_cell"><em>Invert Hiding All Results Option</em></div>';

	if($has_system_logs)
	{
		$t .= '<div class="div_table_cell"> </div>';
	}

	$t .= '<div class="div_table_cell">' . self::html_select_menu('ppt', 'ppt', null, array('D' => 'Dollar', 'DPH' => 'Dollar / Hour'), true) . '</div>
	<div class="div_table_cell"> </div>
	<div class="div_table_cell"> &nbsp; <em>' . pts_strings::format_time(array_sum($test_run_times) / count($test_run_times), 'SECONDS', true, 60) . '</em></div>
	<div class="div_table_cell">';

	if($can_delete_results)
	{
		$t .= '<button type="button" onclick="javascript:reorder_result_file(\'' . $public_id . '\'); return false;">Sort / Reorder Runs</button>';
	}
	$t .= '</div></div>';
}

$t .= '
</div>
</div></div>';
		}

$analyze_options .= $t;

if($system_identifier_count > 2)
{
	$analyze_options .= '<br /><div>Only show results where ' . self::html_select_menu('ftt', 'ftt', null, array_merge(array(null), $result_file->get_system_identifiers()), false) . ' is faster than ' . self::html_select_menu('ftb', 'ftb', null, array_merge(array(null), $result_file->get_system_identifiers()), false) . '</div>';
}

if($result_file->get_test_count() > 1)
{
	$analyze_options .= '<div>Only show results matching title/arguments (delimit multiple options with a comma): ' . self::html_input_field('oss', 'oss') . '</div>';
}

		$analyze_options .= '<br /><input style="clear: both;" name="submit" value="Refresh Results" type="submit" /></form>';

		return $analyze_options;
	}
	public static function process_result_export_pre_render(&$request, &$result_file, &$extra_attributes)
	{
		// Result export?
		$result_title = (isset($_GET['result']) ? $_GET['result'] : 'result');
		switch(isset($_REQUEST['export']) ? $_REQUEST['export'] : null)
		{
			case 'pdf':
				header('Content-Type: application/pdf');
				$pdf_output = pts_result_file_output::result_file_to_pdf($result_file, $result_title . '.pdf', 'D', $extra_attributes);
				exit;
			case 'csv':
				$result_csv = pts_result_file_output::result_file_to_csv($result_file, ',', $extra_attributes);
				header('Content-Description: File Transfer');
				header('Content-Type: application/csv');
				header('Content-Disposition: attachment; filename=' . $result_title . '.csv');
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
				header('Content-Disposition: attachment; filename=' . $result_title . '.csv');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . strlen($result_csv));
				echo $result_csv;
				exit;
			case 'txt':
				$result_txt = pts_result_file_output::result_file_to_text($result_file);
				header('Content-Description: File Transfer');
				header('Content-Type: text/plain');
				header('Content-Disposition: attachment; filename=' . $result_title . '.txt');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . strlen($result_txt));
				echo $result_txt;
				exit;
			case 'xml-suite':
				$suite_xml = pts_result_file_output::result_file_to_suite_xml($result_file);
				header('Content-Description: File Transfer');
				header('Content-Type: text/xml');
				header('Content-Disposition: attachment; filename=' . $result_title . '-suite.xml');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . strlen($suite_xml));
				echo $suite_xml;
				exit;
			case 'xml':
				$result_xml = $result_file->get_xml(null, true);
				header('Content-Description: File Transfer');
				header('Content-Type: text/xml');
				header('Content-Disposition: attachment; filename=' . $result_title . '.xml');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . strlen($result_xml));
				echo $result_xml;
				exit;
		}
		// End result export
	}
	public static function process_helper_html(&$request, &$result_file, &$extra_attributes)
	{
		self::process_result_export_pre_render($request, $result_file, $extra_attributes);
		$html = null;
		if(self::check_request_for_var($request, 'spr'))
		{
			$results = $result_file->get_result_objects();
			$spreads = array();
			foreach($results as $i => &$result_object)
			{
				$spreads[$i] = $result_object->get_spread();
			}
			arsort($spreads);
			$spreads = array_slice($spreads, 0, min(count($results) / 4, 10), true);

			if(!empty($spreads))
			{
				$html .= '<h3>Notable Results</h3>';
				foreach($spreads as $result_key => $spread)
				{
					$ro = $result_file->get_result_objects($result_key);
					if(!is_object($ro[0]))
					{
						continue;
					}
					$html .= '<a href="#r-' . $result_key . '">' . $ro[0]->test_profile->get_title() . ' - ' . $ro[0]->get_arguments_description() . '</a><br />';
				}
			}
		}
		return $html;
	}
	public static function check_request_for_var(&$request, $check)
	{
		// the obr_ check is to maintain OpenBenchmarking.org compatibility for its original variable naming to preserve existing URLs
		$ret = false;
		if(defined('OPENBENCHMARKING_BUILD') && isset($request['obr_' . $check]))
		{
			$ret = empty($request['obr_' . $check]) ? true : $request['obr_' . $check];
		}
		if(isset($request[$check]))
		{
			$ret = empty($request[$check]) ? true : $request[$check];
		}

		if($ret && isset($ret[5]))
		{
			$ret = str_replace('_DD_', '.', $ret);
		}

		return $ret;
	}
	public static function process_request_to_attributes(&$request, &$result_file, &$extra_attributes)
	{
		if(($oss = self::check_request_for_var($request, 'oss')))
		{
			$oss = pts_strings::comma_explode($oss);
			foreach($result_file->get_result_objects() as $i => $result_object)
			{
				$matched = false;
				foreach($oss as $search_check)
				{
					if(stripos($result_object->get_arguments_description(), $search_check) === false && stripos($result_object->test_profile->get_identifier(), $search_check) === false && stripos($result_object->test_profile->get_title(), $search_check) === false)
					{
						// Not found
						$matched = false;
					}
					else
					{
						$matched = true;
						break;
					}
				}
				if(!$matched)
				{
					$result_file->remove_result_object_by_id($i);
				}
			}
		}
		if(self::check_request_for_var($request, 'ftt') && self::check_request_for_var($request, 'ftt'))
		{
			$ftt = self::check_request_for_var($request, 'ftt');
			$ftb = self::check_request_for_var($request, 'ftb');
			if(!empty($ftt) && !empty($ftb) && $ftt !== true && $ftb !== true)
			{
				foreach($result_file->get_result_objects() as $i => $result_object)
				{
					$ftt_result = $result_object->test_result_buffer->get_result_from_identifier($ftt);
					$ftb_result = $result_object->test_result_buffer->get_result_from_identifier($ftb);

					if($ftt_result && $ftb_result)
					{
						$ftt_wins = false;

						if($result_object->test_profile->get_result_proportion() == 'HIB')
						{
							if($ftt_result > $ftb_result)
							{
								$ftt_wins = true;
							}
						}
						else
						{
							if($ftt_result < $ftb_result)
							{
								$ftt_wins = true;
							}
						}

						if(!$ftt_wins)
						{
							$result_file->remove_result_object_by_id($i);
						}
					}
					else
					{
						$result_file->remove_result_object_by_id($i);
					}
				}
			}
		}
		if(($stis = self::check_request_for_var($request, 'stis')))
		{
			if(!is_array($stis))
			{
				$stis = explode(',', $stis);
			}
			$suites_in_result_file = pts_test_suites::suites_in_result_file($result_file, true, 0);
			$tests_to_show = array();
			foreach($stis as $suite_to_show)
			{
				$suite_to_show = base64_decode($suite_to_show);
				if(isset($suites_in_result_file[$suite_to_show]))
				{
					foreach($suites_in_result_file[$suite_to_show][1] as $test_to_show)
					{
						$tests_to_show[] = $test_to_show;
					}
				}
			}

			if(!empty($tests_to_show))
			{
				foreach($result_file->get_result_objects() as $i => $result_object)
				{
					if($result_object->get_parent_hash())
					{
						if(!$result_file->get_result_object_by_hash($result_object->get_parent_hash()) || !in_array($result_file->get_result_object_by_hash($result_object->get_parent_hash())->test_profile->get_identifier(false), $tests_to_show))
						{
							$result_file->remove_result_object_by_id($i);
						}
					}
					else if(!in_array($result_object->test_profile->get_identifier(false), $tests_to_show))
					{
						$result_file->remove_result_object_by_id($i);
					}
				}
			}
		}
		if(self::check_request_for_var($request, 'hlc'))
		{
			foreach($result_file->get_result_objects() as $i => $result_object)
			{
				if($result_object->result_flat())
				{
					$result_file->remove_result_object_by_id($i);
				}
			}
		}
		if(self::check_request_for_var($request, 'hnr'))
		{
			$result_file->remove_noisy_results();
		}
		if(self::check_request_for_var($request, 'hni'))
		{
			$system_count = $result_file->get_system_count();
			foreach($result_file->get_result_objects() as $i => $result_object)
			{
				if($result_object->test_result_buffer->get_count() < $system_count)
				{
					$result_file->remove_result_object_by_id($i);
				}
			}
		}

		if(self::check_request_for_var($request, 'grs'))
		{
			$result_file->sort_result_object_order_by_spread();
		}
		else if(self::check_request_for_var($request, 'grt'))
		{
			$result_file->sort_result_object_order_by_title();
		}
		else if(self::check_request_for_var($request, 'gru'))
		{
			$result_file->sort_result_object_order_by_result_scale();
		}
		else if(self::check_request_for_var($request, 'grr'))
		{
			$result_file->sort_result_object_order_by_run_time();
		}

		if(self::check_request_for_var($request, 'shm'))
		{
			foreach(pts_result_file_analyzer::generate_harmonic_mean_result($result_file) as $result)
			{
				if($result)
				{
					$result_file->add_result($result);
				}
			}
		}
		if(self::check_request_for_var($request, 'sgm'))
		{
			$result = pts_result_file_analyzer::generate_geometric_mean_result($result_file);
			if($result)
			{
				$result_file->add_result($result);
			}
		}
		if(self::check_request_for_var($request, 'sts'))
		{
			foreach(pts_result_file_analyzer::generate_geometric_mean_result_for_suites_in_result_file($result_file, true, 0) as $result)
			{
				if($result)
				{
					$result_file->add_result($result);
				}
			}
		}
		if(self::check_request_for_var($request, 'swl'))
		{
			foreach(pts_result_file_analyzer::generate_wins_losses_results($result_file) as $result)
			{
				if($result)
				{
					$result_file->add_result($result);
				}
			}
		}
		if(self::check_request_for_var($request, 'cts'))
		{
			pts_result_file_analyzer::condense_result_file_by_multi_option_tests($result_file);
		}
		if(self::check_request_for_var($request, 'cmv'))
		{
			pts_result_file_analyzer::condense_result_file_by_multi_version_tests($result_file);
		}
		if(self::check_request_for_var($request, 'sor'))
		{
			$extra_attributes['sort_result_buffer_values'] = true;
		}
		if(self::check_request_for_var($request, 'rro'))
		{
			$extra_attributes['reverse_result_buffer'] = true;
		}
		if(self::check_request_for_var($request, 'sro'))
		{
			$extra_attributes['sort_result_buffer'] = true;
		}
		if(self::check_request_for_var($request, 'nor'))
		{
			$extra_attributes['normalize_result_buffer'] = true;
		}
		if(self::check_request_for_var($request, 'ftr'))
		{
			$extra_attributes['force_tracking_line_graph'] = true;
		}
		if(self::check_request_for_var($request, 'imw'))
		{
			$extra_attributes['multi_way_comparison_invert_default'] = false;
		}
		if(self::check_request_for_var($request, 'cmw'))
		{
			$extra_attributes['condense_multi_way'] = true;
		}
		if(($hgv = self::check_request_for_var($request, 'hgv')))
		{
			if(is_array($hgv))
			{
				$extra_attributes['highlight_graph_values'] = $hgv;
			}
			else
			{
				$extra_attributes['highlight_graph_values'] = explode(',', $hgv);
			}
		}
		else if(self::check_request_for_var($request, 'hgv_base64'))
		{
			$extra_attributes['highlight_graph_values'] = explode(',', base64_decode(self::check_request_for_var($request, 'hgv_base64')));
		}
		if(($rmm = self::check_request_for_var($request, 'rmm')))
		{
			if(!is_array($rmm))
			{
				$rmm = explode(',', $rmm);
			}

			foreach($rmm as $rm)
			{
				$result_file->remove_run($rm);
			}
		}
		if(self::check_request_for_var($request, 'scalar'))
		{
			$extra_attributes['compact_to_scalar'] = true;
		}
		if(self::check_request_for_var($request, 'ncb'))
		{
			$extra_attributes['no_color_branding'] = true;
		}
		if(self::check_request_for_var($request, 'nbp'))
		{
			$extra_attributes['no_box_plots'] = true;
		}
		if(self::check_request_for_var($request, 'vb'))
		{
			$extra_attributes['vertical_bars'] = true;
		}
		/*
		if(self::check_request_for_var($request, 'gtb'))
		{
			$extra_attributes['graph_render_type'] = 'HORIZONTAL_BOX_PLOT';
		}
		else if(self::check_request_for_var($request, 'gtl'))
		{
			$extra_attributes['graph_render_type'] = 'LINE_GRAPH';
			$extra_attributes['graph_raw_values'] = true;
		}
		*/
		if(self::check_request_for_var($request, 'rol'))
		{
			foreach($result_file->get_result_objects() as $i => $result_object)
			{
				$result_object->recalculate_averages_without_outliers(1.5);
			}
		}

		$perf_per_dollar_values = array();
		foreach($result_file->get_system_identifiers() as $si)
		{
			$ppd = self::check_request_for_var($request, 'ppd_' . rtrim(base64_encode($si), '='));
			if($ppd && $ppd > 0 && is_numeric($ppd))
			{
				$perf_per_dollar_values[$si] = $ppd;
			}
		}

		if(!empty($perf_per_dollar_values))
		{
			$perf_per_hour = self::check_request_for_var($request, 'ppt') == 'DPH';
			pts_result_file_analyzer::generate_perf_per_dollar($result_file, $perf_per_dollar_values, 'Dollar', false, $perf_per_hour);
		}
	}
	public static function html_input_field($name, $id, $on_change = null)
	{
		return '<input type="text" name="' . $name . '" id="' . $id . '" onclick="" value="' . (isset($_REQUEST[$name]) ? strip_tags($_REQUEST[$name]) : null) . '">';
	}
	public static function html_select_menu($name, $id, $on_change, $elements, $use_index = true, $other_attributes = array(), $selected = false)
	{
		$tag = null;
		foreach($other_attributes as $i => $v)
		{
			$tag .= ' ' . $i . '="' . $v . '"';
		}

		$html_menu = '<select name="' . $name . '" id="' . $id . '" onchange="' . $on_change . '"' . $tag . '>' . PHP_EOL;

		if($selected === false)
		{
			$selected = isset($_REQUEST[$name]) ? $_REQUEST[$name] : false;
		}

		$force_select = isset($other_attributes['multiple']);

		foreach($elements as $value => $name)
		{
			if($use_index == false)
			{
				$value = $name;
			}
			if($name == null)
			{
				$name = '[SELECT]';
			}

			$html_menu .= '<option value="' . $value . '"' . ($value == $selected || $force_select ? ' selected="selected"' : null) . '>' . $name . '</option>';
		}

		$html_menu .= '</select>';

		return $html_menu;
	}
}

?>
