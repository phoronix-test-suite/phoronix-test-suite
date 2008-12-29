<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	bilde_renderer.php: The Phoronix Multi-Format "Bilde" Image Renderer

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

abstract class bilde_renderer
{
	var $image;
	var $image_width = -1;
	var $image_height = -1;
	var $embed_identifiers = null;

	public static $file_extension = "";

	abstract function __construct($width, $height, $embed_identifiers = ""); // create the object
	abstract function render_image($output_file = null, $quality = 100);
	abstract function resize_image($width, $height);
	abstract function destroy_image();

	abstract function write_text_left($text_string, $font_type, $font_size, $font_color, $bound_x1, $bound_y1, $bound_x2, $bound_y2, $rotate_text = false);
	abstract function write_text_right($text_string, $font_type, $font_size, $font_color, $bound_x1, $bound_y1, $bound_x2, $bound_y2, $rotate_text = false);
	abstract function write_text_center($text_string, $font_type, $font_size, $font_color, $bound_x1, $bound_y1, $bound_x2, $bound_y2, $rotate_text = false);

	abstract function draw_rectangle($x1, $y1, $width, $height, $background_color);
	abstract function draw_rectangle_border($x1, $y1, $width, $height, $border_color);
	abstract function draw_polygon($points, $body_color, $border_color = null, $border_width = 0);
	abstract function draw_ellipse($center_x, $center_y, $width, $height, $body_color, $border_color = null, $border_width = 0);
	abstract function draw_line($start_x, $start_y, $end_x, $end_y, $color, $line_width = 1);

	abstract function png_image_to_type($file);
	abstract function jpg_image_to_type($file);
	abstract function image_copy_merge($source_image_object, $to_x, $to_y, $source_x = 0, $source_y = 0, $width = -1, $height = -1);
	abstract function convert_hex_to_type($hex);
	abstract function text_string_dimensions($string, $font_type, $font_size, $predefined_string = false);

	//
	// Generic Functions
	//

	public function get_image_width()
	{
		return $this->image_width;
	}
	public function get_image_height()
	{
		return $this->image_height;
	}
	public function image_file_to_type($file)
	{
		$return_type = null;

		if(is_readable($file))
		{
			$file_extension = strtoupper(substr($file, strrpos($file, ".") + 1));

			switch($file_extension)
			{
				case "PNG":
					$return_type = $this->png_image_to_type($file);
					break;
				case "JPG":
				case "JPEG":
					$return_type = $this->jpg_image_to_type($file);
					break;
			}
		}

		return $return_type;
	}
	protected function soft_text_string_dimensions($text_string, $font_type, $font_size, $predefined_string = false)
	{
		// TODO: Needs To Be Implemented
		return array(0, 0);
	}
	protected function text_string_width($text_string, $font_type, $font_size)
	{
		$dimensions = $this->text_string_dimensions($text_string, $font_type, $font_size);
		return $dimensions[0];
	}
	protected function text_string_height($text_string, $font_type, $font_size)
	{
		$dimensions = $this->text_string_dimensions($text_string, $font_type, $font_size);
		return $dimensions[1];
	}
	protected function trim_double($double, $accuracy = 2)
	{
		// Set precision for a variable's points after the decimal spot
		$return = explode(".", $double);

		if(count($return) == 1)
		{
			$return[1] = "00";
		}
	
		if(count($return) == 2 && $accuracy > 0)
		{
			$strlen = strlen($return[1]);

			if($strlen > $accuracy)
			{
				$return[1] = substr($return[1], 0, $accuracy);
			}
			else if($strlen < $accuracy)
			{
				for($i = $strlen; $i < $accuracy; $i++)
				{
					$return[1] .= '0';
				}
			}

			$return = $return[0] . "." . $return[1];
		}
		else
		{
			$return = $return[0];
		}

		return $return;
	}
}

?>
