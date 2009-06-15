<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel

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

class result_file_to_ps implements pts_option_interface
{
	public static function run($r)
	{
		echo pts_string_header("Result File To PostScript Converter");

		if(!extension_loaded("ps") || !function_exists("ps_new"))
		{
			echo "\nThe PS extension for PHP does not appear to be installed.\n\n";
			return;
		}

		putenv("JPG_DEBUG=true"); // Force to JPEG mode
		pts_generate_graphs($r[0], SAVE_RESULTS_DIR . $r[0] . "/");

		$xml_parser = new pts_results_tandem_XmlReader($saved_results_file);
		$ps = ps_new();
		$page_width = 596;
		$page_height = 842;

		ps_set_info("Title", $xml_parser->getXMLValue(P_RESULTS_SUITE_TITLE));
		ps_set_info("Creator", pts_codename(true));
		ps_set_info("Author", "Phoronix Test Suite");
		//ps_set_info("Keywords", "Phoronix Test Suite");
		//ps_set_info("Subject", "Phoronix Test Suite");

		ps_begin_page($ps, $page_width, $page_height);
		$pdf->Image(STATIC_DIR . "pts-308x160.png", 69, 85, 73, 38);
		$pdf->Ln(120);
		$pdf->WriteStatementCenter("www.phoronix-test-suite.com");
		$pdf->Ln(15);
		$pdf->WriteBigHeaderCenter($xml_parser->getXMLValue(P_RESULTS_SUITE_TITLE));
		$pdf->WriteText($xml_parser->getXMLValue(P_RESULTS_SUITE_DESCRIPTION));


		$pdf->AddPage();
		$pdf->Ln(15);

		$identifiers = $xml_parser->getXMLArrayValues(P_RESULTS_SYSTEM_IDENTIFIERS);
		$hardware_r = $xml_parser->getXMLArrayValues(P_RESULTS_SYSTEM_HARDWARE);
		$software_r = $xml_parser->getXMLArrayValues(P_RESULTS_SYSTEM_SOFTWARE);
		$notes_r = $xml_parser->getXMLArrayValues(P_RESULTS_SYSTEM_NOTES);
		//$date_r = $xml_parser->getXMLArrayValues(P_RESULTS_SYSTEM_DATE);
		$tests = $xml_parser->getXMLArrayValues(P_RESULTS_TEST_TITLE);

		$pdf->SetSubject($xml_parser->getXMLValue(P_RESULTS_SUITE_TYPE) . " Benchmarks");
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
		if(count($identifiers) > 1 && is_file(SAVE_RESULTS_DIR . $r[0] . "/result-graphs/overview.jpg"))
		{
			$pdf->AddPage();
			$pdf->Ln(100);
			$pdf->Image(SAVE_RESULTS_DIR . $r[0] . "/result-graphs/overview.jpg", 15, 40, 180);
		}
		*/


		$pdf->AddPage();
		$placement = 1;
		for($i = 1; $i <= count($tests); $i++)
		{
			if(is_file(SAVE_RESULTS_DIR . $r[0] . "/result-graphs/" . $i . ".jpg"))
			{
				$pdf->Ln(100);
				$pdf->Image(SAVE_RESULTS_DIR . $r[0] . "/result-graphs/" . $i . ".jpg", 20, 40 + (($placement - 1) * 120), 180);
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

		if(pts_is_assignment("SAVE_TO"))
		{
			$pdf_file = pts_read_assignment("SAVE_TO");

			if(substr($pdf_file, -4) != ".pdf")
			{
				$pdf_file .= ".pdf";
			}
		}
		else
		{
			$pdf_file = pts_user_home() . $r[0] . ".pdf";
		}

		$pdf->Output($pdf_file);
		pts_set_assignment_next("PREV_PDF_FILE", $pdf_file);
		echo "\nSaved To: " . $pdf_file . "\n\n";
	}
}

?>
