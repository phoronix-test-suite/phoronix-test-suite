<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
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
	// TODO: rework the entire bilde_renderer drawing API by PTS3, see michaellarabel for details. Right now it's getting a messy with the additions
	var $renderer = "bilde_renderer";
	protected $image;
	protected $image_width = -1;
	protected $image_height = -1;
	protected $embed_identifiers = null;
	protected $uid_count = 1;
	protected $special_attributes = null;

	abstract function __construct($width, $height, $embed_identifiers = ""); // create the object

	abstract function html_embed_code($file_name, $attributes = null, $is_xsl = false);
	abstract function render_image($output_file = null, $quality = 100);
	abstract function resize_image($width, $height);
	abstract function destroy_image();

	abstract function write_text_left($text_string, $font_type, $font_size, $font_color, $bound_x1, $bound_y1, $bound_x2, $bound_y2, $rotate_text = false, $onclick = null);
	abstract function write_text_right($text_string, $font_type, $font_size, $font_color, $bound_x1, $bound_y1, $bound_x2, $bound_y2, $rotate_text = false, $onclick = null);
	abstract function write_text_center($text_string, $font_type, $font_size, $font_color, $bound_x1, $bound_y1, $bound_x2, $bound_y2, $rotate_text = false, $onclick = null);

	abstract function draw_rectangle($x1, $y1, $width, $height, $background_color);
	abstract function draw_rectangle_border($x1, $y1, $width, $height, $border_color);
	abstract function draw_polygon($points, $body_color, $border_color = null, $border_width = 0);
	abstract function draw_ellipse($center_x, $center_y, $width, $height, $body_color, $border_color = null, $border_width = 0, $default_hide = false);
	abstract function draw_line($start_x, $start_y, $end_x, $end_y, $color, $line_width = 1, $title = null);

	abstract function png_image_to_type($file);
	abstract function jpg_image_to_type($file);
	abstract function image_copy_merge($source_image_object, $to_x, $to_y, $source_x = 0, $source_y = 0, $width = -1, $height = -1);
	abstract function convert_hex_to_type($hex);
	abstract function convert_type_to_hex($type);
	abstract function text_string_dimensions($string, $font_type, $font_size, $predefined_string = false);

	//
	// Meta functions that could be implemented within renderer-specifc code if available
	//

	public function draw_dashed_line($start_x, $start_y, $end_x, $end_y, $color, $line_width, $dash_length, $blank_length)
	{
		// TODO: below code can be cleaned up
		$dash_length--;
		if($start_y == $end_y)
		{
			$blank_length++;
			$current_x = $start_x;
			$next_x = $current_x + $dash_length;

			while($next_x < $end_x)
			{
				$this->draw_line($current_x, $start_y - ($line_width / 2), $current_x, $end_y + ($line_width / 2), $color, $dash_length + 1);

				$current_x = $current_x + $blank_length;
				$next_x = $current_x + $dash_length;
			}
		}
		else if($start_x == $end_x)
		{
			$current_y = $start_y;
			$next_y = $current_y + $dash_length;

			while($next_y < $end_y)
			{
				$this->draw_line($start_x - ($line_width / 2), $current_y, $end_x - ($line_width / 2), $next_y, $color, $line_width);

				$current_y = $current_y + $blank_length;
				$next_y = $current_y + $dash_length;
			}
		}
	}
	public function draw_poly_line($x_y_pair_array, $color, $line_width = 1)
	{
		$prev_pair = array_shift($x_y_pair_array);

		foreach($x_y_pair_array as $x_y)
		{
			$this->draw_line($prev_pair[0], $prev_pair[1], $x_y[0], $x_y[1], $color, $line_width);
			$prev_pair = $x_y;
		}
	}
	public function request_uid()
	{
		return 'i_' . $this->uid_count++;
	}
	public function draw_rectangle_with_border($x1, $y1, $width, $height, $background_color, $border_color, $title = null)
	{
		$this->draw_rectangle($x1, $y1, $width, $height, $background_color);
		$this->draw_rectangle_border($x1, $y1, $width, $height, $border_color);
	}
	public function draw_arrow($tip_x1, $tip_y1, $tail_x1, $tail_y1, $background_color, $border_color = null, $border_width = 0)
	{
		// TODO: Allow better support when arrow is running horizontally or on an angle instead of just vertical
		$arrow_length = sqrt(pow(($tail_x1 - $tip_x1), 2) + pow(($tail_y1 - $tip_y1), 2));
		$arrow_length_half = $arrow_length / 2;

		$arrow_points = array(
		$tip_x1, $tip_y1,
		$tail_x1 + $arrow_length_half, $tail_y1,
		$tail_x1 - $arrow_length_half, $tail_y1
		);

		$this->draw_polygon($arrow_points, $background_color, $border_color, $border_width);
	}

	public static function renderer_supported()
	{
		// This should be implemented by the different bilde renderers if the renderer is dependent upon some extensions or something else for the support
		return true;
	}

	//
	// Setup Functions
	//

	public static function setup_renderer($requested_renderer, $width, $height, $embed_identifiers = null, $special_attributes = null)
	{
		bilde_renderer::setup_font_directory();
		$available_renderers = array("PNG", "JPG", "GIF", "SWF", "SVG");
		$fallback_renderer = "SVG";
		$selected_renderer = $fallback_renderer;
		$use_renderer = false;

		if(($this_renderer = getenv("BILDE_RENDERER")) != false || defined("BILDE_RENDERER") && ($this_renderer = BILDE_RENDERER) || ($this_renderer = $requested_renderer) != null)
		{
			$is_supported = call_user_func(array("bilde_" . strtolower($this_renderer) . "_renderer", "renderer_supported"));

			if($is_supported)
			{
				$use_renderer = $this_renderer;
			}
		}

		if($use_renderer == false)
		{
			foreach($available_renderers as $this_renderer)
			{
				$is_supported = call_user_func(array("bilde_" . strtolower($this_renderer) . "_renderer", "renderer_supported"));

				if($is_supported)
				{
					$selected_renderer = $this_renderer;
					break;
				}
			}
		}
		else
		{
			$selected_renderer = $use_renderer;
		}

		eval("\$renderer = new bilde_" . strtolower($selected_renderer) . "_renderer(\$width, \$height, \$embed_identifiers);");

		if($special_attributes != null)
		{
			$renderer->set_special_attributes($special_attributes);
		}

		return $renderer;
	}
	public function set_special_attributes($attributes)
	{
		$this->special_attributes = $attributes;
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
				"/usr/share/fonts/TTF/liberation/LiberationSans-Regular.ttf",
				"/usr/X11/lib/X11/fonts/TrueType/arphic/uming.ttf",
				"/usr/local/lib/X11/fonts/bitstream-vera/Vera.ttf"
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
				$box_array = imagettfbbox($font_size, 0, $font_type, "JAZ@![]()@|_qy");
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
}

?>
