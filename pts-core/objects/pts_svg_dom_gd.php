<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2011 - 2020, Phoronix Media
	Copyright (C) 2011 - 2020, Michael Larabel

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

class pts_svg_dom_gd
{
	protected static $color_table;
	protected static $default_font;

	public static function setup_default_font($font = null)
	{
		$default_font = self::find_default_font($font);

		if($default_font)
		{
			$font_type = basename($default_font);

			if($default_font != $font_type && !defined('CUSTOM_FONT_DIR'))
			{
				define('CUSTOM_FONT_DIR', dirname($default_font));
			}

			self::$default_font = $font_type;
		}

		if(getenv('GDFONTPATH') == false)
		{
			if(defined('CUSTOM_FONT_DIR'))
			{
				putenv('GDFONTPATH=' . CUSTOM_FONT_DIR);
			}
			else if(defined('FONT_DIR'))
			{
				putenv('GDFONTPATH=' . FONT_DIR);
			}
			else if(($font_env = getenv('FONT_DIR')) != false)
			{
				putenv('GDFONTPATH=' . $font_env);
			}
			else if(is_dir('/usr/share/fonts'))
			{
				putenv('GDFONTPATH=/usr/share/fonts');
			}
		}
	}
	public static function find_default_font($find_font)
	{
		if(!defined('BILDE_DEFAULT_FONT'))
		{
			$default_font = false;

			if($find_font != null && is_readable($find_font))
			{
				$default_font = $find_font;
			}
			else
			{
				$font_dir = false;
				foreach(array('/usr/share/fonts/', '/Library/Fonts', 'C:/Windows/Fonts/') as $font_dir_check)
				{
					if(is_dir($font_dir_check))
					{
						$font_dir = $font_dir_check;
						break;
					}
				}

				if($font_dir)
				{
					$backup_font = false;
					$preferred_fonts = array('DejaVuSans.ttf', 'LiberationSans-Regular.ttf', 'Vera.ttf', 'FreeSans.ttf', 'verdana.ttf', 'calibri.ttf', 'Courier New.ttf');
					$backup_fonts = array('Sans.ttf', 'Sans-Regular.ttf', '-Regular.ttf', 'Trebuchet MS.ttf');

					foreach(array_merge(pts_file_io::glob($font_dir . '*/*/*.ttf'), pts_file_io::glob($font_dir . '*/*.ttf'), pts_file_io::glob($font_dir . '*.ttf')) as $ttf_font_check)
					{
						foreach($preferred_fonts as $pf)
						{
							if(stripos($ttf_font_check, $pf) !== false)
							{
								$default_font = $ttf_font_check;
								break;
							}
						}
						if(!empty($default_font))
						{
							break;
						}
						foreach($backup_fonts as $bf)
						{
							if(stripos($ttf_font_check, $bf) !== false)
							{
								$backup_font = $ttf_font_check;
								break;
							}
						}
					}
					if(empty($default_font) && !empty($backup_font))
					{
						$default_font = $backup_font;
					}
				}
			}

			define('BILDE_DEFAULT_FONT', $default_font);
		}

		return BILDE_DEFAULT_FONT;
	}
	public static function is_supported()
	{
		return (extension_loaded('gd') && function_exists('getimagesizefromstring')  && function_exists('imagettftext')) || (PTS_IS_CLIENT && (pts_client::executable_in_path('rsvg-convert') || pts_client::executable_in_path('inkscape')));
	}
	public static function svg_dom_to_gd($dom, $format)
	{
		if($dom->childNodes->item(2)->nodeName == 'svg')
		{
			$width = $dom->childNodes->item(2)->attributes->getNamedItem('width')->nodeValue;
			$height = $dom->childNodes->item(2)->attributes->getNamedItem('height')->nodeValue;

			if(extension_loaded('gd') && function_exists('imagettftext') && $width > 1 && $height > 1)
			{
				$gd = imagecreatetruecolor($width, $height);
				if(function_exists('imageresolution'))
				{
					imageresolution($gd, 200);
				}
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
			else if(PTS_IS_CLIENT && pts_client::executable_in_path('rsvg-convert') && $format == 'PNG')
			{
				// Using Inkscape for converting SVG to PNG generally means higher quality conversion
				$temp_svg = sys_get_temp_dir() . '/pts-temp-' . rand(0, 50000) . '.svg';
				file_put_contents($temp_svg, $dom->saveXML());
				$temp_png = sys_get_temp_dir() . '/pts-temp-' . rand(0, 50000) . '.png';
				shell_exec('rsvg-convert -f png -o ' . $temp_png . ' ' . $temp_svg);
				unlink($temp_svg);
				if(is_file($temp_png))
				{
					$temp_png_output = file_get_contents($temp_png);
					unlink($temp_png);
					return $temp_png_output;
				}
				unlink($temp_png);
			}
			else if(PTS_IS_CLIENT && pts_client::executable_in_path('inkscape') && $format == 'PNG')
			{
				// Using Inkscape for converting SVG to PNG generally means higher quality conversion
				$temp_svg = sys_get_temp_dir() . '/pts-temp-' . rand(0, 50000) . '.svg';
				file_put_contents($temp_svg, $dom->saveXML());
				$temp_png = sys_get_temp_dir() . '/pts-temp-' . rand(0, 50000) . '.png';
				shell_exec('inkscape -z -e ' . $temp_png . ' -w ' . $width . ' -h ' . $height . ' ' . $temp_svg);
				unlink($temp_svg);
				if(is_file($temp_png))
				{
					$temp_png_output = file_get_contents($temp_png);
					unlink($temp_png);
					return $temp_png_output;
				}
				unlink($temp_png);
			}
			else
			{
				return false;
			}

//foreach($dom->childNodes->item(2)->attributes  as $atrr)
//	{ echo $atrr->name . ' ' . $atrr->value . PHP_EOL; }
		}
		else
		{
			// If the first tag isn't an svg tag, chances are something is broke...
			return false;
		}

		self::$color_table = array();
		foreach($dom->childNodes->item(2)->childNodes as $node)
		{
			self::evaluate_node($node, $gd);
			// imagejpeg($this->image, $output_file, $quality);
			//var_dump($node->attributes);
		}

		ob_start();
		switch($format)
		{
			case 'JPEG':
				imagejpeg($gd);
				break;
			case 'PNG':
				imagepng($gd);
				break;
		}
		$output = ob_get_clean();

		return $output;
	}
	protected static function evaluate_node(&$node, &$gd, $preset = null)
	{
		switch($node->nodeName)
		{
			case 'g':
				// Special handling for g
				$g = self::attributes_to_array($node, false, $preset);
				for($i = 0; $i < $node->childNodes->length; $i++)
				{
					$n = $node->childNodes->item($i);
					self::evaluate_node($n, $gd, $g);
				}
				break;
			case 'a':
				$node = $node->childNodes->item(0);
				self::evaluate_node($node, $gd, $preset);
				break;
			case 'svg':
				// Not relevant at this point to GD rendering
				break;
			case 'line':
				$a = self::attributes_to_array($node, array('x1', 'y1', 'x2', 'y2', 'stroke', 'stroke-width', 'stroke-dasharray'), $preset);
				$line_color = self::gd_color_allocate($gd, $a['stroke']);
				if($a['stroke-dasharray'] != null)
				{
					list($dash_length, $blank_length) = explode(',', $a['stroke-dasharray']);
					if($a['y1'] == $a['y2'])
					{
						for($i = $a['x1']; $i < $a['x2']; $i += ($blank_length + $dash_length))
						{
							imagefilledrectangle($gd, $i, ($a['y1'] - floor($a['stroke-width'] / 2)), ($i + $dash_length), ($a['y1'] + floor($a['stroke-width'] / 2)), $line_color);
							//imageline($gd, $i, $pos, ($i + $dash_length), $pos, $line_color);
						}
					}
					else
					{
						for($i = $a['y1']; $i < $a['y2']; $i += ($blank_length + $dash_length))
						{
							imagefilledrectangle($gd, ($a['x1'] - floor($a['stroke-width'] / 2)), $i, ($a['x1'] + floor($a['stroke-width'] / 2)), ($i + $dash_length), $line_color);
							//imageline($gd, $i, $pos, ($i + $dash_length), $pos, $line_color);
						}
					}
				}
				else
				{
					imagesetthickness($gd, $a['stroke-width']);
					imageline($gd, $a['x1'], $a['y1'], $a['x2'], $a['y2'], $line_color);
				}
				break;
			case 'polyline':
				$a = self::attributes_to_array($node, array('points', 'stroke', 'stroke-width', 'fill'), $preset);
				imagesetthickness($gd, $a['stroke-width']);
				$line_color = self::gd_color_allocate($gd, $a['stroke']);

				$a['points'] = explode(' ', $a['points']);
				for($i = 1; $i < count($a['points']); $i++)
				{
					$s_point = explode(',', $a['points'][($i - 1)]);
					$e_point = explode(',', $a['points'][$i]);
					imageline($gd, $s_point[0], $s_point[1], $e_point[0], $e_point[1], $line_color);
				}
				break;
			case 'text':
				$a = self::attributes_to_array($node, array('x', 'y', 'font-size', 'text-anchor', 'fill', 'dominant-baseline', 'transform'), $preset);
				$text = $node->nodeValue;
				$a['font-size'] -= 1.6;
				$box_array = imagettfbbox($a['font-size'], 0, self::$default_font, $text);
				$box_width = $box_array[4] - $box_array[6];
				$box_height = $box_array[1] - $box_array[7];

				$rotate = 0;
				if($a['transform'])
				{
					$rotate = substr($a['transform'], 7);
					$rotate = substr($rotate, 0, strpos($rotate, ' '));
					// $rotate this should be the rotation degree in SVG
					if($rotate != 0)
					{
						$rotate += 180;
					}
					switch($a['text-anchor'])
					{
						case 'middle':
							$a['y'] -= round($box_width / 2);
							break;
					}
				}
				else
				{
					switch($a['text-anchor'])
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
					switch($a['dominant-baseline'])
					{
						case 'text-before-edge':
							$a['y'] += $box_height;
							break;
						case 'middle':
							$a['y'] += round($box_height / 2);
							break;
					}
				}
				imagettftext($gd, $a['font-size'], $rotate, $a['x'], $a['y'], self::gd_color_allocate($gd, $a['fill']), self::$default_font, $text);
				break;
			case 'polygon':
				$a = self::attributes_to_array($node, array('points', 'fill', 'stroke', 'stroke-width'), $preset);
				$a['points'] = explode(' ', $a['points']);
				$points = array();
				foreach($a['points'] as &$point)
				{
					$point = explode(',', $point);
					$points[] = $point[0];
					$points[] = $point[1];
				}
				if($a['stroke-width'])
				{
					imagesetthickness($gd, $a['stroke-width']);
					if(PHP_MAJOR_VERSION >= 8)
					{
						// PHP 8.1 deprecates the $num_points parameter and issues its warning for usage..
						// However, the alternative signature without using $num_points was only added in PHP 8.0.0
						imagefilledpolygon($gd, $points, self::gd_color_allocate($gd, $a['stroke']));
					}
					else
					{
						imagefilledpolygon($gd, $points, count($a['points']), self::gd_color_allocate($gd, $a['stroke']));
					}
				}
				if(PHP_MAJOR_VERSION >= 8)
				{
					imagefilledpolygon($gd, $points, self::gd_color_allocate($gd, $a['fill']));
				}
				else
				{
					imagefilledpolygon($gd, $points, count($a['points']), self::gd_color_allocate($gd, $a['fill']));
				}
				break;
			case 'rect':
				// Draw a rectangle
				$a = self::attributes_to_array($node, array('x', 'y', 'width', 'height', 'fill', 'stroke', 'stroke-width'), $preset);
				if($a['fill'] != 'none')
				{
					imagefilledrectangle($gd, $a['x'], $a['y'], ($a['x'] + $a['width']), ($a['y'] + $a['height']), self::gd_color_allocate($gd, $a['fill']));
				}
				if($a['stroke'] != null)
				{
					// TODO: implement $a['stroke-width']
					imagerectangle($gd, $a['x'], $a['y'], ($a['x'] + $a['width']), ($a['y'] + $a['height']), self::gd_color_allocate($gd, $a['stroke']));
				}
				break;
			case 'circle':
				// Draw a circle
				$a = self::attributes_to_array($node, array('cx', 'cy', 'r', 'fill'), $preset);
				imagefilledellipse($gd, $a['cx'], $a['cy'], ($a['r'] * 2), ($a['r'] * 2), self::gd_color_allocate($gd, $a['fill']));
				break;
			case 'ellipse':
				// Draw a ellipse/circle
				$a = self::attributes_to_array($node, array('cx', 'cy', 'rx', 'ry', 'fill', 'stroke', 'stroke-width'), $preset);
				imagefilledellipse($gd, $a['cx'], $a['cy'], ($a['rx'] * 2), ($a['ry'] * 2), self::gd_color_allocate($gd, $a['fill']));
				if($a['stroke'] != null)
				{
					// TODO: implement $a['stroke-width']
					imagefilledellipse($gd, $a['cx'], $a['cy'], ($a['rx'] * 2), ($a['ry'] * 2), self::gd_color_allocate($gd, $a['stroke']));
				}
				break;
			case 'image':
				$a = self::attributes_to_array($node, array('xlink:href', 'x', 'y', 'width', 'height'), $preset);
				if(substr($a['xlink:href'], 0, 22) == 'data:image/png;base64,')
				{
					$img = imagecreatefromstring(base64_decode(substr($a['xlink:href'], 22)));
				}
				else
				{
					$img = imagecreatefromstring(file_get_contents($a['xlink:href']));
				}
				imagecopyresampled($gd, $img, $a['x'], $a['y'], 0, 0, $a['width'], $a['height'], imagesx($img), imagesy($img));
				break;
			case 'path':
				// TODO XXX
				break;
			default:
				if(PTS_IS_CLIENT)
				{
					echo $node->nodeName . ' not implemented.' . PHP_EOL;
				}
				break;
		}
	}
	protected static function gd_color_allocate(&$gd, $hex)
	{
		if(!isset(self::$color_table[$hex]))
		{
			self::$color_table[$hex] = imagecolorallocate($gd, hexdec(substr($hex, 1, 2)), hexdec(substr($hex, 3, 2)), hexdec(substr($hex, 5, 2)));
		}

		return self::$color_table[$hex];
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
	public static function generate_result_file_graphs($test_results_identifier, $save_to_dir = false, $extra_attributes = null)
	{
		// Since dropping the old result viewer, this function is no longer used except for niche cases (debug render, PDF generation)

		if($save_to_dir)
		{
			if(pts_file_io::mkdir($save_to_dir . '/result-graphs') == false)
			{
				// Don't delete old files now, in case any modules (e.g. FlameGrapher) output something in there ahead of time
				/*// Directory must exist, so remove any old graph files first
				foreach(pts_file_io::glob($save_to_dir . '/result-graphs/*') as $old_file)
				{
					unlink($old_file);
				}*/
			}
		}

		if($test_results_identifier instanceof pts_result_file)
		{
			$result_file = &$test_results_identifier;
		}
		else
		{
			$result_file = new pts_result_file($test_results_identifier);
		}

		$result_file->avoid_duplicate_identifiers();

		$generated_graphs = array();
		$generated_graph_tables = false;

		// Render overview chart
		if($save_to_dir)
		{
			$chart = new pts_ResultFileTable($result_file);
			$chart->renderChart($save_to_dir . '/result-graphs/overview.BILDE_EXTENSION');

			$intent = -1;
			if(($intent = pts_result_file_analyzer::analyze_result_file_intent($result_file, $intent, true)) || $result_file->get_system_count() == 1)
			{
				$chart = new pts_ResultFileCompactSystemsTable($result_file, $intent);
			}
			else
			{
				$chart = new pts_ResultFileSystemsTable($result_file);
			}
			$chart->renderChart($save_to_dir . '/result-graphs/systems.BILDE_EXTENSION');
			unset($chart);

			if($intent && is_dir($result_file->get_system_log_dir()))
			{
				$chart = new pts_DetailedSystemComponentTable($result_file, $result_file->get_system_log_dir(), $intent);

				if($chart)
				{
					$chart->renderChart($save_to_dir . '/result-graphs/detailed_component.BILDE_EXTENSION');
				}
			}
		}
		$result_objects = $result_file->get_result_objects();
		$test_titles = array();
		foreach($result_objects as &$result_object)
		{
			$test_titles[] = $result_object->test_profile->get_title();
		}

		$offset = 0;
		foreach($result_objects as $key => &$result_object)
		{
			$save_to = $save_to_dir;
			$offset++;

			if($save_to_dir && is_dir($save_to_dir))
			{
				$save_to .= '/result-graphs/' . $offset . '.BILDE_EXTENSION';

				if(PTS_IS_CLIENT)
				{
					if($result_file->is_multi_way_comparison(null, $extra_attributes))
					{
						$table_keys = array();

						foreach($test_titles as $this_title_index => $this_title)
						{
							if(isset($test_titles[$key]) && $this_title == $test_titles[$key])
							{
								$table_keys[] = $this_title_index;
							}
						}
					}
					else
					{
						$table_keys = $key;
					}

					$chart = new pts_ResultFileTable($result_file, null, $table_keys);
					$chart->renderChart($save_to_dir . '/result-graphs/' . $offset . '_table.BILDE_EXTENSION');
					unset($chart);
					$generated_graph_tables = true;
				}
			}

			$graph = pts_render::render_graph($result_object, $result_file, $save_to, $extra_attributes);

			if($graph == false)
			{
				continue;
			}

			$generated_graphs[] = $graph;
		}

		// Generate mini / overview graphs
		if($save_to_dir)
		{
			$graph = new pts_OverviewGraph($result_file);
			$rendered = $graph->renderGraph();

			// Check to see if skip_graph was realized during the rendering process
			if($rendered)
			{
				$graph->svg_dom->output($save_to_dir . '/result-graphs/visualize.BILDE_EXTENSION');
			}
			unset($graph);

			if($result_file->get_system_count() == 2)
			{
				$graph = new pts_graph_run_vs_run($result_file);
			}
			else
			{
				$graph = new pts_graph_radar_chart($result_file);
			}

			$rendered = $graph->renderGraph();

			// Check to see if skip_graph was realized during the rendering process
			if($rendered)
			{
				$graph->svg_dom->output($save_to_dir . '/result-graphs/radar.BILDE_EXTENSION');
			}
			unset($graph);
		}

		return $generated_graphs;
	}
}

?>
