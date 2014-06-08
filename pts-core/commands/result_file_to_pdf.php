<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2011, Phoronix Media
	Copyright (C) 2009 - 2011, Michael Larabel

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

class result_file_to_pdf implements pts_option_interface
{
	const doc_section = 'Result Management';
	const doc_description = 'This option will read a saved test results file and output the system hardware and software information along with the results to a PDF file.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_types', 'is_result_file'), null)
		);
	}
	public static function run($r)
	{
		if(is_file('/usr/share/php/fpdf/fpdf.php'))
		{
			include_once('/usr/share/php/fpdf/fpdf.php');
		}
		else
		{
			echo PHP_EOL . 'The FPDF library must be installed.' . PHP_EOL;
			return false;
		}

		$_REQUEST['force_format'] = 'PNG'; // Force to PNG renderer
		$_REQUEST['svg_dom_gd_no_interlacing'] = true; // Otherwise FPDF will fail
		pts_client::generate_result_file_graphs($r[0], PTS_SAVE_RESULTS_PATH . $r[0] . '/');

		$result_file = new pts_result_file($r[0]);
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

		$identifiers = $result_file->get_system_identifiers();
		$hardware_r = $result_file->get_system_hardware();
		$software_r = $result_file->get_system_software();
		$tests = $result_file->get_test_titles();

		$pdf->SetSubject($result_file->get_title() . ' Benchmarks');
		$pdf->SetKeywords(implode(', ', $identifiers));

		$pdf->WriteHeader('Test Systems:');
		for($i = 0; $i < count($identifiers); $i++)
		{
			$pdf->WriteMiniHeader($identifiers[$i]);
			$pdf->WriteText($hardware_r[$i]);
			$pdf->WriteText($software_r[$i]);
		}

		$pdf->AddPage();
		$placement = 1;
		for($i = 1; $i <= count($tests); $i++)
		{
			if(is_file(PTS_SAVE_RESULTS_PATH . $r[0] . '/result-graphs/' . $i . '.png'))
			{
				$pdf->Ln(100);
				$pdf->Image(PTS_SAVE_RESULTS_PATH . $r[0] . '/result-graphs/' . $i . '.png', 50, 40 + (($placement - 1) * 120), 120);
			}

			if($placement == 2)
			{
				$placement = 0;

				if($i != count($tests))
				{
					$pdf->AddPage();
				}
			}
			$placement++;
		}

		$pdf_file = pts_client::user_home_directory() . $r[0] . '.pdf';

		$pdf->Output($pdf_file);
		echo PHP_EOL . 'Saved To: ' . $pdf_file . PHP_EOL;
	}
}

?>
