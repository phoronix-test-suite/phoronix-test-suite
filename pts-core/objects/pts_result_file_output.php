<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2018, Phoronix Media
	Copyright (C) 2010 - 2018, Michael Larabel

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

class pts_result_file_output
{
	public static function result_file_to_json(&$result_file)
	{
		$json = array();
		$json['title'] = $result_file->get_title();

		$json['results'] = array();
		foreach($result_file->get_result_objects() as $result_object)
		{
			$json['results'][$result_object->test_profile->get_identifier()] = array(
				'arguments' => $result_object->get_arguments_description(),
				'units' => $result_object->test_profile->get_result_scale(),
				);

			foreach($result_object->test_result_buffer as &$buffers)
			{
				foreach($buffers as &$buffer)
				{
					$json['results'][$result_object->test_profile->get_identifier()]['results'][$buffer->get_result_identifier()] = array(
						'value' => $buffer->get_result_value(),
						'all_results' => $buffer->get_result_raw()
						);
				}
			}
		}

		return json_encode($json, JSON_PRETTY_PRINT);
	}
	public static function result_file_to_csv(&$result_file)
	{
		$csv_output = null;
		$delimiter = ',';

		$csv_output .= $result_file->get_title() . PHP_EOL . PHP_EOL;

		$columns = array();
		$hw = array();
		$sw = array();
		foreach($result_file->get_systems() as $system)
		{
			$columns[] = $system->get_identifier();
			$hw[] = $system->get_hardware();
			$sw[] = $system->get_software();
		}
		$rows = array();
		$table_data = array();

		pts_result_file_analyzer::system_components_to_table($table_data, $columns, $rows, $hw);
		pts_result_file_analyzer::system_components_to_table($table_data, $columns, $rows, $sw);

		$csv_output .= ' ';

		foreach($columns as $column)
		{
			$csv_output .= $delimiter . '"' . $column . '"';
		}
		$csv_output .= PHP_EOL;

		foreach($rows as $i => $row)
		{
			$csv_output .= $row;

			foreach($columns as $column)
			{
				$csv_output .= $delimiter . (isset($table_data[$column][$i]) ? $table_data[$column][$i] : null);
			}

			$csv_output .= PHP_EOL;
		}

		$csv_output .= PHP_EOL;
		$csv_output .= ' ' . $delimiter;

		foreach($columns as $column)
		{
			$csv_output .= $delimiter . '"' . $column . '"';
		}
		$csv_output .= PHP_EOL;

		foreach($result_file->get_result_objects() as $result_object)
		{
			if(getenv('PTS_CSV_ALTERNATE_DESCRIPTION') !== false)
			{
				$csv_output .= '"' . $result_object->test_profile->get_identifier() . ' - ' . $result_object->get_arguments() . '"';
			}
			else
			{
				$csv_output .= '"' . $result_object->test_profile->get_identifier() . ' - ' . $result_object->get_arguments() . '"';
			}

			$csv_output .= $delimiter . $result_object->test_profile->get_result_proportion();

			foreach($columns as $column)
			{
				$buffer_item = $result_object->test_result_buffer->find_buffer_item($column);
				$value = $buffer_item != false ? $buffer_item->get_result_value() : null;
				$csv_output .= $delimiter . $value;
			}
			$csv_output .= PHP_EOL;
		}
		$csv_output .= PHP_EOL;

		return $csv_output;
	}
	public static function result_file_to_text(&$result_file, $terminal_width = 80)
	{
		$result_output = null;

		$result_output .= $result_file->get_title() . PHP_EOL;
		$result_output .= $result_file->get_description() . PHP_EOL . PHP_EOL . PHP_EOL;

		$system_identifiers = array();
		$system_hardware = array();
		$system_software = array();
		foreach($result_file->get_systems() as $system)
		{
			$system_identifiers[] = $system->get_identifier();
			$system_hardware[] = $system->get_hardware();
			$system_software[] = $system->get_software();
		}

		for($i = 0; $i < count($system_identifiers); $i++)
		{
			$result_output .= $system_identifiers[$i] . ': ' . PHP_EOL . PHP_EOL;
			$result_output .= "\t" . $system_hardware[$i] . PHP_EOL . PHP_EOL . "\t" . $system_software[$i] . PHP_EOL . PHP_EOL;
		}

		foreach($result_file->get_result_objects() as $result_object)
		{
			$result_output .= trim($result_object->test_profile->get_title() . ' ' . $result_object->test_profile->get_app_version() . PHP_EOL . $result_object->get_arguments_description());
			$result_output .= self::test_result_to_text($result_object, $terminal_width);
			$result_output .= PHP_EOL . PHP_EOL;
		}

		return $result_output;
	}
	public static function test_result_to_text(&$result_object, $terminal_width = 80, $color_output = false, $highlight_result = null)
	{
		$result_output = null;

		if($result_object->test_profile->get_result_scale() != null)
		{
			$result_output .= PHP_EOL . '    ' . $result_object->test_profile->get_result_scale();
			if($result_object->test_profile->get_result_proportion() == 'LIB')
			{
				$result_output .= ' < Lower Is Better';
			}
			else if($result_object->test_profile->get_result_proportion() == 'HIB')
			{
				$result_output .= ' > Higher Is Better';
			}
		}

		$identifiers = $result_object->test_result_buffer->get_identifiers();
		$longest_identifier_length = strlen(pts_strings::find_longest_string($identifiers)) + 2;

		foreach($result_object->test_result_buffer as &$buffers)
		{
			if(empty($buffers))
				continue;

			$max_value = 0;
			$min_value = pts_arrays::first_element($buffers)->get_result_value();
			foreach($buffers as &$buffer_item)
			{
				if($buffer_item->get_result_value() > $max_value)
				{
					$max_value = $buffer_item->get_result_value();
				}
				else if($buffer_item->get_result_value() < $min_value)
				{
					$min_value = $buffer_item->get_result_value();
				}
			}

			$longest_result = strlen($max_value) + 1;
			foreach($buffers as &$buffer_item)
			{
				$val = $buffer_item->get_result_value();

				if(stripos($val, ',') !== false)
				{
					$vals = explode(',', $val);
					$val = 'MIN: ' . min($vals) . ' / AVG: ' . round(array_sum($vals) / count($vals), 2) . ' / MAX: ' . max($vals);
				}

				$result_line = '    ' . $buffer_item->get_result_identifier() . ' ';
				$result_length_offset = $longest_identifier_length - strlen($buffer_item->get_result_identifier());
				if($result_length_offset > 0)
				{
					$result_line .= str_repeat('.', $result_length_offset) . ' ';
				}
				$result_line .= $val;

				if(is_numeric($val))
				{
					$repeat_length = $longest_result - strlen($val);
					$result_line .= ($repeat_length >= 0 ? str_repeat(' ', $repeat_length) : null)  . '|';
					$current_line_length = strlen($result_line);
					$result_line .= str_repeat('=', round(($val / $max_value) * ($terminal_width - $current_line_length)));
				}

				if($color_output && PTS_IS_CLIENT)
				{
					if($highlight_result == $buffer_item->get_result_identifier())
						$result_line = pts_client::cli_just_bold($result_line);
					else if(is_array($highlight_result) && in_array($buffer_item->get_result_identifier(), $highlight_result))
						$result_line = pts_client::cli_just_bold($result_line);
				}

				$result_output .= PHP_EOL . $result_line;
			}
		}
		return $result_output;
	}
	public static function result_file_to_pdf(&$result_file, $dest, $output_name, $extra_attributes = null)
	{
		ob_start();
		$_REQUEST['force_format'] = 'JPEG'; // Force to PNG renderer
		$_REQUEST['svg_dom_gd_no_interlacing'] = true; // Otherwise FPDF will fail
		$pdf = new pts_pdf_template($result_file->get_title(), null);

		$pdf->AddPage();
		$pdf->Image(PTS_CORE_STATIC_PATH . 'images/pts-308x160.png', 69, 85, 73, 38);
		$pdf->Ln(120);
		$pdf->WriteStatementCenter('www.phoronix-test-suite.com');
		$pdf->Ln(15);
		$pdf->WriteBigHeaderCenter($result_file->get_title());
		$pdf->WriteText($result_file->get_description());

		$pdf->AddPage();
		$pdf->Ln(15);

		$pdf->SetSubject($result_file->get_title() . ' Benchmarks');
		//$pdf->SetKeywords(implode(', ', $identifiers));

		$pdf->WriteHeader('Test Systems:');
		foreach($result_file->get_systems() as $s)
		{
			$pdf->WriteMiniHeader($s->get_identifier());
			$pdf->WriteText($s->get_hardware());
			$pdf->WriteText($s->get_software());
		}

		$pdf->AddPage();

		$placement = 1;
		$i = 0;
		foreach($result_file->get_result_objects() as $key => $result_object)
		{
			$graph = pts_render::render_graph_process($result_object, $result_file, false, $extra_attributes);
			if($graph == false)
			{
				continue;
			}

			$graph->renderGraph();
			$output = $graph->svg_dom->output(null);
			$pdf->Ln(100);
			$pdf->ImageJPGInline($output, 50, 40 + (($placement - 1) * 120), 120);

			if($placement == 2 || $result_object->test_result_buffer->get_count() > 12)
			{
				$placement = 0;
				$pdf->AddPage();
			}
			$placement++;
			$i++;
		}
		$pdf->WriteText('This file was automatically generated via the Phoronix Test Suite open-source benchmarking software.');
		ob_get_clean();
		$pdf->Output($dest, $output_name);
	}
}

?>
