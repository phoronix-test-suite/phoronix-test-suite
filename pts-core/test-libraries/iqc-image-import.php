<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel

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
file_put_contents(getenv("IQC_IMAGE_PNG"), "111");

if(!extension_loaded("gd"))
{
	exit(1);
}
if(!isset($argv[5]))
{
	exit(1);
}
if(($iqc_image_png = getenv("IQC_IMAGE_PNG")) == false)
{
	exit(1);
}
if(is_file($iqc_image_png))
{
	unlink($iqc_image_png);
}

$img_original = $argv[1];
$start_x = $argv[2];
$start_y = $argv[3];
$destination_width = $argv[4];
$destination_height = $argv[5];

if(!is_file($img_original))
{
	exit(1);
}

switch(strtolower(array_pop(explode(".", $img_original))))
{
	case "tga":
		$img = imagecreatefromtga($img_original);
		break;
	case "png":
		$img = imagecreatefrompng($img_original);
		break;
	case "jpg":
	case "jpeg":
		$img = imagecreatefromjpeg($img_original);
		break;
	default:
		exit(1);
		break;
}


$img_sliced = imagecreatetruecolor($destination_width, $destination_height);
imagecopyresampled($img_sliced, $img, 0, 0, $start_x, $start_y, $destination_width, $destination_height, $destination_width, $destination_height);
imagepng($img_sliced, $iqc_image_png);

function imagecreatefromtga($tga_file)
{
	// There is no mainline PHP GD support for reading TGA at this time
	$handle = fopen($tga_file, "rb");
	$data = fread($handle, filesize($tga_file));
	fclose($handle);
   
	$pointer = 18;
	$pixel_x = 0;
	$pixel_y = 0;
	$img_width = base_convert(bin2hex(strrev(substr($data, 12, 2))), 16, 10);
	$img_height = base_convert(bin2hex(strrev(substr($data, 14, 2))), 16, 10);
	$img = imagecreatetruecolor($img_width, $img_height);

	while($pointer < strlen($data))
	{
		imagesetpixel($img, $pixel_x, ($img_height - $pixel_y), base_convert(bin2hex(strrev(substr($data, $pointer, 3))), 16, 10));
		$pixel_x++;

		if($pixel_x == $img_width)
		{
			$pixel_y++;
			$pixel_x = 0;
		}

		$pointer += 3;
	}
   
	return $img;
}

?>
