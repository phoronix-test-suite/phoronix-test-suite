<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2011, Phoronix Media
	Copyright (C) 2011, Michael Larabel

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

class pts_svg_dom
{
	protected $dom;
	protected $svg;

	public function __construct($width, $height)
	{
		$dom = new DOMImplementation();
		$dtd = $dom->createDocumentType('svg', '-//W3C//DTD SVG 1.1//EN', 'http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd');
		$this->dom = $dom->createDocument(null, null, $dtd);
		$this->dom->formatOutput = PTS_IS_CLIENT;

		$pts_comment = $this->dom->createComment(pts_title(true) . ' [ http://www.phoronix-test-suite.com/ ]');
		$this->dom->appendChild($pts_comment);

		$this->svg = $this->dom->createElementNS('http://www.w3.org/2000/svg', 'svg');
		$this->svg->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
		$this->svg->setAttribute('version', '1.1');
		$this->svg->setAttribute('font-family', 'sans-serif');
		$this->svg->setAttribute('viewbox', '0 0 ' . $width . ' ' . $height);
		$this->svg->setAttribute('width', $width);
		$this->svg->setAttribute('height', $height);

		$this->dom->appendChild($this->svg);
	}
	public function save_xml()
	{
		return $this->dom->saveXML();
	}
	public static function sanitize_hex($hex)
	{
		$hex = preg_replace('/(?<=^#)([a-f0-9])\\1([a-f0-9])\\2([a-f0-9])\\3\z/i', '\1\2\3', $hex);

		return strtolower($hex);
	}
	public function draw_svg_line($start_x, $start_y, $end_x, $end_y, $color, $line_width = 1, $extra_elements = null)
	{
		$attributes = array('x1' => $start_x, 'y1' => $start_y, 'x2' => $end_x, 'y2' => $end_y, 'stroke' => $color, 'stroke-width' => $line_width);

		if($extra_elements != null)
		{
			$attributes = array_merge($attributes, $extra_elements);
		}

		$this->add_element('line', $attributes);
	}
	public function draw_svg_arc($center_x, $center_y, $radius, $offset_percent, $percent, $attributes)
	{
		$deg = ($percent * 360);
		$offset_deg = ($offset_percent * 360);
		$arc = $percent > 0.5 ? 1 : 0;

		$p1_x = round(cos(deg2rad($offset_deg)) * $radius) + $center_x;
		$p1_y = round(sin(deg2rad($offset_deg)) * $radius) + $center_y;
		$p2_x = round(cos(deg2rad($offset_deg + $deg)) * $radius) + $center_x;
		$p2_y = round(sin(deg2rad($offset_deg + $deg)) * $radius) + $center_y;

		$attributes['d'] = "M$center_x,$center_y L$p1_x,$p1_y A$radius,$radius 0 $arc,1 $p2_x,$p2_y Z";
		$this->add_element('path', $attributes);
	}
	public function add_element($element_type, $attributes = array())
	{
		$el = $this->dom->createElement($element_type);

		foreach($attributes as $name => $value)
		{
			$el->setAttribute($name, $value);
		}
		$this->svg->appendChild($el);
	}
	public function add_text_element($text_string, $attributes)
	{
		$el = $this->dom->createElement('text');
		$text_node = $this->dom->createTextNode($text_string);

		foreach($attributes as $name => $value)
		{
			$el->setAttribute($name, $value);
		}
		$el->appendChild($text_node);
		$this->svg->appendChild($el);
	}
	public function html_embed_code($file_name, $attributes = null, $is_xsl = false)
	{
		$file_name = str_replace('BILDE_EXTENSION', 'svg', $file_name);
		$attributes = pts_arrays::to_array($attributes);
		$attributes['data'] = $file_name;

		if($is_xsl)
		{
			$html = '<object type="image/svg+xml">';

			foreach($attributes as $option => $value)
			{
				$html .= '<xsl:attribute name="' . $option . '">' . $value . '</xsl:attribute>';
			}
			$html .= '</object>';
		}
		else
		{
			$html = '<object type="image/svg+xml"';

			foreach($attributes as $option => $value)
			{
				$html .= $option . '="' . $value . '" ';
			}
			$html .= '/>';
		}

		return $html;
	}
}

?>
