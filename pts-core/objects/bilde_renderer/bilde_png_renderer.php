<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	bilde_png_renderer: The PNG rendering implementation for bilde_renderer. Bilde == Norwegian for Image.

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

class bilde_png_renderer extends bilde_renderer
{
	public function __construct($width, $height, $embed_identifiers = "")
	{
		$this->image = imagecreate($width, $height);

		imageinterlace($this->image, true);

		if(function_exists("imageantialias"))
		{
			imageantialias($this->image, true);
		}
	}
	public function render_image($output_file = null, $quality = 0)
	{
		return imagepng($this->image, $output_file, $quality);
	}
	public function destroy_image()
	{
		imagedestroy($this->image);
	}

	public function write_text_left($text_string, $font_type, $font_size, $font_color, $bound_x1, $bound_y1, $bound_x2, $bound_y2, $rotate_text = false)
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
			$rotation = 270;
		}
		imagettftext($this->image, $font_size, $rotation, $text_x, $text_y, $font_color, $font_type, $text_string);
	}
	public function write_text_right($text_string, $font_type, $font_size, $font_color, $bound_x1, $bound_y1, $bound_x2, $bound_y2, $rotate_text = false)
	{
		$text_dimensions = $this->text_string_dimensions($text_string, $font_type, $font_size);
		$text_width = $text_dimensions[0];
		$text_height = $text_dimensions[1];

		if($rotate_text == false)
		{
			$rotation = 0;
		}
		else
		{
			$rotation = 90;
		}

		$text_x = $bound_x2 - $text_width;
		$text_y = $bound_y1 + round($text_height / 2);

		imagettftext($this->image, $font_size, $rotation, $text_x, $text_y, $font_color, $font_type, $text_string);
	}
	public function write_text_center($text_string, $font_type, $font_size, $font_color, $bound_x1, $bound_y1, $bound_x2, $bound_y2, $rotate_text = false)
	{
		if($bound_x1 != $bound_x2)
		{
			while($this->text_string_width($this->trim_double($text_string, 2), $font_type, $font_size) > abs($bound_x2 - $bound_x1 - 3))
			{
				$font_size -= 0.5;
			}
		}

		$text_dimensions = $this->text_string_dimensions(strtoupper($text_string), $font_type, $font_size, $big_type);
		$text_height = $text_dimensions[1];

		$text_dimensions = $this->text_string_dimensions($text_string, $font_type, $font_size, $big_type);
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

		imagettftext($this->image, $font_size, $rotation, $text_x, $text_y, $font_color, $font_type, $text_string);
	}

	public function draw_rectangle($x1, $y1, $width, $height, $background_color)
	{
		imagefilledrectangle($this->image, $x1, $y1, $width, $height, $background_color);
	}
	public function draw_rectangle_border($x1, $y1, $width, $height, $border_color)
	{
		imagerectangle($this->image, $x1, $y1, $width, $height, $border_color);
	}
	public function draw_line($start_x, $start_y, $end_x, $end_y, $color, $line_width = 1)
	{
		for($i = 0; $i < $line_width; $i++)
		{
			imageline($this->image, $start_x, $start_y + $i, $end_x, $end_y + $i, $color);
		}
	}

	public function png_image_to_type($file)
	{
		return imagecreatefrompng($file);
	}
	public function jpg_image_to_type($file)
	{
		return imagecreatefromjpeg($file);
	}
	public function image_copy_merge($source_image_object, $to_x, $to_y, $source_x = 0, $source_y = 0, $width = -1, $height = -1)
	{
		if($width == -1)
		{
			$width = imagesx($source_image_object);
		}
		if($height == -1)
		{
			$height = imagesy($source_image_object);
		}

		imagecopy($this->image, $source_image_object, $to_x, $to_y, $source_x, $source_y, $width, $height);
	}
	public function convert_hex_to_type($hex)
	{
		return imagecolorallocate($this->image, hexdec(substr($hex, 1, 2)), hexdec(substr($hex, 3, 2)), hexdec(substr($hex, 5, 2)));
	}
	public function text_string_dimensions($string, $font_type, $font_size, $predefined_string = false)
	{
		$box_array = imagettfbbox($font_size, 0, $font_type, $string);
		$box_width = $box_array[4] - $box_array[6];

		if($predefined_string)
		{
			$box_array = imagettfbbox($size, 0, $font, "AZ@![]()@|_");
		}

		$box_height = $box_array[1] - $box_array[7];

		return array($box_width, $box_height);
	}
}

?>
