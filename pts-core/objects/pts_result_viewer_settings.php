<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2020, Phoronix Media
	Copyright (C) 2008 - 2020, Michael Larabel

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
		$drop_down_menus = array('Export Result Data' => array(
						'export=pdf' => 'Result File To PDF',
						'export=txt' => 'Result File To Text',
						'export=csv' => 'Result File To CSV/Excel',
						'export=csv-all' => 'Individual Run Data To CSV/Excel',
						),
					);
		if(count($result_file->get_system_identifiers()) > 1)
		{
			$drop_down_menus['Sort Results'] = array(
				'sro' => 'By Identifier (ASC)',
				'sro&rro' => 'By Identifier (DESC)',
				'sor' => 'By Performance (ASC)',
				'sor&rro' => 'By Performance (DESC)',
				);
		}
		if($result_file->get_test_count() > 1)
		{
			$drop_down_menus['Sort Graph Order'] = array(
				'grs' => 'By Result Spread',
				'gru' => 'By Result Unit',
				'grt' => 'By Test Title'
				);
		}

		$analyze_options .= '<div style="clear: both; float: right;"><ul>';
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
				$analyze_options .= '<li><a href="' . $uri . '&' . $option . '&' .  http_build_query($_POST) . '">' . $txt . '</a></li>';
			}
			$analyze_options .= '</ul></li>';
		}
		$analyze_options .= '</ul></div>';
		return $analyze_options;
	}
	public static function get_html_options_markup(&$result_file, &$request)
	{
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
		// END OF CHECKS

		$analyze_options .= '<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">';
		$analyze_checkboxes = array(
			'Statistics' => array(),
			'Sorting' => array(),
			'Graph Settings' => array(),
			'Multi-Way Comparison' => array(),
			'Helpers' => array()
			);

		if($system_identifier_count > 1)
		{
			$analyze_checkboxes['Statistics'][] = array('shm', 'Show Overall Harmonic Mean(s)');
			$analyze_checkboxes['Statistics'][] = array('sgm', 'Show Overall Geometric Mean');
			$analyze_checkboxes['Statistics'][] = array('swl', 'Show Wins / Losses Counts (Pie Chart)');
			$analyze_checkboxes['Statistics'][] = array('nor', 'Normalize Results');
			$analyze_checkboxes['Graph Settings'][] = array('ftr', 'Force Line Graphs (Where Applicable)');
			$analyze_checkboxes['Graph Settings'][] = array('scalar', 'Convert To Scalar (Where Applicable)');
			$analyze_checkboxes['Helpers'][] = array('spr', 'Show Notable Results');

			if($has_identifier_with_color_brand)
			{
				$analyze_checkboxes['Graph Settings'][] = array('ncb', 'No Color Branding');
			}
		}

		$analyze_checkboxes['Graph Settings'][] = array('vb', 'Prefer Vertical Bar Graphs');
		$analyze_checkboxes['Statistics'][] = array('rol', 'Remove Outliers Before Calculating Averages');
		$analyze_checkboxes['Statistics'][] = array('gtb', 'Graph Values Of All Runs (Box Plot)');
		$analyze_checkboxes['Statistics'][] = array('gtl', 'Graph Values Of All Runs (Line Graph)');

		if($result_file->get_test_count() > 12 && defined('PTS_TEST_SUITE_PATH') && is_dir(PTS_TEST_SUITE_PATH))
		{
			$analyze_checkboxes['Helpers'][] = array('sts', 'Show Performance Breakdown By Performance-Per-Suite');
		}

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
			$t .= '<div style="float: left; overflow: hidden; padding: 4px;">';
			$t .= '<h2>' . $title . '</h2>';
			foreach($group as $i => $key)
			{
				$t .= '<input type="checkbox" name="' . $key[0] . '" value="y"' . (self::check_request_for_var($request, $key[0]) ? ' checked="checked"' : null) . ' /> ' . $key[1] . '<br />';
			}
			$t .= '</div>';
		}

		if($system_identifier_count > 1)
		{
			$has_system_logs = glob($result_file->get_system_log_dir() . '/*/*');
			$t .= '<div style="clear: both;"><h2>Run Management</h2>
<div class="div_table">
<div class="div_table_body">
<div class="div_table_first_row">
<div class="div_table_cell">Highlight<br />Result</div>
<div class="div_table_cell">Hide<br />Result</div>
<div class="div_table_cell">Identifier</div>';

if($has_system_logs)
{
	$t .= '<div class="div_table_cell">View Logs</div>';
}

$t .= '<div class="div_table_cell">Perf-Per-Dollar</div>
<div class="div_table_cell">Test Completion</div>
</div>
';
$hgv = self::check_request_for_var($request, 'hgv');
$rmm = self::check_request_for_var($request, 'rmm');
foreach($result_file->get_systems() as $sys)
{
	$si = $sys->get_identifier();
	$ppd = self::check_request_for_var($request, 'ppd_' . base64_encode($si));
$t .= '
	<div class="div_table_row">
	<div class="div_table_cell"><input type="checkbox" name="hgv[]" value="' . $si . '"' . (is_array($hgv) && in_array($si, $hgv) ? ' checked="checked"' : null) . ' /></div>
	<div class="div_table_cell"><input type="checkbox" name="rmm[]" value="' . $si . '"' . (is_array($rmm) && in_array($si, $rmm) ? ' checked="checked"' : null) . ' /></div>
	<div class="div_table_cell">' . $si . '</div>';

	if($has_system_logs)
	{
		$t .= '<div class="div_table_cell">' . ($result_file->get_system_log_dir($si) ? '<a class="mini" href="#" onclick="javascript:display_system_logs_for_result(\'' . RESULTS_VIEWING_ID . '\', \'' . $si . '\'); return false;">View System Logs</a>' : ' ') . '</div>';
	}

	$t .= '<div class="div_table_cell"><input type="number" min="0" step="0.01" name="ppd_' . base64_encode($si) . '" value="' . ($ppd ? $ppd : '0.00') . '" /></div>
<div class="div_table_cell">' . date('F d Y @ H:i', strtotime($sys->get_timestamp())) . '</div>
	</div>';
}

$t .= '
</div>
</div></div>';
		}

		$analyze_options .= $t . '<br /><input style="clear: both;" name="submit" value="Refresh Results" type="submit" /></form>';

		return $analyze_options;
	}
	public static function process_helper_html(&$request, &$result_file, &$extra_attributes)
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
				$result_csv = pts_result_file_output::result_file_to_csv($result_file);
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
		}
		// End result export

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
		if(defined('OPENBENCHMARKING_BUILD') && isset($request['obr_' . $check]))
		{
			return empty($request['obr_' . $check]) ? true : $request['obr_' . $check];
		}
		if(isset($request[$check]))
		{
			return empty($request[$check]) ? true : $request[$check];
		}
	}
	public static function process_request_to_attributes(&$request, &$result_file, &$extra_attributes)
	{
		if(self::check_request_for_var($request, 'sts'))
		{
			foreach(pts_result_file_analyzer::generate_geometric_mean_result_for_suites_in_result_file($result_file, true, 20) as $result)
			{
				if($result)
				{
					$result_file->add_result($result);
				}
			}
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
		if(self::check_request_for_var($request, 'grs'))
		{
			$result_file->sort_result_object_order_by_spread();
		}
		if(self::check_request_for_var($request, 'grt'))
		{
			$result_file->sort_result_object_order_by_title();
		}
		if(self::check_request_for_var($request, 'gru'))
		{
			$result_file->sort_result_object_order_by_result_scale();
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
			if(is_array($rmm))
			{
				foreach($rmm as $rm)
				{
					$result_file->remove_run($rm);
				}
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
		if(self::check_request_for_var($request, 'gtb'))
		{
			$extra_attributes['graph_render_type'] = 'HORIZONTAL_BOX_PLOT';
		}
		else if(self::check_request_for_var($request, 'gtl'))
		{
			$extra_attributes['graph_render_type'] = 'LINE_GRAPH';
			$extra_attributes['graph_raw_values'] = true;
		}
		if(self::check_request_for_var($request, 'rol'))
		{
			foreach($result_file->get_result_objects() as $i => $result_object)
			{
				$result_object->recalculate_averages_without_outliers(1.5);
			}
		}

		foreach($result_file->get_system_identifiers() as $si)
		{
			$ppd = self::check_request_for_var($request, 'ppd_' . base64_encode($si));
			if($ppd && $ppd > 0 && is_numeric($ppd))
			{
				pts_result_file_analyzer::generate_perf_per_dollar($result_file, $si, $ppd);
			}
		}
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

			$html_menu .= '<option value="' . $value . '"' . ($value == $selected || $force_select ? ' selected="selected"' : null) . '>' . $name . '</option>';
		}

		$html_menu .= '</select>';

		return $html_menu;
	}
}

?>
