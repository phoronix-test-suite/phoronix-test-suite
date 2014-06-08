<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2011 - 2014, Phoronix Media
	Copyright (C) 2011 - 2014, Michael Larabel

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

pts_svg_dom_gd::setup_default_font();
pts_svg_dom_gd::setup_draw_funcs();

class pts_svg_dom_gd
{
	private static $color_table;
	private static $default_font;
	private static $draw_funcs;

	public static function setup_default_font($font = null)
	{
		self::$default_font = self::find_default_font($font);
	}
	public static function setup_draw_funcs()
	{
		self::$draw_funcs = array(
			'svg'      => array('pts_svg_dom_gd', 'draw_svg'),
			'script'   => array('pts_svg_dom_gd', 'draw_script'),
			'line'     => array('pts_svg_dom_gd', 'draw_line'),
			'polyline' => array('pts_svg_dom_gd', 'draw_polyline'),
			'text'     => array('pts_svg_dom_gd', 'draw_text'),
			'polygon'  => array('pts_svg_dom_gd', 'draw_polygon'),
			'rect'     => array('pts_svg_dom_gd', 'draw_rectangle'),
			'circle'   => array('pts_svg_dom_gd', 'draw_circle'),
			'ellipse'  => array('pts_svg_dom_gd', 'draw_ellipse'),
			'image'    => array('pts_svg_dom_gd', 'draw_image'),
			'a'        => array('pts_svg_dom_gd', 'draw_anchor'),
			'g'        => array('pts_svg_dom_gd', 'draw_group'),
		);
	}
	public static function find_default_font($find_font)
	{
		if(!defined('BILDE_DEFAULT_FONT'))
		{
			if(is_readable($find_font))
			{
				$default_font = $find_font;
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
				'/usr/local/lib/X11/fonts/bitstream-vera/Vera.ttf',
				'/Library/Fonts/Courier New.ttf',
				'/Library/Fonts/Trebuchet MS.ttf'
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
	public static function svg_dom_to_gd($dom, $format)
	{
		$gd = self::generate_gd($dom);
		if ($gd == null)
		{
			return false;
		}
		self::draw_dom_elements($dom, $gd);
		return self::save_image($gd, $format);
	}
	private static function generate_gd($dom)
	{
		$gd = null;

		if(extension_loaded('gd') && function_exists('imagettftext') && $dom->childNodes->item(2)->nodeName == 'svg')
		{
			$width = $dom->childNodes->item(2)->attributes->getNamedItem('width')->nodeValue;
			$height = $dom->childNodes->item(2)->attributes->getNamedItem('height')->nodeValue;

			if($width > 1 && $height > 1)
			{
				$gd = imagecreatetruecolor($width, $height);

				if(!isset($_REQUEST['svg_dom_gd_no_interlacing']))
				{
					// PHP FPDF fails on image interlacing
					imageinterlace($gd, true);
				}

				if(function_exists('imageantialias'))
				{
					imageantialias($gd, true);
				}
			}
			else
			{
				return null;
			}
		}
		else
		{
			// If the first tag isn't an svg tag, chances are something is broke...
			return null;
		}

		return $gd;
	}
	private static function draw_dom_elements($dom, $gd)
	{
		self::$color_table = array();
		foreach($dom->childNodes->item(2)->childNodes as $node)
		{
			self::draw_node($gd, $node);
		}
	}
	private static function draw_node(&$gd, &$node)
	{
		$draw_func = pts_svg_dom_gd::$draw_funcs[$node->nodeName];
		if ($draw_func == null && PTS_IS_CLIENT)
		{
			echo $node->nodeName . ' not implemented.' . PHP_EOL;
		}
		else
		{
			$draw_func($gd, $node);
		}
	}
	private static function save_image($gd, $format)
	{
		$tmp_output = tempnam('/tmp', 'pts-gd');
		switch($format)
		{
			case 'JPEG':
				imagejpeg($gd, $tmp_output, 100);
				$output = file_get_contents($tmp_output);
				unlink($tmp_output);
				break;
			case 'PNG':
				imagepng($gd, $tmp_output, 1);
				$output = file_get_contents($tmp_output);
				unlink($tmp_output);
				break;
		}

		return $output;
	}
	protected static function gd_color_allocate(&$gd, $hex)
	{
		if(!isset(self::$color_table[$hex]))
		{
			self::$color_table[$hex] = imagecolorallocate($gd, hexdec(substr($hex, 1, 2)), hexdec(substr($hex, 3, 2)), hexdec(substr($hex, 5, 2)));
		}

		return self::$color_table[$hex];
	}
	protected static function attributes_to_array(&$node, $attrs)
	{
		$values = array();

		foreach($attrs as $attribute)
		{
			$values[$attribute] = $node->attributes->getNamedItem($attribute) ? $node->attributes->getNamedItem($attribute)->nodeValue : false;
		}

		return $values;
	}
	private static function draw_svg(&$gd, &$node)
	{
		// don't do anything
		return;
	}
	private static function draw_script(&$gd, &$node)
	{
		// don't do anything
		return;
	}
	private static function draw_line(&$gd, &$node)
	{
		$a = self::attributes_to_array($node, array('x1', 'y1', 'x2', 'y2', 'stroke', 'stroke-width', 'stroke-dasharray'));
		$line_color = self::gd_color_allocate($gd, $a['stroke']);

		if($a['stroke-dasharray'] != null)
		{
			list($dash_length, $blank_length) = explode(',', $a['stroke-dasharray']);

			if($a['y1'] == $a['y2'])
			{
				for($i = $a['x1']; $i < $a['x2']; $i += ($blank_length + $dash_length))
				{
					imagefilledrectangle($gd, $i, ($a['y1'] - floor($a['stroke-width'] / 2)), ($i + $dash_length), ($a['y1'] + floor($a['stroke-width'] / 2)), $line_color);
				}
			}
			else
			{
				for($i = $a['y1']; $i < $a['y2']; $i += ($blank_length + $dash_length))
				{
					imagefilledrectangle($gd, ($a['x1'] - floor($a['stroke-width'] / 2)), $i, ($a['x1'] + floor($a['stroke-width'] / 2)), ($i + $dash_length), $line_color);
				}
			}
		}
		else
		{
			imagesetthickness($gd, $a['stroke-width']);
			imageline($gd, $a['x1'], $a['y1'], $a['x2'], $a['y2'], $line_color);
		}
	}
	private static function draw_polyline(&$gd, &$node)
	{
		$a = self::attributes_to_array($node, array('points', 'stroke', 'stroke-width', 'fill'));
		imagesetthickness($gd, $a['stroke-width']);
		$line_color = self::gd_color_allocate($gd, $a['stroke']);

		$a['points'] = explode(' ', $a['points']);
		for ($i = 1; $i < count($a['points']); $i++)
		{
			$s_point = explode(',', $a['points'][($i - 1)]);
			$e_point = explode(',', $a['points'][$i]);
			imageline($gd, $s_point[0], $s_point[1], $e_point[0], $e_point[1], $line_color);
		}
	}
	private static function draw_text(&$gd, &$node)
	{
		$a = self::attributes_to_array($node, array('x', 'y', 'font-size', 'text-anchor', 'fill', 'dominant-baseline', 'transform'));
		$text = $node->nodeValue;
		$a['font-size'] -= 1.6;

		$box_array = imagettfbbox($a['font-size'], 0, self::$default_font, $text);
		$box_width = $box_array[4] - $box_array[6];
		$box_height = $box_array[1] - $box_array[7];

		$rotate = 0;
		if ($a['transform'])
		{
			$rotate = substr($a['transform'], 7);
			$rotate = substr($rotate, 0, strpos($rotate, ' '));
			// $rotate this should be the rotation degree in SVG

			if ($rotate != 0)
			{
				$rotate += 180;
			}

			switch ($a['text-anchor'])
			{
			case 'middle':
				$a['y'] -= round($box_width / 2);
				break;
			}
		}
		else
		{
			switch ($a['text-anchor'])
			{
			case 'start':
				break;
			case 'middle':
				$a['x'] -= round($box_width / 2);
				break;
			case 'end':
				$a['x'] -= $box_width - 4;
				break;
			}
			switch ($a['dominant-baseline'])
			{
			case 'text-before-edge':
				$a['y'] += $box_height;
				break;
			case 'middle':
				$a['y'] += round($box_height / 2);
				break;
			}
		}
		imagettftext($gd, $a['font-size'], $rotate, $a['x'], $a['y'], self::gd_color_allocate($gd, $a['fill']),
					 self::$default_font, $text);
	}
	private static function draw_polygon(&$gd, &$node)
	{
		$a = self::attributes_to_array($node, array('points', 'fill', 'stroke', 'stroke-width'));
		$a['points'] = explode(' ', $a['points']);
		$points = array();
		foreach ($a['points'] as &$point)
		{
			$point = explode(',', $point);
			array_push($points, $point[0]);
			array_push($points, $point[1]);
		}

		if ($a['stroke-width'])
		{
			imagesetthickness($gd, $a['stroke-width']);
			imagefilledpolygon($gd, $points, count($a['points']), self::gd_color_allocate($gd, $a['stroke']));
		}
		imagefilledpolygon($gd, $points, count($a['points']), self::gd_color_allocate($gd, $a['fill']));
	}
	private static function draw_rectangle(&$gd, &$node)
	{
		$a = self::attributes_to_array($node, array('x', 'y', 'width', 'height', 'fill', 'stroke', 'stroke-width'));

		if ($a['fill'] != 'none')
		{
			imagefilledrectangle($gd, $a['x'], $a['y'],	($a['x'] + $a['width']), ($a['y'] + $a['height']),
								 self::gd_color_allocate($gd, $a['fill']));
		}

		if ($a['stroke'] != null)
		{
			// TODO: implement $a['stroke-width']
			imagerectangle($gd, $a['x'], $a['y'], ($a['x'] + $a['width']), ($a['y'] + $a['height']),
						   self::gd_color_allocate($gd, $a['stroke']));
		}
	}
	private static function draw_circle(&$gd, &$node)
	{
		$a = self::attributes_to_array($node, array('cx', 'cy', 'r', 'fill'));
		imagefilledellipse($gd, $a['cx'], $a['cy'], ($a['r'] * 2), ($a['r'] * 2),
						   self::gd_color_allocate($gd, $a['fill']));
	}
	private static function draw_ellipse(&$gd, &$node)
	{
		$a = self::attributes_to_array($node, array('cx', 'cy', 'rx', 'ry', 'fill', 'stroke', 'stroke-width'));
		imagefilledellipse($gd, $a['cx'], $a['cy'], ($a['rx'] * 2), ($a['ry'] * 2),
						   self::gd_color_allocate($gd, $a['fill']));

		if ($a['stroke'] != null)
		{
			// TODO: implement $a['stroke-width']
			imagefilledellipse($gd, $a['cx'], $a['cy'], ($a['rx'] * 2), ($a['ry'] * 2),
							   self::gd_color_allocate($gd, $a['stroke']));
		}
	}
	private static function draw_image(&$gd, &$node)
	{
		$a = self::attributes_to_array($node, array('xlink:href', 'x', 'y', 'width', 'height'));

		if (substr($a['xlink:href'], 0, 22) == 'data:image/png;base64,')
		{
			$img = imagecreatefromstring(base64_decode(substr($a['xlink:href'], 22)));
		}
		else
		{
			$img = imagecreatefromstring(file_get_contents($a['xlink:href']));
		}

		imagecopyresampled($gd, $img, $a['x'], $a['y'], 0, 0, $a['width'], $a['height'], imagesx($img), imagesy($img));
	}
	private static function draw_anchor(&$gd, &$node)
	{
		// This is just a link so get whatever is the child of the embedded link to display
		$childNode = $node->childNodes->item(0);
		self::draw_node($gd, $childNode);
	}
	private static function draw_group(&$gd, &$node)
	{
		// don't do anything
		foreach($node->childNodes as $childNode)
		{
			self::draw_node($gd, $childNode);
		}
	}
}

?>
