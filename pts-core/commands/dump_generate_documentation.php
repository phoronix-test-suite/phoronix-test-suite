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

class dump_generate_documentation implements pts_option_interface
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

		$pdf = new pts_pdf_template(pts_title(false), "Test Client Documentation");

		$pdf->AddPage();
		$pdf->Image(PTS_CORE_STATIC_PATH . "images/pts-308x160.png", 69, 85, 73, 38, 'PNG', 'http://www.phoronix-test-suite.com/');
		$pdf->Ln(120);
		$pdf->WriteStatement("www.phoronix-test-suite.com", 'C', 'http://www.phoronix-test-suite.com/');
		$pdf->Ln(15);
		$pdf->WriteBigHeaderCenter(pts_title(true));
		$pdf->WriteHeaderCenter("User Manual");
		//$pdf->WriteText($result_file->get_description());

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

		// Write the test options HTML
		$dom = new DOMDocument();
		$html = $dom->createElement('html');
		$dom->appendChild($html);
		$head = $dom->createElement('head');
		$title = $dom->createElement('title', "User Options");
		$head->appendChild($title);
		$html->appendChild($head);
		$body = $dom->createElement('body');
		$html->appendChild($body);

		$p = $dom->createElement('p', "The following options are currently supported by the Phoronix Test Suite client. A list of available options can also be found by running ");
		$em = $dom->createElement('em', 'phoronix-test-suite help.');
		$p->appendChild($em);
		$phr = $dom->createElement('hr');
		$p->appendChild($phr);
		$body->appendChild($p);

		foreach($pts_options as $section => &$contents)
		{
			if(empty($contents))
			{
				continue;
			}

			$header = $dom->createElement('h1', $section);
			$body->appendChild($header);

			sort($contents);
			foreach($contents as &$option)
			{
				$sub_header = $dom->createElement('h3', $option[0]);
				$em = $dom->CreateElement('em', '  ' . implode(' ', $option[1]));
				$sub_header->appendChild($em);

				$body->appendChild($sub_header);

				$p = $dom->createElement('p', $option[2]);
				$body->appendChild($p);
			}
		}

		echo $dom->saveHTMLFile(PTS_PATH . "documentation/html_sections/00_user_options.html");

		// Load the HTML documentation
		foreach(pts_file_io::glob(PTS_PATH . "documentation/html_sections/*_*.html") as $html_file)
		{
			$pdf->html_to_pdf($html_file);
		}

		if(!is_writable(PTS_PATH . 'documentation/'))
		{
			echo "\nNot writable: " . PTS_PATH . 'documentation/';
		}
		else
		{
			$pdf_file = PTS_PATH . 'documentation/phoronix-test-suite.pdf';
			$pdf->Output($pdf_file);
			echo "\nSaved To: " . $pdf_file . "\n\n";

			// Also re-generate the man page
			$man_page = ".TH phoronix-test-suite 1  \"www.phoronix-test-suite.com\" \"" . PTS_VERSION . "\"\n.SH NAME\n";
			$man_page .= "phoronix-test-suite \- The Phoronix Test Suite is an extensible open-source platform for performing testing and performance evaluation.\n";
			$man_page .= ".SH SYNOPSIS\n.B phoronix-test-suite [options]\n.br\n.B phoronix-test-suite benchmark [test | suite]\n";
			$man_page .= ".SH DESCRIPTION\nThe Phoronix Test Suite is the most comprehensive testing and benchmarking platform available that provides an extensible framework for which new tests can be easily added. The software is designed to effectively carry out both qualitative and quantitative benchmarks in a clean, reproducible, and easy-to-use manner.\n";
			$man_page .= ".SH OPTIONS\n.TP\n";

			foreach($pts_options as $section => &$contents)
			{
				if(empty($contents))
				{
					continue;
				}

				$man_page .= '.SH ' . strtoupper($section) . "\n";

				sort($contents);
				foreach($contents as &$option)
				{
					$man_page .= '.B ' . trim($option[0] . ' ' . implode(' ', $option[1])) . "\n" . $option[2] . "\n.TP\n";
				}
			}
			$man_page .= ".SH SEE ALSO\n.B Websites:\n.br\nhttp://www.phoronix-test-suite.com/\n.br\nhttp://commercial.phoronix-test-suite.com/\n.br\nhttp://www.openbenchmarking.org/\n.br\nhttp://www.phoronix.com/\n.br\nhttp://www.phoronix.com/forums/\n";
			$man_page .= ".SH AUTHORS\nCopyright 2008 - " . date('Y') . " by Phoronix Media, Michael Larabel.\n.TP\n";

			file_put_contents(PTS_PATH . "documentation/man-pages/phoronix-test-suite.1", $man_page);
		}
	}
}

?>
