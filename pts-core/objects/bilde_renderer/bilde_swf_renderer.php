<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
	bilde_swf_renderer: The SWF (Flash) rendering implementation for bilde_renderer.

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

class bilde_swf_renderer extends bilde_renderer
{
	private $swf_font = null;
	public $renderer = "SWF";

	public function __construct($width, $height, $embed_identifiers = "")
	{
		$this->image = new SWFMovie();
		$this->image_width = $width;
		$this->image_height = $height;
		$this->image->setDimension($width, $height);

		$this->swf_font = new SWFFont("_sans"); // TODO: Implement better font support
	}
	public static function renderer_supported()
	{
		return extension_loaded("ming");
	}
	public function html_embed_code($file_name, $attributes = null, $is_xsl = false)
	{
		$file_name = str_replace("BILDE_EXTENSION", strtolower($this->get_renderer()), $file_name);
		$attributes = pts_arrays::to_array($attributes);
		$attributes["value"] = $file_name;
		$attributes["src"] = $file_name;

		if($is_xsl)
		{
			$html = "<object classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\" codebase=\"http://active.macromedia.com/flash2/cabs/swflash.cab#version=4,0,0,0\" id=\"objects\"><param name=\"movie\">";

			foreach($attributes as $option => $value)
			{
				$html .= "<xsl:attribute name=\"" . $option . "\">" . $value . "</xsl:attribute>";
			}

			$html .= "</param><embed type=\"application/x-shockwave-flash\" pluginspage=\"http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash\">";

			foreach($attributes as $option => $value)
			{
				$html .= "<xsl:attribute name=\"" . $option . "\">" . $value . "</xsl:attribute>";
			}

			$html .= "</embed></object>";
		}
		else
		{
			$html = "<object classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\" codebase=\"http://active.macromedia.com/flash2/cabs/swflash.cab#version=4,0,0,0\" id=\"objects\"><param name=\"movie\" ";

			foreach($attributes as $option => $value)
			{
				$html .= $option . "=\"" . $value . "\" ";
			}

			$html .= "></param><embed type=\"application/x-shockwave-flash\" pluginspage=\"http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash\" ";

			foreach($attributes as $option => $value)
			{
				$html .= $option . "=\"" . $value . "\" ";
			}

			$html .= "></embed></object>";
		}

		return $html;
	}
	public function render_image($output_file = null, $quality = 100)
	{
		return $this->image->save($output_file);
	}
	public function resize_image($width, $height)
	{
		$this->image_width = $width;
		$this->image_height = $height;
		$this->image->setDimension($width, $height);
	}
	public function destroy_image()
	{
		$this->image = null;
	}

	public function write_text_left($text_string, $font_type, $font_size, $font_color, $bound_x1, $bound_y1, $bound_x2, $bound_y2, $rotate_text = false)
	{
		$this->write_swf_text($text_string, $font_type, $font_size, $font_color, $bound_x1, $bound_y1, $bound_x2, $bound_y2, $rotate_text, "LEFT");
	}
	public function draw_arc($center_x, $center_y, $radius, $offset_percent, $percent, $body_color, $border_color = null, $border_width = 1, $title = null)
	{
		return false; // TODO: implement
	}
	public function write_text_right($text_string, $font_type, $font_size, $font_color, $bound_x1, $bound_y1, $bound_x2, $bound_y2, $rotate_text = false)
	{
		$this->write_swf_text($text_string, $font_type, $font_size, $font_color, $bound_x1, $bound_y1, $bound_x2, $bound_y2, $rotate_text, "RIGHT");
	}
	public function write_text_center($text_string, $font_type, $font_size, $font_color, $bound_x1, $bound_y1, $bound_x2, $bound_y2, $rotate_text = false)
	{
		$this->write_swf_text($text_string, $font_type, $font_size, $font_color, $bound_x1, $bound_y1, $bound_x2, $bound_y2, $rotate_text, "CENTER");
	}

	public function draw_rectangle($x1, $y1, $width, $height, $background_color)
	{
		$points = array(
		$x1, $y1,
		$width, $y1,
		$width, $height,
		$x1, $height
		);
		$this->draw_polygon($points, $background_color, $background_color, 1);
	}
	public function draw_rectangle_border($x1, $y1, $width, $height, $border_color)
	{
		$points = array(
		$x1, $y1,
		$width, $y1,
		$width, $height,
		$x1, $height
		);
		$this->draw_polygon($points, null, $border_color, 1);
	}
	public function draw_polygon($points, $body_color, $border_color = null, $border_width = 0)
	{
		$poly = new SWFShape();

		if(!empty($body_color))
		{
			$poly->setLeftFill($body_color[0], $body_color[1], $body_color[2]);
		}
		if(!empty($border_color) && $border_width > 0)
		{
			$poly->setLine($border_width, $border_color[0], $border_color[1], $border_color[2]);
		}


		$point_pairs = array();
		$this_pair = array();

		foreach($points as $one_point)
		{
			array_push($this_pair, $one_point);

			if(count($this_pair) == 2)
			{
				array_push($point_pairs, $this_pair);
				$this_pair = array();
			} 
		}

		if(count($point_pairs) > 1)
		{
			$poly->movePenTo($point_pairs[0][0], $point_pairs[0][1]);

			for($i = 1; $i < count($point_pairs); $i++)
			{
				$poly->drawLineTo($point_pairs[$i][0], $point_pairs[$i][1]);
			}
			$poly->drawLineTo($point_pairs[0][0], $point_pairs[0][1]);
		}

		$this->image->add($poly);
	}
	public function draw_ellipse($center_x, $center_y, $width, $height, $body_color, $border_color = null, $border_width = 0, $default_hide = false)
	{
		if($default_hide == true)
		{
			return false;
		}

		if($width > $height)
		{
			$base_size = $width;
		}
		else
		{
			$base_size = $height;
		}

		$ellipse = new SWFShape();
		$ellipse->setLine($border_width, $border_color[0], $border_color[1], $border_color[2]);
		$ellipse->setRightFill($body_color[0], $body_color[1], $body_color[2]);
		$ellipse->drawCircle($base_size / 2);
		$added = $this->image->add($ellipse);

		$added->moveTo($center_x, $center_y);
		$added->scaleTo(($width / $base_size), ($height / $base_size));
	}
	public function draw_line($start_x, $start_y, $end_x, $end_y, $color, $line_width = 1, $title = null)
	{
		$line = new SWFShape();
		$line->setLine(1, $color[0], $color[1], $color[2]);
		$line->movePenTo($start_x, $start_y);
		$line->drawLineTo($end_x, $end_y);
		$added = $this->image->add($line);
	}

	public function png_image_to_type($file)
	{
		return new SWFBitmap(fopen($file, "rb"));
	}
	public function jpg_image_to_type($file)
	{
		return new SWFBitmap(fopen($file, "rb"));
	}
	public function image_copy_merge($source_image_object, $to_x, $to_y, $source_x = 0, $source_y = 0, $width = -1, $height = -1)
	{
		// TODO: $source_x, $source_y, $width, $height need to be implemented
		$added = $this->image->add($source_image_object);
		$added->moveTo($to_x, $to_y);
	}
	public function convert_hex_to_type($hex)
	{
		return array(hexdec(substr($hex, 1, 2)), hexdec(substr($hex, 3, 2)), hexdec(substr($hex, 5, 2)));
	}
	public function convert_type_to_hex($type)
	{
		return '#' . dexhec($type[0]) . dexhec($type[1]) . dexhec($type[2]);
	}
	public function text_string_dimensions($string, $font_type, $font_size, $predefined_string = false)
	{
		return array(0, 0);
	}

	// Privates

	private function write_swf_text($text_string, $font_type, $font_size, $font_color, $bound_x1, $bound_y1, $bound_x2, $bound_y2, $rotate_text, $orientation = "LEFT")
	{
		switch($orientation)
		{
			case "CENTER":
				$align = SWFTEXTFIELD_ALIGN_CENTER;
				break;
			case "RIGHT":
				if($bound_x1 == $bound_x2)
				{
					$bound_x1 -= $this->image_width;
				}

				$align = SWFTEXTFIELD_ALIGN_RIGHT;
				break;
			case "LEFT":
			default:
				$align = SWFTEXTFIELD_ALIGN_LEFT;
				break;
		}

		// TODO: Implement $font_type, $rotate_text support
		$t = new SWFTextField();
		$t->setFont($this->swf_font);
		$t->setColor($font_color[0], $font_color[1], $font_color[2]);
		$t->setHeight($font_size);

		if(($width = abs($bound_x1 - $bound_x2)) > 0)
		{
			$t->setBounds(abs($bound_x1 - $bound_x2), $font_size);
		}

		$t->align($align);
		$t->addString($text_string);

		$added = $this->image->add($t);
		$added->moveTo($bound_x1, $bound_y1);
	}
}

?>
