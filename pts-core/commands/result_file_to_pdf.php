<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2010, Phoronix Media
	Copyright (C) 2009 - 2010, Michael Larabel

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
	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array("pts_types", "is_result_file"), null, "No result file was found.")
		);
	}
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

		define("BILDE_RENDERER", "PNG"); // Force to PNG renderer
		define("BILDE_IMAGE_INTERLACING", false); // Otherwise FPDF will fail
		pts_render::generate_result_file_graphs($r[0], PTS_SAVE_RESULTS_PATH . $r[0] . "/");

		$result_file = new pts_result_file($r[0]);
		$pdf = new pts_pdf_template($result_file->get_title(), null);

		$pdf->AddPage();
		$pdf->Image(PTS_CORE_STATIC_PATH . "images/pts-308x160.png", 69, 85, 73, 38);
		$pdf->Ln(120);
		$pdf->WriteStatementCenter("www.phoronix-test-suite.com");
		$pdf->Ln(15);
		$pdf->WriteBigHeaderCenter($result_file->get_title());
		$pdf->WriteText($result_file->get_suite_description());


		$pdf->AddPage();
		$pdf->Ln(15);

		$identifiers = $result_file->get_system_identifiers();
		$hardware_r = $result_file->get_system_hardware();
		$software_r = $result_file->get_system_software();
		$notes_r = $result_file->get_system_notes();
		$tests = $result_file->get_test_titles();

		$pdf->SetSubject($result_file->get_title() . " Benchmarks");
		$pdf->SetKeywords(implode(", ", $identifiers));

		$pdf->WriteHeader("Test Systems:");
		for($i = 0; $i < count($identifiers); $i++)
		{
			$pdf->WriteMiniHeader($identifiers[$i]);
			$pdf->WriteText($hardware_r[$i]);
			$pdf->WriteText($software_r[$i]);
			$pdf->WriteText($notes_r[$i]);
		}

		/*
		if(count($identifiers) > 1 && is_file(PTS_SAVE_RESULTS_PATH . $r[0] . "/result-graphs/overview.jpg"))
		{
			$pdf->AddPage();
			$pdf->Ln(100);
			$pdf->Image(PTS_SAVE_RESULTS_PATH . $r[0] . "/result-graphs/overview.jpg", 15, 40, 180);
		}
		*/


		$pdf->AddPage();
		$placement = 1;
		for($i = 1; $i <= count($tests); $i++)
		{
			if(is_file(PTS_SAVE_RESULTS_PATH . $r[0] . "/result-graphs/" . $i . ".png"))
			{
				$pdf->Ln(100);
				$pdf->Image(PTS_SAVE_RESULTS_PATH . $r[0] . "/result-graphs/" . $i . ".png", 20, 40 + (($placement - 1) * 120), 178);
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


		// To save:
		/*
		$pdf_file = "SAVE_TO";

		if(substr($pdf_file, -4) != ".pdf")
		{
			$pdf_file .= ".pdf";
		}
		*/
		$pdf_file = pts_client::user_home_directory() . $r[0] . ".pdf";

		$pdf->Output($pdf_file);
		echo "\nSaved To: " . $pdf_file . "\n\n";
	}
}

?>
