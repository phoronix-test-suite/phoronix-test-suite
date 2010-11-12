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

class bilde_svgz_renderer extends bilde_svg_renderer
{
	public $renderer = "SVGZ";

	public static function renderer_supported()
	{
		return function_exists("gzcompress");
	}
	public function render_image($output_file = null, $quality = 100)
	{
		$svg_image = parent::render_image(null);

		return $output_file != null ? ($gz = gzopen($output_file, 'w9')) && gzwrite($gz, $svg_image) && gzclose($gz) : gzcompress($svg_image, 9);
	}
}

?>
