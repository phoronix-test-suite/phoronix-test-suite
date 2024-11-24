<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2018, Phoronix Media
	Copyright (C) 2018, Michael Larabel

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

class pts_test_profile_results_definition
{
	private $system_monitors;
	private $extra_data;
	private $image_parsers;
	private $result_parsers;

	public function __construct()
	{
		$this->system_monitors = array();
		$this->extra_data = array();
		$this->image_parsers = array();
		$this->result_parsers = array();
	}
	public function add_system_monitor_definition($s, $p, $r)
	{
		$this->system_monitors[] = new pts_test_profile_results_definition_system_monitor($s, $p, $r);
	}
	public function get_system_monitor_definitions()
	{
		return $this->system_monitors;
	}
	public function add_extra_data_definition($i)
	{
		$this->system_monitors[] = new pts_test_profile_results_definition_extra_data($i);
	}
	public function get_extra_data_definitions()
	{
		return $this->extra_data;
	}
	public function add_image_parser_definition($s, $m, $x, $y, $w, $h)
	{
		$this->image_parsers[] = new pts_test_profile_results_definition_image_parser($s, $m, $x, $y, $w, $h);
	}
	public function get_image_parser_definitions()
	{
		return $this->image_parsers;
	}
	public function add_result_parser_definition($ot, $mtta, $rk, $lh, $lbh, $lah, $rbs, $ras, $sfr, $srp, $mm, $drb, $mrb, $rs, $rpro, $rpre, $ad, $atad, $ff, $tcts, $dob, $doa, $df, $drd, $ri)
	{
		$this->result_parsers[] = new pts_test_profile_results_definition_result_parser($ot, $mtta, $rk, $lh, $lbh, $lah, $rbs, $ras, $sfr, $srp, $mm, $drb, $mrb, $rs, $rpro, $rpre, $ad, $atad, $ff, $tcts, $dob, $doa, $df, $drd, $ri);
	}
	public function get_result_parser_definitions()
	{
		return $this->result_parsers;
	}
}

class pts_test_profile_results_definition_system_monitor
{
	private $sensor;
	private $polling_frequency;
	private $report;

	public function __construct($s, $p, $r)
	{
		$this->sensor = $s;
		$this->polling_frequency = $p;
		$this->report = $r;
	}
	public function get_sensor()
	{
		return $this->sensor;
	}
	public function get_polling_frequency()
	{
		return $this->polling_frequency;
	}
	public function get_report()
	{
		return $this->report;
	}
}
class pts_test_profile_results_definition_extra_data
{
	private $identifier;
	public function __construct($i)
	{
		$this->identifier = $i;
	}
	public function get_identifier()
	{
		return $this->identifier;
	}
}
class pts_test_profile_results_definition_image_parser
{
	private $source_image;
	private $match_to_test_args;
	private $imagex;
	private $imagey;
	private $imagewidth;
	private $imageheight;

	public function __construct($s, $m, $x, $y, $w, $h)
	{
		$this->source_image = $s;
		$this->match_to_test_args = $m;
		$this->imagex = $x;
		$this->imagey = $y;
		$this->imagewidth = $w;
		$this->imageheight = $h;
	}
	public function get_source_image()
	{
		return $this->source_image;
	}
	public function get_match_to_image_args()
	{
		return $this->match_to_test_args;
	}
	public function get_image_x()
	{
		return $this->imagex;
	}
	public function get_image_y()
	{
		return $this->imagey;
	}
	public function get_image_width()
	{
		return $this->imagewidth;
	}
	public function get_image_height()
	{
		return $this->imageheight;
	}
}
class pts_test_profile_results_definition_result_parser
{
	private $output_template;
	private $match_to_test_args;
	private $result_key;
	private $line_hint;
	private $line_before_hint;
	private $line_after_hint;
	private $result_before_string;
	private $result_after_string;
	private $strip_from_result;
	private $strip_result_postfix;
	private $multi_match;
	private $divide_result_by;
	private $divide_result_divisor;
	private $multiply_result_by;
	private $result_scale;
	private $result_proportion;
	private $result_precision;
	private $arguments_description;
	private $append_to_arguments_description;
	private $file_format;
	private $turn_chars_to_space;
	private $delete_output_before;
	private $delete_output_after;
	private $display_format;
	private $result_importance;

	public function __construct($ot, $mtta, $rk, $lh, $lbh, $lah, $rbs, $ras, $sfr, $srp, $mm, $drb, $mrb, $rs, $rpro, $rpre, $ad, $atad, $ff, $tcts, $dob, $doa, $df, $drd, $ri)
	{
		$this->output_template = $ot;
		$this->match_to_test_args = $mtta;
		$this->result_key = $rk;
		$this->line_hint = $lh;
		$this->line_before_hint = $lbh;
		$this->line_after_hint = $lah;
		$this->result_before_string = $rbs;
		$this->result_after_string = $ras;
		$this->strip_from_result = $sfr;
		$this->strip_result_postfix = $srp;
		$this->multi_match = $mm;
		$this->divide_result_by = $drb;
		$this->divide_result_divisor = $drd;
		$this->multiply_result_by = $mrb;
		$this->result_scale = $rs;
		$this->result_proportion = $rpro;
		$this->result_precision = $rpre;
		$this->arguments_description = $ad;
		$this->append_to_arguments_description = $atad;
		$this->file_format = $ff;
		$this->turn_chars_to_space = $tcts;
		$this->delete_output_before = $dob;
		$this->delete_output_after = $doa;
		$this->display_format = $df;
		$this->result_importance = $ri;
	}
	public function get_output_template()
	{
		return $this->output_template;
	}
	public function get_match_to_test_args()
	{
		return $this->match_to_test_args;
	}
	public function get_result_key()
	{
		return $this->result_key;
	}
	public function get_line_hint()
	{
		return $this->line_hint;
	}
	public function get_line_before_hint()
	{
		return $this->line_before_hint;
	}
	public function get_line_after_hint()
	{
		return $this->line_after_hint;
	}
	public function get_result_before_string()
	{
		return $this->result_before_string;
	}
	public function get_result_after_string()
	{
		return $this->result_after_string;
	}
	public function get_strip_from_result()
	{
		return $this->strip_from_result;
	}
	public function get_strip_result_postfix()
	{
		return $this->strip_result_postfix;
	}
	public function get_multi_match()
	{
		return $this->multi_match;
	}
	public function get_divide_result_by()
	{
		return $this->divide_result_by;
	}
	public function get_divide_result_divisor()
	{
		return $this->divide_result_divisor;
	}
	public function get_multiply_result_by()
	{
		return $this->multiply_result_by;
	}
	public function get_result_scale()
	{
		return $this->result_scale;
	}
	public function get_result_proportion()
	{
		return $this->result_proportion;
	}
	public function get_result_precision()
	{
		return $this->result_precision;
	}
	public function get_arguments_description()
	{
		return $this->arguments_description;
	}
	public function get_append_to_arguments_description()
	{
		return $this->append_to_arguments_description;
	}
	public function get_file_format()
	{
		return $this->file_format;
	}
	public function get_turn_chars_to_space()
	{
		return $this->turn_chars_to_space;
	}
	public function get_delete_output_before()
	{
		return $this->delete_output_before;
	}
	public function get_delete_output_after()
	{
		return $this->delete_output_after;
	}
	public function get_result_importance()
	{
		return $this->result_importance;
	}
	public function get_display_format()
	{
		return $this->display_format;
	}
	public function set_display_format($f)
	{
		$this->display_format = $f;
	}
}
?>
