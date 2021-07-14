<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2016, Phoronix Media
	Copyright (C) 2016, Michael Larabel

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


class pts_svg_dom_html
{
	protected $html_dom;

	public function __construct($dom)
	{
		$new_dom = new DOMImplementation();
		$dtd = $new_dom->createDocumentType('html', '', '');
		$this->html_dom = $new_dom->createDocument(null, '', $dtd);
		$this->html_dom->formatOutput = true;

		$width = $dom->childNodes->item(2)->attributes->getNamedItem('width')->nodeValue;
		$height = $dom->childNodes->item(2)->attributes->getNamedItem('height')->nodeValue;
		$html = null;

		$main_target = $this->html_dom->createElement('div');
		$main_target->setAttribute('style', 'position: relative; width: ' . $width . 'px; height: ' . $height . 'px;');

		foreach($dom->childNodes->item(2)->childNodes as $node)
		{
			$this->evaluate_node($node, $main_target);
			// imagejpeg($this->image, $output_file, $quality);
			//var_dump($node->attributes);
		}

		$this->html_dom->appendChild($main_target);
	}
	public function get_html()
	{
		return $this->html_dom->saveHTML();
	}
	protected function evaluate_node(&$node, &$target, $preset = null)
	{
		switch($node->nodeName)
		{
			case 'g':
				// Special handling for g
				$g = self::attributes_to_array($node, false, $preset);
				for($i = 0; $i < $node->childNodes->length; $i++)
				{
					$n = $node->childNodes->item($i);
					$this->evaluate_node($n, $target, $g);
				}
				break;
			case 'a':
				$node = $node->childNodes->item(0);
				$this->evaluate_node($node, $target, $preset);
				break;
			case 'svg':
				// Not relevant at this point to rendering
				break;
			case 'line':
				$a = self::attributes_to_array($node, array('x1', 'y1', 'x2', 'y2', 'stroke', 'stroke-width', 'stroke-dasharray'), $preset);
				$border_style = $a['stroke-dasharray'] != null ? 'dashed' : 'solid';
				$tag = $this->html_dom->createElement('div');
				$tag->setAttribute('style', 'border: 1px ' . $border_style . ' ' . $a['stroke'] . '; height: 0; ' . self::compute_html_line_style($a['x1'], $a['y1'], $a['x2'], $a['y2']));
				$target->appendChild($tag);

				break;
			case 'polyline':
				$a = self::attributes_to_array($node, array('points', 'stroke', 'stroke-width', 'fill'), $preset);
				$a['points'] = explode(' ', $a['points']);
				for($i = 1; $i < count($a['points']); $i++)
				{
					$s_point = explode(',', $a['points'][($i - 1)]);
					$e_point = explode(',', $a['points'][$i]);
					$border_style = $a['stroke-dasharray'] != null ? 'dashed' : 'solid';
					$tag = $this->html_dom->createElement('div');
					$tag->setAttribute('style', 'border: 1px ' . $border_style . ' ' . $a['stroke'] . '; height: 0; ' . self::compute_html_line_style($s_point[0], $s_point[1], $e_point[0], $e_point[1]));
					$target->appendChild($tag);
				}
				break;
			case 'text':
				$a = self::attributes_to_array($node, array('x', 'y', 'font-size', 'text-anchor', 'fill', 'dominant-baseline', 'transform'), $preset);
				$text = $node->nodeValue;

				$extra = null;
				if($a['transform'])
				{
					$extra .= 'transform: ' . $a['transform'] . '; ';
				}
				else
				{
					$extra .= 'text-anchor: ' . $a['text-anchor'] . '; ';
					$extra .= 'dominant-baseline: ' . $a['dominant-baseline'] . '; ';
				}

				$tag = $this->html_dom->createElement('div');
				$tag->setAttribute('style', 'position: absolute; left: ' . $a['x'] . 'px ; top: ' . $a['y'] . 'px; color: ' . $a['fill'] . '; font-size: ' . $a['font-size'] . 'px; ');
				$text_node = $this->html_dom->createTextNode($text);
				$tag->appendChild($text_node);
				$target->appendChild($tag);
				break;
			case 'polygon':
				$a = self::attributes_to_array($node, array('points', 'fill', 'stroke', 'stroke-width'), $preset);
				// no support in this short of SVG or HTML5 canvas
				break;
			case 'rect':
				// Draw a rectangle
				$a = self::attributes_to_array($node, array('x', 'y', 'width', 'height', 'fill', 'stroke', 'stroke-width'), $preset);
				$background = $a['fill'] != 'none' ? $a['fill'] : 'transparent';
				$border = $a['stroke'] != null ? 'border: ' . $a['stroke-width'] . 'px solid ' . $a['stroke'] . '; ' : '';
				$tag = $this->html_dom->createElement('div');
				$tag->setAttribute('style', 'position: absolute; left: ' . $a['x'] . 'px; top: ' . $a['y'] . 'px; width: ' . $a['width'] . 'px; height: ' . $a['height'] . 'px; background: ' . $background . '; ' . $border);
				$target->appendChild($tag);
				break;
			case 'circle':
				// Draw a circle
				$a = self::attributes_to_array($node, array('cx', 'cy', 'r', 'fill'), $preset);
				// no support in this short of SVG or HTML5 canvas
				break;
			case 'ellipse':
				// Draw a ellipse/circle
				$a = self::attributes_to_array($node, array('cx', 'cy', 'rx', 'ry', 'fill', 'stroke', 'stroke-width'), $preset);
				// no support in this short of SVG or HTML5 canvas
				break;
			case 'image':
				$a = self::attributes_to_array($node, array('xlink:href', 'x', 'y', 'width', 'height'), $preset);
				// TODO
				/*
				if(substr($a['xlink:href'], 0, 22) == 'data:image/png;base64,')
				{
					$img = imagecreatefromstring(base64_decode(substr($a['xlink:href'], 22)));
				}
				else
				{
					$img = imagecreatefromstring(file_get_contents($a['xlink:href']));
				}
				imagecopyresampled(, $img, $a['x'], $a['y'], 0, 0, $a['width'], $a['height'], imagesx($img), imagesy($img));
				*/
				break;
			case 'path':
				break;
			default:
				if(PTS_IS_CLIENT)
				{
					echo $node->nodeName . ' not implemented.' . PHP_EOL;
				}
				break;
		}
	}
	protected static function compute_html_line_style($x1, $y1, $x2, $y2)
	{
		$a = $x1 - $x2;
		$b = $y1 - $y2;
		$c = sqrt($a * $a + $b * $b);

		$sx = ($x1 + $x2) / 2;
		$y = ($y1 + $y2) / 2;

		$x = $sx - $c / 2;
		$alpha = pi() - atan2(($b * -1), $a);

		return 'width: ' . $c . '; -moz-transform: rotate(' . $alpha . 'rad); -webkit-transform: rotate(' . $alpha . 'rad); transform: rotate(' . $alpha . 'rad); position: absolute; top: ' . $y . '; left: ' . $x . '; ';
	}
	protected static function attributes_to_array(&$node, $attrs = false, $values = null)
	{
		if(!is_array($values))
		{
			$values = array();
		}

		foreach($node->attributes as $attribute)
		{
			$values[$attribute->nodeName] = $attribute->nodeValue;
		}

		if($attrs != false)
		{
			foreach($attrs as $attribute)
			{
				if(!isset($values[$attribute]))
				{
					$values[$attribute] = false;
				}
			}
		}

		return $values;
	}
}

?>
