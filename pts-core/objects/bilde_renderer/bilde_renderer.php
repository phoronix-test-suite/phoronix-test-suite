<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2011, Phoronix Media
	Copyright (C) 2008 - 2011, Michael Larabel
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
	public $renderer = 'bilde_renderer';
	protected $image;
	protected $image_width = -1;
	protected $image_height = -1;
	protected $embed_identifiers = null;
	protected $uid_count = 1;
	protected $special_attributes = null;

	abstract function __construct($width, $height, $embed_identifiers = null); // create the object

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
	abstract function draw_ellipse($center_x, $center_y, $width, $height, $body_color, $border_color = null, $border_width = 0, $default_hide = false);
	abstract function draw_line($start_x, $start_y, $end_x, $end_y, $color, $line_width = 1, $title = null);
	abstract function draw_arc($center_x, $center_y, $radius, $offset_percent, $percent, $body_color, $border_color = null, $border_width = 1, $title = null);

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
		if($start_y == $end_y)
		{
			$pos = $start_y - ($line_width / 2);

			for($i = $start_x; $i < $end_x; $i += ($blank_length + $dash_length))
			{
				$this->draw_line($i, $pos, ($i + $dash_length), $pos, $color, $line_width);
			}
		}
		else
		{
			$pos = $end_x - ($line_width / 2);

			for($i = $start_y; $i < $end_y; $i += ($blank_length + $dash_length))
			{
				$this->draw_line($pos, $i, $pos, ($i + $dash_length), $color, $line_width);
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
		$is_vertical = ($tip_x1 == $tail_x1);

		if($is_vertical)
		{
			// Vertical arrow
			$arrow_length = sqrt(pow(($tail_x1 - $tip_x1), 2) + pow(($tail_y1 - $tip_y1), 2));
			$arrow_length_half = $arrow_length / 2;

			$arrow_points = array(
			$tip_x1, $tip_y1,
			$tail_x1 + $arrow_length_half, $tail_y1,
			$tail_x1 - $arrow_length_half, $tail_y1
			);
		}
		else
		{
			// Horizontal arrow
			$arrow_length = sqrt(pow(($tail_x1 - $tip_x1), 2) + pow(($tail_y1 - $tip_y1), 2));
			$arrow_length_half = $arrow_length / 2;

			$arrow_points = array(
			$tip_x1, $tip_y1,
			$tail_x1, $tail_y1 + $arrow_length_half,
			$tail_x1, $tail_y1 - $arrow_length_half
			);
		}

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
		$available_renderers = array('PNG', 'JPG', 'GIF', 'SWF', 'SVG', 'SVGZ');
		$selected_renderer = 'SVG';

		if(isset($_SERVER['HTTP_USER_AGENT']))
		{
			static $browser_renderer = null;

			if($browser_renderer == null || isset($_REQUEST['force_format']))
			{
				$browser_renderer = bilde_renderer_web::browser_compatibility_check($_SERVER['HTTP_USER_AGENT']);
			}

			$requested_renderer = $browser_renderer;
		}

		if((($this_renderer = getenv('BILDE_RENDERER')) != false || defined('BILDE_RENDERER') && ($this_renderer = BILDE_RENDERER) || ($this_renderer = $requested_renderer) != null) && in_array($this_renderer, $available_renderers))
		{
			$is_supported = call_user_func(array('bilde_' . strtolower($this_renderer) . '_renderer', 'renderer_supported'));

			if($is_supported)
			{
				$selected_renderer = $this_renderer;
			}
		}

		switch(strtolower($selected_renderer))
		{
			case 'svg':
				$renderer = new bilde_svg_renderer($width, $height, $embed_identifiers);
				break;
			case 'png':
				$renderer = new bilde_png_renderer($width, $height, $embed_identifiers);
				break;
			case 'jpg':
				$renderer = new bilde_jpg_renderer($width, $height, $embed_identifiers);
				break;
			case 'gif':
				$renderer = new bilde_gif_renderer($width, $height, $embed_identifiers);
				break;
			case 'swf':
				$renderer = new bilde_swf_renderer($width, $height, $embed_identifiers);
				break;
			case 'svgz':
				$renderer = new bilde_svgz_renderer($width, $height, $embed_identifiers);
				break;
		}

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
		$output_file = str_replace('BILDE_EXTENSION', strtolower($this->get_renderer()), $output_file);
		return $this->render_image($output_file, $quality);
	}

	//
	// Generic Functions
	//

	public static function setup_font_directory()
	{
		// Setup directory for TTF Fonts
		if(getenv('GDFONTPATH') == false)
		{
			if(defined('CUSTOM_FONT_DIR'))
			{
				putenv('GDFONTPATH=' . CUSTOM_FONT_DIR);
			}
			else if(defined('FONT_DIR'))
			{
				putenv('GDFONTPATH=' . FONT_DIR);
			}
			else if(($font_env = getenv('FONT_DIR')) != false)
			{
				putenv('GDFONTPATH=' . $font_env);
			}
			else
			{
				putenv('GDFONTPATH=' . getcwd());
			}
		}
	}
	public static function find_default_ttf_font($find_font = null)
	{
		if(!defined('BILDE_DEFAULT_FONT'))
		{
			if(is_readable($find_font))
			{
				$default_font = $find_font;
			}
			else if(ini_get('open_basedir'))
			{
				$default_font = false;
			}
			else
			{
				$default_font = false;
				$possible_fonts = array(
				'/usr/share/fonts/truetype/ttf-dejavu/DejaVuSans.ttf',
				'/usr/share/fonts/truetype/freefont/FreeSans.ttf',
				'/usr/share/fonts/truetype/ttf-bitstream-vera/Vera.ttf',
				'/usr/share/fonts/dejavu/DejaVuSans.ttf',
				'/usr/share/fonts/liberation/LiberationSans-Regular.ttf',
				'/usr/share/fonts/truetype/DejaVuSans.ttf',
				'/usr/share/fonts/truetype/LiberationSans-Regular.ttf',
				'/usr/share/fonts/TTF/dejavu/DejaVuSans.ttf',
				'/usr/share/fonts/TTF/liberation/LiberationSans-Regular.ttf',
				'/usr/X11/lib/X11/fonts/TrueType/arphic/uming.ttf',
				'/usr/local/lib/X11/fonts/bitstream-vera/Vera.ttf'
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

			define('BILDE_DEFAULT_FONT', $default_font);
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
			$file_extension = strtoupper(substr($file, strrpos($file, '.') + 1));

			switch($file_extension)
			{
				case 'PNG':
					$return_type = $this->png_image_to_type($file);
					break;
				case 'JPG':
				case 'JPEG':
					$return_type = $this->jpg_image_to_type($file);
					break;
			}
		}

		return $return_type;
	}
	public static function soft_text_string_dimensions($text_string, $font_type, $font_size, $predefined_string = false)
	{
		bilde_renderer::setup_font_directory();

		if(function_exists('imagettfbbox') && $font_type != false)
		{
			$box_array = imagettfbbox($font_size, 0, $font_type, $text_string);
			$box_width = $box_array[4] - $box_array[6];

			if($predefined_string)
			{
				$box_array = imagettfbbox($font_size, 0, $font_type, 'JAZ@![]()@|_qy');
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
	public static function color_gradient($color1, $color2, $color_weight)
	{
		$color1 = self::color_hex_to_rgb($color1);
		$color2 = self::color_hex_to_rgb($color2);

		$diff_r = $color2['r'] - $color1['r'];
		$diff_g = $color2['g'] - $color1['g'];
		$diff_b = $color2['b'] - $color1['b'];

		$r = ($color1['r'] + $diff_r * $color_weight);
		$g = ($color1['g'] + $diff_g * $color_weight);
		$b = ($color1['b'] + $diff_b * $color_weight);

		return self::color_rgb_to_hex($r, $g, $b);
	}
	public static function color_shade($color, $percent, $mask)
	{
		$color = self::color_hex_to_rgb($color);

		foreach($color as &$color_value)
		{
			$color_value = round($color_value * $percent) + round($mask * (1 - $percent));
			$color_value = $color_value > 255 ? 255 : $color_value;
		}

		return self::color_rgb_to_hex($color['r'], $color['g'], $color['b']);
	}
	public static function color_cache($ns, $id, &$colors)
	{
		//return array_shift($colors);
		static $cache = array();
		static $color_shift = 0;
		static $color_shift_size = 120;
		$i = count($cache);
		$color_shift_size = ($i == 0 ? 120 : 360 / $i); // can't be assigned directly to static var

		if(!isset($cache[$ns][$id]))
		{
			if(!isset($cache[$ns]))
			{
				$cache[$ns] = array();
			}

			do
			{
				if(empty($colors))
				{
					return false;
				}

				$hsl = self::color_rgb_to_hsl($colors[0]);
				$hsl = bilde_renderer::shift_hsl($hsl, $color_shift % 360);
				$color = bilde_renderer::color_hsl_to_hex($hsl);

				$color_shift += $color_shift_size;
				if($color_shift == ($color_shift_size * 3))
				{
					$color_shift_size *= 0.3;
					$colors[0] = self::color_shade($colors[0], 0.9, 1);
				}
				else if($color_shift > 630)
				{
					// We have already exhausted the cache pool once
					array_shift($colors);
					$color_shift = 0;
				}
			}
			while(in_array($color, $cache[$ns]));
			$cache[$ns][$id] = $color;
		}

		return $cache[$ns][$id];
	}
	public static function color_hex_to_rgb($hex)
	{
		$color = hexdec($hex);

		return array(
			'r' => ($color >> 16) & 0xff,
			'g' => ($color >> 8) & 0xff,
			'b' => $color & 0xff
			);
	}
	public static function color_hsl_to_hex($hsl)
	{
		if($hsl['s'] == 0)
		{
			$rgb['r'] = $hsl['l'] * 255;
			$rgb['g'] = $hsl['l'] * 255;
			$rgb['b'] = $hsl['l'] * 255;
		}
		else
		{
			$conv2 = $hsl['l'] < 0.5 ? $hsl['l'] * (1 + $hsl['s']) : ($hsl['l'] + $hsl['s']) - ($hsl['l'] * $hsl['s']);
			$conv1 = 2 * $hsl['l'] - $conv2;
			$rgb['r'] = round(255 * self::color_hue_convert($conv1, $conv2, $hsl['h'] + (1 / 3)));
			$rgb['g'] = round(255 * self::color_hue_convert($conv1, $conv2, $hsl['h']));
			$rgb['b'] = round(255 * self::color_hue_convert($conv1, $conv2, $hsl['h'] - (1 / 3)));
		}

		return self::color_rgb_to_hex($rgb['r'], $rgb['g'], $rgb['b']);
	}
	protected static function color_hue_convert($v1, $v2, $vh)
	{
		if($vh < 0)
		{
			$vh += 1;
		}

		if($vh > 1)
		{
			$vh -= 1;
		}

		if((6 * $vh) < 1)
		{
			return $v1 + ($v2 - $v1) * 6 * $vh;
		}

		if((2 * $vh) < 1)
		{
			return $v2;
		}

		if((3 * $vh) < 2)
		{
			return $v1 + ($v2 - $v1) * ((2 / 3 - $vh) * 6);
		}

		return $v1;
	}
	public static function color_rgb_to_hsl($hex)
	{
		$rgb = bilde_renderer::color_hex_to_rgb($hex);

		foreach($rgb as &$value)
		{
			$value = $value / 255;
		}

		$min = min($rgb);
		$max = max($rgb);
		$delta = $max - $min;

		$hsl['l'] = ($max + $min) / 2;

		if($delta == 0)
		{
			$hsl['h'] = 0;
			$hsl['s'] = 0;
		}
		else
		{
			$hsl['s'] = $delta / ($hsl['l'] < 0.5 ? $max + $min : 2 - $max - $min);

			$delta_rgb = array();
			foreach($rgb as $color => $value)
			{
				$delta_rgb[$color] = ((($max - $value) / 6) + ($max / 2)) / $delta;
			}

			switch($max)
			{
				case $rgb['r']:
					$hsl['h'] = $delta_rgb['b'] - $delta_rgb['g'];
					break;
				case $rgb['g']:
					$hsl['h'] = (1 / 3) + $delta_rgb['r'] - $delta_rgb['b'];
					break;
				case $rgb['b']:
				default:
					$hsl['h'] = (2 / 3) + $delta_rgb['g'] - $delta_rgb['r'];
					break;
			}

			$hsl['h'] += $hsl['h'] < 0 ? 1 : 0;
			$hsl['h'] -= $hsl['h'] > 1 ? 1 : 0;
		}

		return $hsl;
	}
	public static function shift_hsl($hsl, $rotate_h_degrees = 180)
	{
		if($rotate_h_degrees > 0)
		{
			$rotate_dec = $rotate_h_degrees / 360;
			$hsl['h'] = $hsl['h'] <= $rotate_dec ? $hsl['h'] + $rotate_dec : $hsl['h'] - $rotate_dec;
		}

		return $hsl;
	}
	public static function color_rgb_to_hex($r, $g, $b)
	{
		$color = ($r << 16) | ($g << 8) | $b;
		return '#' . sprintf('%06x', $color);
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
