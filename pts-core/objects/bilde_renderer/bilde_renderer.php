<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
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
	var $renderer = "bilde_renderer";
	protected $image;
	protected $image_width = -1;
	protected $image_height = -1;
	protected $embed_identifiers = null;

	abstract function __construct($width, $height, $embed_identifiers = ""); // create the object

	abstract function html_embed_code($file_name, $attributes = null, $is_xsl = false);
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
	// Meta functions that could be implemented within renderer-specifc code if available
	//

	public function draw_rectangle_with_border($x1, $y1, $width, $height, $background_color, $border_color)
	{
		$this->draw_rectangle($x1, $y1, $width, $height, $background_color);
		$this->draw_rectangle_border($x1, $y1, $width, $height, $border_color);
	}
	public function draw_arrow($tip_x1, $tip_y1, $tail_x1, $tail_y1, $background_color)
	{
		// TODO: Allow better support when arrow is running horizontally or on an angle instead of just vertical
		$arrow_length = sqrt(pow(($tail_x1 - $tip_x1), 2) + pow(($tail_y1 - $tip_y1), 2));
		$arrow_length_half = $arrow_length / 2;

		$arrow_points = array(
		$tip_x1, $tip_y1,
		$tail_x1 + $arrow_length_half, $tail_y1,
		$tail_x1 - $arrow_length_half, $tail_y1
		);

		$this->draw_polygon($arrow_points, $background_color);
	}

	public static function renderer_supported()
	{
		// This should be implemented by the different bilde renderers if the renderer is dependent upon some extensions or something else for the support
		return true;
	}

	//
	// Setup Functions
	//

	public static function setup_renderer($requested_renderer, $width, $height, $embed_identifiers = "")
	{
		bilde_renderer::setup_font_directory();
		$available_renderers = array("PNG", "JPG", "GIF", "SWF", "SVG");
		$fallback_renderer = "SVG";
		$selected_renderer = $fallback_renderer;

		if(($this_renderer = getenv("BILDE_RENDERER")) != false || defined("BILDE_RENDERER") && ($this_renderer = BILDE_RENDERER))
		{
			eval("\$is_supported = bilde_" . strtolower($this_renderer) . "_renderer::renderer_supported();");

			if($is_supported)
			{
				eval("\$renderer = new bilde_" . strtolower($this_renderer) . "_renderer(\$width, \$height, \$embed_identifiers);");
				return $renderer;
			}
		}

		foreach($available_renderers as $this_renderer)
		{
			eval("\$is_supported = bilde_" . strtolower($this_renderer) . "_renderer::renderer_supported();");

			if($is_supported)
			{
				$selected_renderer = $this_renderer;
				break;
			}
		}

		eval("\$renderer = new bilde_" . strtolower($selected_renderer) . "_renderer(\$width, \$height, \$embed_identifiers);");
		return $renderer;
	}
	public function render_to_file($output_file = null, $quality = 100)
	{
		$output_file = str_replace("BILDE_EXTENSION", strtolower($this->get_renderer()), $output_file);
		return $this->render_image($output_file, $quality);
	}

	//
	// Generic Functions
	//

	public static function setup_font_directory()
	{
		// Setup directory for TTF Fonts
		if(getenv("GDFONTPATH") == false)
		{
			if(defined("CUSTOM_FONT_DIR"))
			{
				putenv("GDFONTPATH=" . CUSTOM_FONT_DIR);
			}
			else if(defined("FONT_DIR"))
			{
				putenv("GDFONTPATH=" . FONT_DIR);
			}
			else if(($font_env = getenv("FONT_DIR")) != false)
			{
				putenv("GDFONTPATH=" . $font_env);
			}
			else
			{
				putenv("GDFONTPATH=" . getcwd());
			}
		}
	}
	public static function find_default_ttf_font($find_font = null)
	{
		if(!defined("BILDE_DEFAULT_FONT"))
		{
			if(is_readable($find_font))
			{
				$default_font = $find_font;
			}
			else if(ini_get("open_basedir"))
			{
				$default_font = false;
			}
			else
			{
				$default_font = false;
				$possible_fonts = array(
				"/usr/share/fonts/truetype/ttf-dejavu/DejaVuSans.ttf",
				"/usr/share/fonts/truetype/freefont/FreeSans.ttf",
				"/usr/share/fonts/truetype/ttf-bitstream-vera/Vera.ttf",
				"/usr/share/fonts/dejavu/DejaVuSans.ttf",
				"/usr/share/fonts/liberation/LiberationSans-Regular.ttf",
				"/usr/share/fonts/truetype/DejaVuSans.ttf",
				"/usr/share/fonts/truetype/LiberationSans-Regular.ttf",
				"/usr/share/fonts/TTF/dejavu/DejaVuSans.ttf",
				"/usr/share/fonts/TTF/liberation/LiberationSans-Regular.ttf"
				);

				foreach($possible_fonts as $font_file)
				{
					if(is_readable($font_file))
					{
						$default_font = $font_file;
						break;
					}
				}
			}

			define("BILDE_DEFAULT_FONT", $default_font);
		}

		return BILDE_DEFAULT_FONT;
	}
	public function get_renderer()
	{
		return $this->renderer;
	}
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
	public static function soft_text_string_dimensions($text_string, $font_type, $font_size, $predefined_string = false)
	{
		bilde_renderer::setup_font_directory();

		if(function_exists("imagettfbbox") && $font_type != false)
		{
			$box_array = imagettfbbox($font_size, 0, $font_type, $text_string);
			$box_width = $box_array[4] - $box_array[6];

			if($predefined_string)
			{
				$box_array = imagettfbbox($font_size, 0, $font_type, "AZ@![]()@|_qy");
			}

			$box_height = $box_array[1] - $box_array[7];
		}
		else
		{
			// Basic calculation
			$box_height = 0.75 * $font_size;
			$box_width = 0.8 * strlen($text_string) * $font_size; // 0.8 now but should be about 1.18
		}

		// Width x Height
		return array($box_width, $box_height);
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
