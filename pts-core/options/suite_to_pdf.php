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

class suite_to_pdf implements pts_option_interface
{
	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, "pts_is_suite", null, "No suite found.")
		);
	}
	public static function run($r)
	{
		echo pts_string_header("Suite To PDF Report Creator");

		if(is_file("/usr/share/php/fpdf/fpdf.php"))
		{
			include_once("/usr/share/php/fpdf/fpdf.php");
		}
		else
		{
			echo "\nThe FPDF library must be installed.\n\n";
			return;
		}

		if(isset($r[1]))
		{
			// TODO: Simple hack for now
			pts_set_assignment("SHOW_NODE", $r[1]);
		}

		$suite = new pts_test_suite($r[0]);
		$test_layout = pts_test_suite::pts_format_tests_to_array($r[0]);
		$pdf = new pts_pdf_template($suite->get_name(), $suite->get_name());

		$pdf->AddPage();
		$pdf->Image(STATIC_DIR . "images/pts-308x160.png", 69, 85, 73, 38);
		$pdf->Ln(120);
		$pdf->WriteStatementCenter("www.phoronix-test-suite.com");
		$pdf->Ln(15);
		$pdf->WriteBigHeaderCenter($suite->get_name());
		$pdf->WriteText("Maintainer: " . $suite->get_maintainer() . ". Suite Type: " . $suite->get_suite_type());
		$pdf->WriteText($suite->get_description());

		$pdf->AddPage();
		$pdf->Ln(15);

		self::layout_to_pdf($test_layout, $pdf);

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
			$pdf_file = pts_client::user_home_directory() . $r[0] . ".pdf";
		}

		$pdf->Output($pdf_file);
		pts_set_assignment_next("PREV_PDF_FILE", $pdf_file);
		echo "\nSaved To: " . $pdf_file . "\n\n";
	}
	protected static function layout_to_pdf($test_layout, &$pdf)
	{
		$show_node = pts_read_assignment("SHOW_NODE");

		foreach($test_layout as $key => $item)
		{
			if(is_array($item))
			{
				if(!is_numeric($key))
				{
					// TODO: work around bug with array keys showing
					$pdf->WriteHeader($key);
					$suite = new pts_test_suite($key);
					$pdf->WriteText($suite->get_description());
				}
				self::layout_to_pdf($item, &$pdf);
			}
			else
			{
				$pdf->WriteMiniHeader($item);

				$test = new pts_test_profile($item);

				$pdf->WriteText($test->get_test_title() . ":  " . $test->get_description());

				if($show_node)
				{
					$test_xml = new pts_test_tandem_XmlReader($item);
					$pdf->WriteText($test_xml->getXMLValue($show_node));
				}
			}
		}
	}
}

?>
