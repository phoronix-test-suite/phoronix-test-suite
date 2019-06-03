<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2019, Phoronix Media
	Copyright (C) 2008 - 2019, Michael Larabel

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
				$uri = CURRENT_URI;
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
	public static function get_html_options_markup(&$result_file, &$request)
	{
		$analyze_options = null;

		if(count($result_file->get_system_identifiers()) > 1)
		{
			// CHECKS FOR DETERMINING OPTIONS TO DISPLAY
			$has_identifier_with_color_brand = false;
			$has_box_plot = false;
			$has_line_graph = false;

			foreach($result_file->get_system_identifiers() as $sys)
			{
				if(pts_render::identifier_to_brand_color($sys, null) != null)
				{
					$has_identifier_with_color_brand = true;
					break;
				}
			}

			foreach($result_file->get_result_objects() as $i => &$result_object)
			{
				if(!$has_box_plot && $result_object->test_profile->get_display_format() == 'HORIZONTAL_BOX_PLOT')
				{
					$has_box_plot = true;
				}
				if(!$has_line_graph && $result_object->test_profile->get_display_format() == 'LINE_GRAPH')
				{
					$has_line_graph = true;
				}

				// (optimization) if it has everything, break
				if($has_line_graph && $has_box_plot)
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
			$analyze_checkboxes['Statistics'][] = array('shm', 'Show Overall Harmonic Mean(s)');
			$analyze_checkboxes['Statistics'][] = array('sgm', 'Show Overall Geometric Mean');
			$analyze_checkboxes['Statistics'][] = array('nor', 'Normalize Results');
			$analyze_checkboxes['Graph Settings'][] = array('ftr', 'Force Line Graphs (Where Applicable)');
			$analyze_checkboxes['Graph Settings'][] = array('scalar', 'Convert To Scalar (Where Applicable)');
			$analyze_checkboxes['Helpers'][] = array('spr', 'Show Notable Results');

			if($has_identifier_with_color_brand)
			{
				$analyze_checkboxes['Graph Settings'][] = array('ncb', 'No Color Branding');
			}
			if($has_box_plot || $has_line_graph)
			{
				$analyze_checkboxes['Graph Settings'][] = array('nbp', 'No Box Plots');
			}
			$analyze_checkboxes['Graph Settings'][] = array('vb', 'Prefer Vertical Bar Graphs');
			$analyze_checkboxes['Statistics'][] = array('rol', 'Remove Outliers Before Calculating Averages');
			$analyze_checkboxes['Statistics'][] = array('gtb', 'Graph Values Of All Runs (Box Plot)');

			if($result_file->is_multi_way_comparison())
			{
				$analyze_checkboxes['Multi-Way Comparison'][] = array('cmw', 'Condense Comparison');
				$analyze_checkboxes['Multi-Way Comparison'][] = array('imw', 'Transpose Comparison');
			}

			$t = null;
			foreach($analyze_checkboxes as $title => $group)
			{
				if(empty($group))
				{
					continue;
				}
				$t .= '<h2>' . $title . '</h2>';
				foreach($group as $i => $key)
				{
					$t .= '<input type="checkbox" name="' . $key[0] . '" value="y"' . (self::check_request_for_var($request, $key[0]) ? ' checked="checked"' : null) . ' /> ' . $key[1] . ' ';
				}
			}

			$t .= '<br /><br />Highlight/Baseline Result: ' .  self::html_select_menu('hgv', 'hgv', null, array_merge(array(null), $result_file->get_system_identifiers()), false);
			$analyze_options .= $t . '<br /><input name="submit" value="Refresh Results" type="submit" /></form>';
		}
		else
		{
			$analyze_options = 'Add more than one test system to expose result analysis options.';
		}
		return $analyze_options;
	}
	public static function process_helper_html(&$request, &$result_file, &$extra_attributes)
	{
		// Result export?
		switch(isset($_REQUEST['export']) ? $_REQUEST['export'] : null)
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
			case 'txt':
				$result_txt = pts_result_file_output::result_file_to_text($result_file);
				header('Content-Description: File Transfer');
				header('Content-Type: text/plain');
				header('Content-Disposition: attachment; filename=' . $_GET['result']. '.txt');
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
		if(self::check_request_for_var($request, 'shm'))
		{
			foreach(pts_result_file_analyzer::generate_harmonic_mean_result($result_file) as $overview_harmonic)
			{
				$result_file->add_result($overview_harmonic);
			}
		}
		if(self::check_request_for_var($request, 'sgm'))
		{
			$overview_geometric = pts_result_file_analyzer::generate_geometric_mean_result($result_file);
			$result_file->add_result($overview_geometric);
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
		if(self::check_request_for_var($request, 'hgv'))
		{
			$extra_attributes['highlight_graph_values'] = explode(',', self::check_request_for_var($request, 'hgv'));
		}
		else if(self::check_request_for_var($request, 'hgv_base64'))
		{
			$extra_attributes['highlight_graph_values'] = explode(',', base64_decode(self::check_request_for_var($request, 'hgv_base64')));
var_dump($extra_attributes['highlight_graph_values']);
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
		if(self::check_request_for_var($request, 'rol'))
		{
			foreach($result_file->get_result_objects() as $i => &$result_object)
			{
				$result_object->recalculate_averages_without_outliers(1.5);
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
