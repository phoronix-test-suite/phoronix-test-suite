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
	public static function get_html_options_markup(&$result_file, &$request)
	{
		$analyze_options = null;
		if(count($result_file->get_system_identifiers()) > 1)
		{
			$analyze_options = '<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">';
			$analyze_checkboxes = array(
				//array('obr_sas', 'Show Aggregate Sum'),
				array('shm', 'Show Harmonic Mean'),
				array('sgm', 'Show Geometric Mean'),
				array('sor', 'Sort Results By Performance'),
				array('sro', 'Sort Results By Identifier'),
				array('rro', 'Reverse Result Order'),
				array('nor', 'Normalize Results'),
				array('ftr', 'Force Line Graphs (Where Appropriate)'),
				array('scalar', 'Convert To Scalar (Where Appropriate)'),
				array('ncb', 'No Color Branding'),
				array('nbp', 'No Box Plots'),
				array('vb', 'Prefer Vertical Bar Graphs'),
				);

			if($result_file->is_multi_way_comparison())
			{
				array_push($analyze_checkboxes, array('cmw', 'Condense Comparison'));
				array_push($analyze_checkboxes, array('imw', 'Transpose Comparison'));
			}

			$t = null;
			foreach($analyze_checkboxes as $i => $key)
			{
				$t .= '<input type="checkbox" name="' . $key[0] . '" value="y"' . (self::check_request_for_var($request, $key[0]) ? ' checked="checked"' : null) . ' /> ' . $key[1] . ' ';
			}

			$t .= '<br /><br /><br />Highlight/Baseline Result: ' .  self::html_select_menu('hgv', 'hgv', null, array_merge(array(null), $result_file->get_system_identifiers()), false);
			$analyze_options .= $t . '<br /><input name="submit" value="Refresh Results" type="submit" /></form>';
		}
		else
		{
			$analyze_options = 'Add more than one test system to expose result analysis options.';
		}
		return $analyze_options;
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
