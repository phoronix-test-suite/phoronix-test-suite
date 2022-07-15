<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2011 - 2017, Phoronix Media
	Copyright (C) 2011 - 2017, Michael Larabel

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
	protected $width;
	protected $height;

	public function __construct($width, $height)
	{
		$dom = new DOMImplementation();
		$dtd = $dom->createDocumentType('svg', '-//W3C//DTD SVG 1.1//EN', 'http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd');
		$this->dom = $dom->createDocument(null, '', $dtd);
		$this->dom->formatOutput = PTS_IS_CLIENT && PTS_IS_DEV_BUILD;

		$pts_comment = $this->dom->createComment(pts_core::program_title() . ' [ https://www.phoronix-test-suite.com/ ]');
		$this->dom->appendChild($pts_comment);

		$this->svg = $this->dom->createElementNS('http://www.w3.org/2000/svg', 'svg');
		$this->svg->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
		$this->svg->setAttribute('version', '1.1');
		$this->svg->setAttribute('font-family', 'sans-serif, droid-sans, helvetica, verdana, tahoma');
		$this->svg->setAttribute('viewbox', '0 0 ' . $width . ' ' . $height);
		$this->svg->setAttribute('width', $width);
		$this->svg->setAttribute('height', $height);
		$this->svg->setAttribute('preserveAspectRatio', 'xMinYMin meet');
		$this->width = $width;
		$this->height = $height;

		$this->dom->appendChild($this->svg);
	}
	public function output($save_as = null, $output_format = 'SVG')
	{
		if(isset($_SERVER['HTTP_USER_AGENT']) || isset($_REQUEST['force_format']))
		{
			static $browser_renderer = null;

			if(isset($_REQUEST['force_format']))
			{
				// Don't nest the force_format within the browser_renderer null check in case its overriden by OpenBenchmarking.org dynamically
				$output_format = $_REQUEST['force_format'];
			}
			else if($browser_renderer == null)
			{
				$output_format = pts_render::renderer_compatibility_check($_SERVER['HTTP_USER_AGENT']);
			}
			else
			{
				$output_format = $browser_renderer;
			}
		}
		$format = $output_format;

		switch($output_format)
		{
			case 'JPG':
			case 'JPEG':
				$output = pts_svg_dom_gd::svg_dom_to_gd($this->dom, 'JPEG');
				$output_format = 'jpg';
				break;
			case 'PNG':
				$output = pts_svg_dom_gd::svg_dom_to_gd($this->dom, 'PNG');
				$output_format = 'png';
				break;
			case 'HTML':
				$html = new pts_svg_dom_html($this->dom);
				$output = $html->get_html();
				$output_format = 'html';
				break;
			case 'SVG':
			default:
				$output = $this->save_xml();
				$output_format = 'svg';
				break;
		}

		if($output == null)
		{
			return false;
		}
		else if($save_as)
		{
			return file_put_contents(str_replace('BILDE_EXTENSION', $output_format, $save_as), $output);
		}
		else
		{
			return $output;
		}
	}
	public function save_xml()
	{
		return $this->dom->saveXML();
	}
	public function draw_svg_line($start_x, $start_y, $end_x, $end_y, $color, $line_width = 1, $extra_elements = null)
	{
		// this function is equivalent to $this->svg_dom->add_element('line', array('x1' => , 'y1' => , 'x2' => , 'y2' => , 'stroke' => , 'stroke-width' => ));
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
	public function draw_svg_circle($center_x, $center_y, $radius, $color, $extra_attributes = null)
	{
		$extra_attributes['cx'] = $center_x;
		$extra_attributes['cy'] = $center_y;
		$extra_attributes['r'] = $radius;
		$extra_attributes['fill'] = $color;
		$this->add_element('circle', $extra_attributes);
	}
	public function make_a($url)
	{
		$el = $this->dom->createElement('a');
		$el->setAttribute('xlink:href', $url);
		$el->setAttribute('target', '_blank');
		return $this->svg->appendChild($el);
	}
	public function make_g($attributes = array(), $append_to = false)
	{
		$el = $this->dom->createElement('g');
		foreach($attributes as $name => $value)
		{
			$el->setAttribute($name, $value);
		}
		return $append_to ? $append_to->appendChild($el) : $this->svg->appendChild($el);
	}
	public function add_element($element_type, $attributes = array(), $append_to = false)
	{
		$el = $this->dom->createElement($element_type);

		if(isset($attributes['xlink:href']) && $attributes['xlink:href'] != null && $element_type != 'a' && ($element_type != 'image' || (isset($attributes['http_link']) && $attributes['http_link'] != null)))
		{
			// image tag uses xlink:href as the image src, so check for 'http_link' instead to make a link out of it
			$link_key = ($element_type == 'image' ? 'http_link' : 'xlink:href');
			$link = $this->dom->createElement('a');
			$link->setAttribute('xlink:href', $attributes[$link_key]);
			$link->setAttribute('target', '_blank');
			$link->appendChild($el);
			if($append_to)
			{
				$append_to->appendChild($link);
			}
			else
			{
				$this->svg->appendChild($link);
			}
			unset($attributes[$link_key]);
		}
		else
		{
			if($append_to)
			{
				$append_to->appendChild($el);
			}
			else
			{
				$this->svg->appendChild($el);
			}
		}

		foreach($attributes as $name => $value)
		{
			$el->setAttribute($name, $value);
		}
	}
	public function add_text_element($text_string, $attributes, $append_to = false)
	{
		if(empty($text_string))
		{
			return;
		}

		$el = $this->dom->createElement('text');
		$text_node = $this->dom->createTextNode($text_string);
		$el->appendChild($text_node);

		if(isset($attributes['xlink:href']) && $attributes['xlink:href'] != null)
		{
			$link = $this->dom->createElement('a');
			$link->setAttribute('xlink:href', $attributes['xlink:href']);
			$link->setAttribute('target', '_blank');
			$link->appendChild($el);
			if($append_to)
			{
				$append_to->appendChild($link);
			}
			else
			{
				$this->svg->appendChild($link);
			}
			unset($attributes['xlink:href']);
		}
		else
		{
			if($append_to)
			{
				$append_to->appendChild($el);
			}
			else
			{
				$this->svg->appendChild($el);
			}
		}

		foreach($attributes as $name => $value)
		{
			if($value == null && $value !== 0)
			{
				continue;
			}

			$el->setAttribute($name, $value);
		}
	}
	public function add_textarea_element($text_string, $attributes, &$estimated_height = 0, &$append_to = false)
	{
		if(!isset($attributes['width']))
		{
			$attributes['width'] = $this->width - $attributes['x'];
		}

		$queue_dimensions = self::estimate_text_dimensions($text_string, $attributes['font-size']);
		if($queue_dimensions[0] < $attributes['width'])
		{
			// No wrapping is occuring, so stuff it in a more efficient text element instead
			unset($attributes['width']);
			$this->add_text_element($text_string, $attributes);
			$estimated_height += ($attributes['font-size'] + 3);
			return;
		}

		$el = $this->dom->createElement('text');
		$word_queue = null;
		$line_count = 0;
		$words = explode(' ', $text_string);
		$word_count = count($words);
		$last_word = null;

		foreach($words as $i => $word)
		{
			$word_queue .= $word . ' ';

			$queue_dimensions = self::estimate_text_dimensions($word_queue, ($attributes['font-size'] - 0.45));
			if($queue_dimensions[0] > $attributes['width'] || $i == ($word_count - 1))
			{
				if($i != ($word_count - 1))
				{
					$last_word_pos = strrpos($word_queue, ' ', -2);
					$last_word = substr($word_queue, $last_word_pos);
					$word_queue = substr($word_queue, 0, $last_word_pos);
				}

				$tspan = $this->dom->createElement('tspan');
				$tspan->setAttribute('x', $attributes['x']);
				$tspan->setAttribute('y', $attributes['y']);
				$tspan->setAttribute('dx', ($line_count == 0 ? 0 : 5));
				$tspan->setAttribute('dy', ($line_count * ($attributes['font-size'] + 1)));
				$text_node = $this->dom->createTextNode($word_queue);
				$tspan->appendChild($text_node);
				$el->appendChild($tspan);
				$word_queue = $last_word;
				$line_count++;
			}
		}
		$estimated_height += $line_count * ($attributes['font-size'] + 2);
		unset($attributes['width']);

		if(isset($attributes['xlink:href']) && $attributes['xlink:href'] != null)
		{
			$link = $this->dom->createElement('a');
			$link->setAttribute('xlink:href', $attributes['xlink:href']);
			$link->setAttribute('target', '_blank');
			$link->appendChild($el);
			if($append_to)
			{
				$append_to->appendChild($link);
			}
			else
			{
				$this->svg->appendChild($link);
			}
			unset($attributes['xlink:href']);
		}
		else
		{
			if($append_to)
			{
				$append_to->appendChild($el);
			}
			else
			{
				$this->svg->appendChild($el);
			}
		}

		foreach($attributes as $name => $value)
		{
			$el->setAttribute($name, $value);
		}
	}
	public static function estimate_text_dimensions($text_string, $font_size)
	{
		if($text_string == null)
		{
			return array(0, 0);
		}
		$box_height = ceil(0.76 * $font_size);
		$box_width = ceil((0.76 * strlen($text_string) * $font_size) - ceil(strlen($text_string) * 1.05));

		// Width x Height
		return array($box_width, $box_height);
	}
}
?>
