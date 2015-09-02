<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2015, Phoronix Media
	Copyright (C) 2009 - 2015, Michael Larabel

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

class analyze_image_delta implements pts_option_interface
{
	const doc_section = 'Result Analytics';
	const doc_description = 'This option will analyze a test result file if it contains any test results that produced an image quality comparison (IQC) and will render image deltas illustrating the difference between images from two test results.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_types', 'is_result_file'), null)
		);
	}
	public static function run($args)
	{
		$result = $args[0];

		$result_file = new pts_result_file($result);
		$result_file_identifiers = $result_file->get_system_identifiers();

		if(count($result_file_identifiers) < 2)
		{
			echo PHP_EOL . 'There are not multiple test runs in this result file.' . PHP_EOL;
			return false;
		}

		$base_identifier = pts_user_io::prompt_text_menu('Select the base test run', $result_file_identifiers);
		$base_select = new pts_result_merge_select($result, $base_identifier);

		$compare_identifier = pts_user_io::prompt_text_menu('Select the test run to compare', $result_file_identifiers);
		$compare_select = new pts_result_merge_select($result, $compare_identifier);

		do
		{
			$extract_to = 'iqc-analyze-' . rand(100, 999);
		}
		while(is_dir(PTS_SAVE_RESULTS_PATH . $extract_to));

		$extract_result = pts_result_file_merger::merge($base_select, $compare_select);
		pts_client::save_test_result($extract_to . '/composite.xml', pts_result_file_writer::result_file_to_xml($result_file));

		$compare_file = new pts_result_file($extract_to);
		$result_file_writer = new pts_result_file_writer('Image Delta');

		foreach($compare_file->get_result_objects() as $result_object)
		{
			if($result_object->test_profile->get_display_format() != 'IMAGE_COMPARISON')
			{
				continue;
			}

			$base_result = null;
			$compare_result = null;

			foreach($result_object->test_result_buffer->get_buffer_items() as $buffer_item)
			{
				if($buffer_item->get_result_identifier() == $base_identifier && $base_result == null)
				{
					$base_result = $buffer_item->get_result_value();
				}
				else if($buffer_item->get_result_identifier() == $compare_identifier && $compare_result == null)
				{
					$compare_result = $buffer_item->get_result_value();
				}

				if($compare_result != null && $base_result != null)
				{
					break;
				}
			}

			if($compare_result == null || $base_result == null)
			{
				continue;
			}

			$base_img = imagecreatefromstring(base64_decode($base_result));
			$compare_img = imagecreatefromstring(base64_decode($compare_result));
			$delta_img = imagecreatefromstring(base64_decode($compare_result));
			$img_width = imagesx($base_img);
			$img_height = imagesy($base_img);
			$img_changed = false;

			for($x = 0; $x < $img_width; $x++)
			{
				for($y = 0; $y < $img_height; $y++)
				{
					$base_image_color = pts_image::rgb_gd_color_at($base_img, $x, $y);
					$compare_image_color = pts_image::rgb_gd_color_at($compare_img, $x, $y);

					if($base_image_color == $compare_image_color || pts_image::rgb_int_diff($base_image_color, $compare_image_color) < 9)
					{
						if(($cords = pts_image::color_pixel_delta($base_img, $compare_img, $x, $y)))
						{
							$pixel_rgb = pts_image::rgb_gd_color_at($delta_img, $cords[0], $cords[1]);
							$color_invert = imagecolorresolve($delta_img, 255 - $pixel_rgb[0], 255 - $pixel_rgb[1], 255 - $pixel_rgb[2]);
							imagesetpixel($delta_img, $x, $y, $color_invert);
							$img_changed = true;
						}
					}
				}
			}

			if($img_changed)
			{
				imagepng($delta_img, PTS_SAVE_RESULTS_PATH . $extract_to . '/scratch.png');
				$result_value = base64_encode(file_get_contents(PTS_SAVE_RESULTS_PATH . $extract_to . '/scratch.png', FILE_BINARY));
				pts_file_io::unlink(PTS_SAVE_RESULTS_PATH . $extract_to . '/scratch.png');
				$result_file_writer->add_result_from_result_object_with_value_string($result_object, $result_value);
			}
		}

		pts_client::save_result_file($result_file_writer, $extract_to);
		pts_client::display_web_page(PTS_SAVE_RESULTS_PATH . $extract_to . '/composite.xml');
	}
}

?>
