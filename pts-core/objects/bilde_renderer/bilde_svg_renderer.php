<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
	bilde_svg_renderer: The SVG rendering implementation for bilde_renderer

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

class bilde_svg_renderer extends bilde_renderer
{
	var $renderer = "SVG";
	var $javascript_functions = null;
	static $svg_style_definitions = null;
	static $render_count = 0;

	public function __construct($width, $height, $embed_identifiers = null)
	{
		// TODO: In the future when tandem_XmlWriter is ready, use that for rendering all of the SVG XML
		$this->image_width = $width;
		$this->image_height = $height;
		$this->embed_identifiers = $embed_identifiers;

		if(self::$svg_style_definitions == null)
		{
			self::$svg_style_definitions = array();
		}
	}
	public static function renderer_supported()
	{
		return true;
	}
	public function html_embed_code($file_name, $attributes = null, $is_xsl = false)
	{
		$file_name = str_replace("BILDE_EXTENSION", strtolower($this->get_renderer()), $file_name);
		$attributes = pts_to_array($attributes);
		$attributes["data"] = $file_name;

		if($is_xsl)
		{
			$html = "<object type=\"image/svg+xml\">";

			foreach($attributes as $option => $value)
			{
				$html .= "<xsl:attribute name=\"" . $option . "\">" . $value . "</xsl:attribute>";
			}
			$html .= "</object>";
		}
		else
		{
			$html = "<object type=\"image/svg+xml\"";

			foreach($attributes as $option => $value)
			{
				$html .= $option . "=\"" . $value . "\" ";
			}
			$html .= "/>";
		}

		return $html;
	}
	public function resize_image($width, $height)
	{
		$this->image_width = $width;
		$this->image_height = $height;
	}
	public function render_image($output_file = null, $quality = 100)
	{
		// $quality is unused with SVG files
		$svg_image = $output_file != null ? "<?xml version=\"1.0\"?>\n<!DOCTYPE svg PUBLIC \"-//W3C//DTD SVG 1.1//EN\" \"http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd\">\n" : false;

		if($output_file != null && is_array($this->embed_identifiers))
		{
			foreach($this->embed_identifiers as $key => $value)
			{
				$svg_image .= "<!-- " . $key . ": " . $value . " -->\n";
			}
		}

		$svg_image .= "<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" version=\"1.1\" viewbox=\"0 0 " . $this->image_width . " " . $this->image_height . "\" width=\"" . $this->image_width . "\" height=\"" . $this->image_height . "\">\n";

		if(!defined("BILDE_SVG_COLLECT_DEFINITIONS"))
		{
			$svg_image .= $this->get_svg_formatted_definitions();
			self::$render_count++;
		}

		if($this->javascript_functions != null)
		{
			$svg_image .= "<script type=\"text/javascript\">\n<![CDATA[\n" . $this->javascript_functions . "\n// ]]>\n</script>";
		}

		$svg_image .= $this->image . "</svg>";

		return $output_file != null ? @file_put_contents($output_file, $svg_image) : $svg_image;
	}
	public function destroy_image()
	{
		$this->image = null;
	}

	public function write_text_left($text_string, $font_type, $font_size, $font_color, $bound_x1, $bound_y1, $bound_x2, $bound_y2, $rotate_text = false, $onclick = null)
	{
		$text_dimensions = $this->text_string_dimensions($text_string, $font_type, $font_size);
		$text_width = $text_dimensions[0];
		$text_height = $text_dimensions[1];

		if($rotate_text == false)
		{
			$text_x = $bound_x1;
			$text_y = $bound_y1 + round($text_height / 2);
			$rotation = 0;
		}
		else
		{
			$text_x = $bound_x1 - round($text_height / 4);
			$text_y = $bound_y1 + round($text_height / 2);
			$rotation = 90;
		}

		$this->write_svg_text($text_string, $font_type, $font_size, $font_color, $text_x, $text_y, $rotation, "LEFT", $onclick);
	}
	public function write_text_right($text_string, $font_type, $font_size, $font_color, $bound_x1, $bound_y1, $bound_x2, $bound_y2, $rotate_text = false, $onclick = null)
	{
		$text_dimensions = $this->text_string_dimensions($text_string, $font_type, $font_size);
		$text_width = $text_dimensions[0];
		$text_height = $text_dimensions[1];

		$bound_x1 -= 2;
		$bound_x2 -= 2;
		$rotation = ($rotate_text == false ? 0 : 90);

		$text_x = $bound_x2 - $text_width;
		$text_y = $bound_y1 + round($text_height / 2);

		$this->write_svg_text($text_string, $font_type, $font_size, $font_color, $text_x, $text_y, $rotation, "RIGHT", $onclick);
	}
	public function write_text_center($text_string, $font_type, $font_size, $font_color, $bound_x1, $bound_y1, $bound_x2, $bound_y2, $rotate_text = false, $onclick = null)
	{
		if($bound_x1 != $bound_x2)
		{
			$font_size += 1.5;
			list($text_width, $text_height) = bilde_renderer::soft_text_string_dimensions($this->trim_double($text_string, 2), $font_type, $font_size);

			while($text_width > abs($bound_x2 - $bound_x1 - 2))
			{
				$font_size -= 0.5;
				list($text_width, $text_height) = bilde_renderer::soft_text_string_dimensions($this->trim_double($text_string, 2), $font_type, $font_size);
			}
			$font_size -= 1.5;

			if($font_size < 4)
			{
				return;
			}
		}

		$text_dimensions = $this->text_string_dimensions(strtoupper($text_string), $font_type, $font_size);
		$text_height = $text_dimensions[1];

		$text_dimensions = $this->text_string_dimensions($text_string, $font_type, $font_size);
		$text_width = $text_dimensions[0];

		if($rotate_text == false)
		{
			$rotation = 0;
			$text_x = (($bound_x2 - $bound_x1) / 2) + $bound_x1 - round($text_width / 2);
			$text_y = $bound_y1 + $text_height;
		}
		else
		{
			$rotation = 90;
			$text_x = $bound_x1 + $text_height;
			$text_y = (($bound_y2 - $bound_y1) / 2) + $bound_y1 + round($text_width / 2);
		}

		$this->write_svg_text($text_string, $font_type, $font_size, $font_color, $text_x, $text_y, $rotation, "CENTER", $onclick);
	}
	public function draw_rectangle_with_border($x1, $y1, $width, $height, $background_color, $border_color, $title = null)
	{
		$width = $width - $x1;
		$height = $height - $y1;
		$x1 += $width < 0 ? $width : 0;
		$y1 += $height < 0 ? $height : 0;

		$class = $this->add_svg_style_definition("stroke: " . $border_color . "; " . "stroke-width: 1px;");
		$this->image .= "<rect x=\"" . round($x1) . "\" y=\"" . round($y1) . "\" class=\"" . $class . "\" width=\"" . abs(round($width)) . "\" height=\"" . abs(round($height)) . "\" fill=\"" . $background_color . "\"" . ($title != null ? " xlink:title=\"" . $title . "\"" : null) . " />\n";
	}
	public function draw_rectangle($x1, $y1, $width, $height, $background_color)
	{
		$width = $width - $x1;
		$height = $height - $y1;
		$x1 += $width < 0 ? $width : 0;
		$y1 += $height < 0 ? $height : 0;

		// could add a add_svg_style_definition here if it could be smart
		$class = $this->add_svg_style_definition("width: " . abs(round($width)) . "px; height: " . abs(round($height)) . "px; fill: " . $background_color . ";");
		$this->image .= "<rect x=\"" . round($x1) . "\" y=\"" . round($y1) . "\" class=\"" . $class . "\" />\n";
	}
	public function draw_rectangle_border($x1, $y1, $width, $height, $border_color)
	{
		$class = $this->add_svg_style_definition("stroke: " . $border_color . "; " . "stroke-width: 1px; fill: none;");

		$this->image .= "<rect x=\"" . round($x1) . "\" y=\"" . round($y1) . "\" width=\"" . round($width - $x1) . "\" height=\"" . round($height - $y1) . "\" class=\"" . $class . "\" />\n";
	}
	public function draw_polygon($points, $body_color, $border_color = null, $border_width = 0)
	{
		$point_pairs = array();
		$this_pair = array();

		foreach($points as $one_point)
		{
			array_push($this_pair, $one_point);

			if(count($this_pair) == 2)
			{
				$pair = implode(",", $this_pair);
				array_push($point_pairs, $pair);
				$this_pair = array();
			} 
		}

		$this->image .= "<polygon fill=\"" . $body_color . "\"" . ($border_color != null ? " stroke=\"" . $border_color . "\"" : null) . " stroke-width=\"" . $border_width . "\" points=\"" . implode(" ", $point_pairs) . "\" />\n";
	}
	public function draw_ellipse($center_x, $center_y, $width, $height, $body_color, $border_color = null, $border_width = 0, $default_hide = false, $title = null)
	{
		if($default_hide == true)
		{
			$this->image .= "<ellipse cx=\"" . $center_x . "\" cy=\"" . $center_y . "\" rx=\"" . floor($width / 2) . "\" ry=\"" . floor($height / 2) . "\" stroke-opacity=\"0\" fill-opacity=\"0\" stroke=\"" . $border_color . "\" fill=\"" . $body_color . "\" stroke-width=\"" . $border_width . "\"" . ($title != null ? " xlink:title=\"" . $title . "\"" : null) . "><set attributeName=\"stroke-opacity\" from=\"0\" to=\"1\" begin=\"mouseover\" end=\"mouseout\" /><set attributeName=\"fill-opacity\" from=\"0\" to=\"1\" begin=\"mouseover\" end=\"mouseout\" /></ellipse>\n";
		}
		else
		{
			$this->image .= "<ellipse cx=\"" . $center_x . "\" cy=\"" . $center_y . "\" rx=\"" . floor($width / 2) . "\" ry=\"" . floor($height / 2) . "\" stroke=\"" . $border_color . "\" fill=\"" . $body_color . "\" stroke-width=\"" . $border_width . "\"" . ($title != null ? " xlink:title=\"" . $title . "\"" : null) . " />\n";
		}
	}
	public function draw_line($start_x, $start_y, $end_x, $end_y, $color, $line_width = 1, $title = null)
	{
		$class = $this->add_svg_style_definition("stroke: " . $color . "; " . "stroke-width: " . $line_width . "px;");
		$this->image .= "<line x1=\"" . round($start_x) . "\" y1=\"" . round($start_y) . "\" x2=\"" . round($end_x) . "\" y2=\"" . round($end_y) . "\" class=\"" . $class . "\"" . ($title != null ? " xlink:title=\"" . $title . "\"" : null) . " />\n";
	}
	public function draw_dashed_line($start_x, $start_y, $end_x, $end_y, $color, $line_width, $dash_length, $blank_length)
	{
		$class = $this->add_svg_style_definition("stroke: " . $color . "; " . "stroke-width: " . $line_width . "px;");
		$this->image .= "<line stroke-dasharray=\"" . $dash_length . "," . $blank_length . "\" x1=\"" . round($start_x) . "\" y1=\"" . round($start_y) . "\" x2=\"" . round($end_x) . "\" y2=\"" . round($end_y) . "\" class=\"" . $class . "\" />\n";
	}
	public function draw_poly_line($x_y_pair_array, $color, $line_width = 1)
	{
		foreach($x_y_pair_array as &$x_y)
		{
			$x = round($x_y[0]);
			$y = round($x_y[1]);

			$x_y = $x . ',' . $y;
		}
		$poly_points = implode(' ', $x_y_pair_array);

		$class = $this->add_svg_style_definition("stroke: " . $color . "; " . "stroke-width: " . $line_width . "px; fill: none;");
		$this->image .= "<polyline points=\"" . $poly_points . "\" class=\"" . $class . "\" />\n";
	}
	public function png_image_to_type($file)
	{
		return false;
	}
	public function jpg_image_to_type($file)
	{
		return false;
	}
	public function image_copy_merge($source_image_object, $to_x, $to_y, $source_x = 0, $source_y = 0, $width = -1, $height = -1)
	{
		return null;
	}
	public function convert_hex_to_type($hex)
	{
		if(($short = substr($hex, 1, 3)) == substr($hex, 4, 3))
		{
			$hex = "#" . $short;
		}

		return $hex;
	}
	public function text_string_dimensions($string, $font_type, $font_size, $predefined_string = false)
	{
		return array(0, 0); // TODO: implement, though seems to do fine without it for the SVG renderer
	}

	// Privates

	private function write_svg_text($string, $font_type, $font_size, $font_color, $text_x, $text_y, $rotation, $orientation = "LEFT", $onclick = null)
	{
		$font_size += 1.5;

		switch($orientation)
		{
			case "CENTER":
				$class = $this->add_svg_style_definition("text-anchor: middle; dominant-baseline: text-before-edge; fill: $font_color;");
				break;
			case "RIGHT":
				$class = $this->add_svg_style_definition("text-anchor: end; dominant-baseline: middle; fill: $font_color;");
				break;
			case "LEFT":
			default:
				$class = $this->add_svg_style_definition("text-anchor: start; dominant-baseline: middle; fill: $font_color;");
				break;
		}

		if($onclick != null && substr($onclick, 0, 7) == "http://")
		{
			$this->image .= "<a xlink:href=\"" . str_replace("&", "&amp;", $onclick) . "\" target=\"new\">";
		}

		// Implement $font_type through font-family if desired
		$this->image .= "<text transform=\"translate(" . round($text_x) . " " . round($text_y) . ")" . ($rotation == 0 ? null : " rotate(" . $rotation . " 0 0)") . "\" font-size=\"" . $font_size . "\" class=\"" . $class . "\"" . ($onclick != null && substr($onclick, 0, 7) != "http://" ? " onclick=\"" . $onclick . "\"" : null) . ">" . $string . "</text>";

		if($onclick != null && substr($onclick, 0, 7) == "http://")
		{
			$this->image .= "</a>";
		}

		$this->image .= "\n";
	}
	private function add_svg_style_definition($attributes)
	{
		if(($key = array_search($attributes, self::$svg_style_definitions)) === false)
		{
			array_push(self::$svg_style_definitions, $attributes);
			$key = count(self::$svg_style_definitions) - 1;
		}

		return "b_" . self::$render_count . '_' . $key;
	}
	private function get_svg_formatted_definitions()
	{
		if(count(self::$svg_style_definitions) > 0)
		{
			$svg = "<defs>\n";
			$svg .= "<style><![CDATA[\n";

			foreach(self::$svg_style_definitions as $key => $attributes)
			{
				$svg .= ".b_" . self::$render_count . '_' . $key . " { " . $attributes . " }\n";
			}

			$svg .= "]]></style>\n";
			$svg .= "</defs>\n";

			self::$svg_style_definitions = array();

			return $svg;
		}
	}
	public static function get_html_svg_defs()
	{
		return "<svg>" . self::get_svg_formatted_definitions() . "</svg>";
	}
}

?>
