<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
	bilde_png_renderer: The PNG rendering implementation for bilde_renderer.

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

class bilde_png_renderer extends bilde_gd_renderer
{
	public $renderer = "PNG";

	public function render_image($output_file = null, $quality = 100)
	{
		$quality = floor(9 - (($quality / 100) * 9)); // calculate compression level
		if(defined("BILDE_IMAGE_INTERLACING"))
		{
			imageinterlace($this->image, BILDE_IMAGE_INTERLACING);
		}
		return imagepng($this->image, $output_file, $quality);
	}
}

?>
