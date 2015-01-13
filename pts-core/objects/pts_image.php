<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2015, Phoronix Media
	Copyright (C) 2010 - 2015, Michael Larabel

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

class pts_image
{
	public static function imagecreatefromtga($tga_file)
	{
		// There is no mainline PHP GD support for reading TGA at this time
		$handle = fopen($tga_file, 'rb');
		$data = fread($handle, filesize($tga_file));
		fclose($handle);
	   
		$pointer = 18;
		$pixel_x = 0;
		$pixel_y = 0;
		$img_width = base_convert(bin2hex(strrev(substr($data, 12, 2))), 16, 10);
		$img_height = base_convert(bin2hex(strrev(substr($data, 14, 2))), 16, 10);
		$pixel_depth = base_convert(bin2hex(strrev(substr($data, 16, 1))), 16, 10);
		$bytes_per_pixel = $pixel_depth / 8;
		$img = imagecreatetruecolor($img_width, $img_height);

		while($pointer < strlen($data))
		{
			// right now it's only reading 3 bytes per pixel, even for ETQW and others have a pixel_depth of 32-bit, rather than replacing 3 with $bytes_per_pixel
			// reading 32-bit TGAs from Enemy Territory: Quake Wars seems to actually work this way even though it's 32-bit
			// 24-bit should be good in all cases
			imagesetpixel($img, $pixel_x, ($img_height - $pixel_y), base_convert(bin2hex(strrev(substr($data, $pointer, 3))), 16, 10));
			$pixel_x++;

			if($pixel_x == $img_width)
			{
				$pixel_y++;
				$pixel_x = 0;
			}

			$pointer += $bytes_per_pixel;
		}
	   
		return $img;
	}
	public static function image_file_to_gd($img_file)
	{
		$img = false;

		switch(strtolower(pts_arrays::last_element(explode('.', $img_file))))
		{
			case 'tga':
				$img = pts_image::imagecreatefromtga($img_file);
				break;
			case 'png':
				$img = imagecreatefrompng($img_file);
				break;
			case 'jpg':
			case 'jpeg':
				$img = imagecreatefromjpeg($img_file);
				break;
		}

		return $img;
	}
	public static function rgb_gd_color_at(&$img, $x, $y)
	{
		$rgb = imagecolorat($img, $x, $y);

		// R, G, B
		return array(($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF);
	}
	public static function rgb_int_diff($rgb1, $rgb2)
	{
		return abs(array_sum($rgb1) - array_sum($rgb2));
	}
	public static function gd_image_delta_composite(&$base_img, &$compare_img, $only_return_changes = true)
	{
		$composite = array();

		for($x = 0; $x < imagesx($base_img); $x++)
		{
			for($y = 0; $y < imagesy($base_img); $y++)
			{
				$base_rgb = imagecolorat($base_img, $x, $y);
				$compare_rgb = imagecolorat($compare_img, $x, $y);
				$diff_rgb = abs($base_rgb - $compare_rgb);

				if($only_return_changes == false || $diff_rgb != 0)
				{
					$composite[$x][$y] = $diff_rgb;
				}
			}
		}

		return $composite;
	}
	public static function color_pixel_delta(&$base_img, &$compare_img, &$x, &$y)
	{
		$color = false; // for now we set to true when it should change, but ultimately should return a color

		$check_points = array(
			array($x + 1, $y),
			array($x - 1, $y),
			array($x, $y + 1),
			array($x, $y - 1)
			);

		foreach($check_points as $point_r)
		{
			if(pts_image::rgb_int_diff(pts_image::rgb_gd_color_at($base_img, $point_r[0], $point_r[1]), pts_image::rgb_gd_color_at($compare_img, $point_r[0], $point_r[1])) > 12)
			{
				$color = array($point_r[0], $point_r[1]);
				break;
			}
		}

		return $color;
	}
	public static function compress_png_image($png_file, $compression_level = 8)
	{
		if(function_exists('imagecreatefrompng'))
		{
			$img = imagecreatefrompng($png_file);

			if($img)
			{
				imagepng($img, $png_file, $compression_level);
				imagedestroy($img);
				return true;
			}
		}

		return false;
	}
}

?>
