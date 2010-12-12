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
		$pdf->Image(PTS_CORE_STATIC_PATH . "images/pts-308x160.png", 69, 85, 73, 38, 'PNG', 'http://www.phoronix-test-suite.com/');
		$pdf->Ln(120);
		$pdf->WriteStatement("www.phoronix-test-suite.com", 'C', 'http://www.phoronix-test-suite.com/');
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

		$pdf->WriteBigHeader("User Options");

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

		// Load the HTML documentation
		foreach(pts_file_io::glob(PTS_PATH . "documentation/html/*_*.html") as $html_file)
		{
			$dom = new DOMDocument();
			$dom->loadHTMLFile($html_file);
			$html_file = $dom->getElementsByTagName('html')->item(0)->getElementsByTagName("head")->item(0)->nodeValue;
			$tags = $dom->getElementsByTagName('html')->item(0)->getElementsByTagName("body")->item(0)->childNodes;

			$pdf->AddPage();
			$pdf->WriteBigHeader($html_file);
			for($i = 0; $i < $tags->length; $i++)
			{
				$value = $tags->item($i)->nodeValue;

				switch($tags->item($i)->nodeName)
				{
					case 'h1':
						$pdf->WriteHeader($value);
						break;
					case 'h2':
						$pdf->Ln();
						$pdf->WriteMiniHeader($value);
						break;
					case 'p':
						$pdf->SetFont("Arial", null, 11);
						for($j = 0; $j < $tags->item($i)->childNodes->length; $j++)
						{
							$value = $tags->item($i)->childNodes->item($j)->nodeValue;
							$name = $tags->item($i)->childNodes->item($j)->nodeName;

							switch($name)
							{
								case 'em':
									$pdf->SetFont(null, 'I');
									break;
								case 'strong':
									$pdf->SetFont(null, 'B');
									break;
								case '#text':
									$pdf->SetFont(null, null);
									break;
								case 'a':
									$pdf->SetTextColor(0, 0, 255);
									$pdf->SetFont(null, 'BU');
									$pdf->Write(5, $value, $tags->item($i)->childNodes->item($j)->attributes->getNamedItem('href')->nodeValue);
									$pdf->SetTextColor(0, 0, 0);
									break;
								default:
									echo "UNSUPPORTED: $name\n";
									break;
							}

							if($name != 'a')
							{
								$pdf->Write(5, $value, null);
							}
							//$pdf->Ln();
						}
						$pdf->Ln(7);
						break;
				}
			}
		}


		$pdf_file = pts_client::user_home_directory() . "documentation.pdf";
		$pdf->Output($pdf_file);
		echo "\nSaved To: " . $pdf_file . "\n\n";
	}
}

?>
