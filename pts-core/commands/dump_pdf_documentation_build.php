<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010, Phoronix Media
	Copyright (C) 2010, Michael Larabel

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

class dump_pdf_documentation_build implements pts_option_interface
{
	public static function run($r)
	{
		if(is_file("/usr/share/php/fpdf/fpdf.php"))
		{
			include_once("/usr/share/php/fpdf/fpdf.php");
		}
		else
		{
			echo "\nThe FPDF library must be installed.\n\n";
			return;
		}

		$pdf = new pts_pdf_template(pts_title(false), "Client Documentation");

		$pdf->AddPage();
		$pdf->Image(PTS_CORE_STATIC_PATH . "images/pts-308x160.png", 69, 85, 73, 38);
		$pdf->Ln(120);
		$pdf->WriteStatementCenter("www.phoronix-test-suite.com");
		$pdf->Ln(15);
		$pdf->WriteBigHeaderCenter(pts_title(true));
		$pdf->WriteHeaderCenter("User Manual");
		//$pdf->WriteText($result_file->get_description());


		$pdf->AddPage();

		$pts_options = array("Test Installation" => array(), "Testing" => array(), "Batch Testing" => array(), "OpenBenchmarking.org" => array(), "System" => array(), "Information" => array(), "Asset Creation" => array(), "Result Management" => array(), "Result Analytics" => array(), "Other" => array());

		foreach(pts_file_io::glob(PTS_COMMAND_PATH . "*.php") as $option_php_file)
		{
			$option_php = basename($option_php_file, ".php");
			$name = str_replace("_", "-", $option_php);

			if(!in_array(pts_strings::first_in_string($name, '-'), array("dump", "task")))
			{
				include_once($option_php_file);

				$reflect = new ReflectionClass($option_php);
				$constants = $reflect->getConstants();

				$doc_description = isset($constants['doc_description']) ? constant($option_php . '::doc_description') : 'No summary is available.';
				$doc_section = isset($constants['doc_section']) ? constant($option_php . '::doc_section') : 'Other';
				$name = isset($constants['doc_use_alias']) ? constant($option_php . '::doc_use_alias') : $name;
				$doc_args = array();

				if(method_exists($option_php, 'argument_checks'))
				{
					$doc_args = call_user_func(array($option_php, 'argument_checks'));
				}

				if(!empty($doc_section) && !isset($pts_options[$doc_section]))
				{
					$pts_options[$doc_section] = array();
				}

				array_push($pts_options[$doc_section], array($name, $doc_args, $doc_description));
			}
		}

		foreach($pts_options as $section => &$contents)
		{
			if(empty($contents))
			{
				continue;
			}

			$pdf->Ln(7);
			$pdf->WriteHeader($section);
			sort($contents);

			foreach($contents as &$option)
			{
				$pdf->WriteDocHeader($option[0], $option[1]);
				$pdf->WriteDocText($option[2]);
			}
		}

		/*
		if(count($identifiers) > 1 && is_file(PTS_SAVE_RESULTS_PATH . $r[0] . "/result-graphs/overview.jpg"))
		{
			$pdf->AddPage();
			$pdf->Ln(100);
			$pdf->Image(PTS_SAVE_RESULTS_PATH . $r[0] . "/result-graphs/overview.jpg", 15, 40, 180);
		}
		*/

		$pdf_file = pts_client::user_home_directory() . "documentation.pdf";

		$pdf->Output($pdf_file);
		echo "\nSaved To: " . $pdf_file . "\n\n";
	}
}

?>
