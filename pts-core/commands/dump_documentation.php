<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2022, Phoronix Media
	Copyright (C) 2010 - 2022, Michael Larabel

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

class dump_documentation implements pts_option_interface
{
	const doc_section = 'Asset Creation';
	const doc_description = 'This option is used for re-generating the Phoronix Test Suite documentation.';
	public static function run($r)
	{
		$pdf = new pts_pdf_template(pts_core::program_title(), 'Test Client Documentation');
		$html_doc = new pts_html_template(pts_core::program_title(), 'Test Client Documentation');

		$pdf->AddPage();
		$pdf->Image(PTS_CORE_STATIC_PATH . 'images/pts-308x160.png', 69, 85, 73, 38, 'PNG', 'https://www.phoronix-test-suite.com/');
		$pdf->Ln(120);
		$pdf->WriteStatement('www.phoronix-test-suite.com', 'C', 'https://www.phoronix-test-suite.com/');
		$pdf->Ln(15);
		$pdf->WriteBigHeaderCenter(pts_core::program_title());
		$pdf->WriteHeaderCenter('User Manual');
		//$pdf->WriteText($result_file->get_description());

		$pts_options = pts_documentation::client_commands_array();

		// Write the test options HTML
		$dom = new DOMDocument();
		$dom->formatOutput = true;
		$html = $dom->createElement('html');
		$dom->appendChild($html);
		$head = $dom->createElement('head');
		$title = $dom->createElement('title', 'User Options');
		$head->appendChild($title);
		$html->appendChild($head);
		$body = $dom->createElement('body');
		$html->appendChild($body);

		$p = $dom->createElement('p', 'The following options are currently supported by the Phoronix Test Suite client. A list of available options can also be found by running ');
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
				if(!empty($option[1]))
				{
					$em = $dom->CreateElement('em', '  ' . implode(' ', $option[1]));
					$sub_header->appendChild($em);

				}
				$body->appendChild($sub_header);

				$p = $dom->createElement('p', $option[2]);
				$body->appendChild($p);
			}
		}

		$dom->saveHTMLFile(PTS_PATH . 'documentation/stubs/00_user_options.html');

		// Write the module options HTML
		$dom = new DOMDocument();
		$dom->formatOutput = true;
		$html = $dom->createElement('html');
		$dom->appendChild($html);
		$head = $dom->createElement('head');
		$title = $dom->createElement('title', 'Module Options');
		$head->appendChild($title);
		$html->appendChild($head);
		$body = $dom->createElement('body');
		$html->appendChild($body);

		$p = $dom->createElement('p', 'The following list is the modules included with the Phoronix Test Suite that are intended to extend the functionality of pts-core. Some of these options have commands that can be run directly in a similar manner to the other Phoronix Test Suite user commands. Some modules are just meant to be loaded directly by adding the module name to the AutoLoadModules tag in ~/.phoronix-test-suite/user-config.xml or via the PTS_MODULES environment variable. A list of available modules is also available by running ');
		$em = $dom->createElement('em', 'phoronix-test-suite list-modules.');
		$p->appendChild($em);
		$phr = $dom->createElement('hr');
		$p->appendChild($phr);
		$body->appendChild($p);

		foreach(pts_module_manager::available_modules(true) as $module)
		{
			pts_module_manager::load_module($module);

			$header = $dom->createElement('h2', pts_module_manager::module_call($module, 'module_name'));
			$body->appendChild($header);

			$desc = $dom->createElement('p', pts_module_manager::module_call($module, 'module_description'));
			$body->appendChild($desc);

			$all_options = pts_module_manager::module_call($module, 'user_commands');
			if(count($all_options) > 0)
			{
			//	$sub_header = $dom->createElement('h3', 'Module Commands');
			//	$body->appendChild($sub_header);

				foreach($all_options as $key => $option)
				{
					$p = $dom->createElement('p');
					$strong = $dom->createElement('strong', 'phoronix-test-suite ' . $module . '.' . str_replace('_', '-', $key));
					$p->appendChild($strong);
					$body->appendChild($p);
				}
			}

			$vars = pts_module_manager::module_call($module, 'module_environment_variables');
			if(is_array($vars) && count($vars) > 0)
			{
				$p = $dom->createElement('p', 'This module utilizes the following environment variables: ' . implode(', ', $vars) . '.');
				$body->appendChild($p);
			}

            // If there is module_info text defined, add it here.
		$moduleInfoText = pts_module_manager::module_call($module, 'module_info');
            if ( $moduleInfoText ) {
            	$moduleInfoText = trim($moduleInfoText);
                $moduleInfo = $dom->createElement('p', $moduleInfoText);
                $body->appendChild($moduleInfo);
            }
		}

		$dom->saveHTMLFile(PTS_PATH . 'documentation/stubs/00_zmodule_options.html');



		// Write the external dependencies HTML
		$dom = new DOMDocument();
		$dom->formatOutput = true;
		$html = $dom->createElement('html');
		$dom->appendChild($html);
		$head = $dom->createElement('head');
		$title = $dom->createElement('title', 'External Dependencies');
		$head->appendChild($title);
		$html->appendChild($head);
		$body = $dom->createElement('body');
		$html->appendChild($body);

		$p = $dom->createElement('p', 'The Phoronix Test Suite has a feature known as &quot;External Dependencies&quot; where the Phoronix Test Suite can attempt to automatically install some of the test-specific dependencies on supported distributions. If running on a distribution where there is currently no External Dependencies profile, the needed package name(s) are listed for manual installation.');
		$body->appendChild($p);
		$p = $dom->createElement('p', 'Below are a list of the operating systems that currently have external dependencies support within the Phoronix Test Suite for the automatic installation of needed test files.');
		$body->appendChild($p);

		$phr = $dom->createElement('hr');
		$p->appendChild($phr);

		$exdep_generic_parser = new pts_exdep_generic_parser();
		$vendors = array_merge($exdep_generic_parser->get_vendor_aliases_formatted(), $exdep_generic_parser->get_vendors_list_formatted());
		sort($vendors);

		$ul = $dom->createElement('ul');
		$p->appendChild($ul);

		foreach($vendors as $vendor)
		{
			$li = $dom->createElement('li', $vendor);
			$p->appendChild($li);
		}


		$dom->saveHTMLFile(PTS_PATH . 'documentation/stubs/02_external_dependencies.html');

		// Write the virtual suites HTML
		$dom = new DOMDocument();
		$dom->formatOutput = true;
		$html = $dom->createElement('html');
		$dom->appendChild($html);
		$head = $dom->createElement('head');
		$title = $dom->createElement('title', 'Virtual Test Suites');
		$head->appendChild($title);
		$html->appendChild($head);
		$body = $dom->createElement('body');
		$html->appendChild($body);

		$p = $dom->createElement('p', 'Virtual test suites are not like a traditional test suite defined by the XML suite specification. Virtual test suites are dynamically generated in real-time by the Phoronix Test Suite client based upon the specified test critera. Virtual test suites can automatically consist of all test profiles that are compatible with a particular operating system or test profiles that meet other critera. When running a virtual suite, the OpenBenchmarking.org repository of the test profiles to use for generating the dynamic suite must be prefixed. ');
		$body->appendChild($p);

		$p = $dom->createElement('p', 'Virtual test suites can be installed and run just like a normal XML test suite and shares nearly all of the same capabilities. However, when running a virtual suite, the user will be prompted to input any user-configuration options for needed test profiles just as they would need to do if running the test individually. When running a virtual suite, the user also has the ability to select individual tests within the suite to run or to run all of the contained test profiles. Virtual test suites are also only supported for an OpenBenchmarking.org repository if there is no test profile or test suite of the same name in the repository. Below is a list of common virtual test suites for the main Phoronix Test Suite repository, but the dynamic list of available virtual test suites based upon the enabled repositories is available by running ');
		$em = $dom->createElement('em', 'phoronix-test-suite list-available-virtual-suites.');
		$p->appendChild($em);
		$phr = $dom->createElement('hr');
		$p->appendChild($phr);
		$body->appendChild($p);

		foreach(pts_virtual_test_suite::available_virtual_suites() as $virtual_suite)
		{
			$sub_header = $dom->createElement('h3', $virtual_suite->get_title());
			$em = $dom->CreateElement('em', '  ' . $virtual_suite->get_identifier());
			$sub_header->appendChild($em);
			$body->appendChild($sub_header);

			$p = $dom->createElement('p', $virtual_suite->get_description());
			$body->appendChild($p);
		}

		$dom->saveHTMLFile(PTS_PATH . 'documentation/stubs/55_virtual_suites.html');

		$env_var_html = '<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Environment Variables</title>
</head>
<body>
' . pts_env::get_documentation(false) . '
</body>
</html>';
		file_put_contents(PTS_PATH . 'documentation/stubs/42_env_vars.html', $env_var_html);

		// Load the HTML documentation
		$md = new pts_md_template();
		foreach(pts_file_io::glob(PTS_PATH . 'documentation/stubs/*_*.html') as $html_file)
		{
			$pdf->html_to_pdf($html_file);
			$html_doc->html_to_html($html_file);
			$md->html_to_md($html_file);
		}

		if(!is_writable(PTS_PATH . 'documentation/'))
		{
			echo PHP_EOL . 'Not writable: ' . PTS_PATH . 'documentation/';
		}
		else
		{
			$pdf_file = PTS_PATH . 'documentation/phoronix-test-suite.pdf';
			$pdf->Output($pdf_file);
			$html_doc->Output(PTS_PATH . 'documentation/phoronix-test-suite.html');
			echo PHP_EOL . 'Saved To: ' . $pdf_file . PHP_EOL . PHP_EOL;

			$md->Output(PTS_PATH . 'documentation/phoronix-test-suite.md');

			// Also re-generate the man page
			$man_page = '.TH phoronix-test-suite 1  "www.phoronix-test-suite.com" "' . PTS_VERSION . '"' . PHP_EOL . '.SH NAME' . PHP_EOL;
			$man_page .= 'phoronix-test-suite \- The Phoronix Test Suite is an extensible open-source platform for performing testing and performance evaluation.' . PHP_EOL;
			$man_page .= '.SH SYNOPSIS' . PHP_EOL . '.B phoronix-test-suite [options]' . PHP_EOL . '.br' . PHP_EOL . '.B phoronix-test-suite benchmark [test | suite]' . PHP_EOL;
			$man_page .= '.SH DESCRIPTION' . PHP_EOL . pts_documentation::basic_description() . PHP_EOL;
			$man_page .= '.SH OPTIONS' . PHP_EOL . '.TP' . PHP_EOL;

			foreach($pts_options as $section => &$contents)
			{
				if(empty($contents))
				{
					continue;
				}

				$man_page .= '.SH ' . strtoupper($section) . PHP_EOL;

				sort($contents);

				foreach($contents as &$option)
				{
					$man_page .= '.B ' . trim($option[0] . ' ' . (!empty($option[1]) && is_array($option[1]) ? implode(' ', $option[1]) : null)) . PHP_EOL . $option[2] . PHP_EOL . '.TP' . PHP_EOL;
				}
			}
			$man_page .= '.SH SEE ALSO' . PHP_EOL . '.B Websites:' . PHP_EOL . '.br' . PHP_EOL . 'https://www.phoronix-test-suite.com/' . PHP_EOL . '.br' . PHP_EOL . 'https://commercial.phoronix-test-suite.com/' . PHP_EOL . '.br' . PHP_EOL . 'https://www.openbenchmarking.org/' . PHP_EOL . '.br' . PHP_EOL . 'https://www.phoronix.com/' . PHP_EOL . '.br' . PHP_EOL;
			$man_page .= '.SH AUTHORS' . PHP_EOL . 'Copyright 2008 - ' . date('Y') . ' by Phoronix Media, Michael Larabel.' . PHP_EOL . '.TP' . PHP_EOL;

			file_put_contents(PTS_PATH . 'documentation/man-pages/phoronix-test-suite.1', $man_page);
		}


		// simple README
		$readme = '# Phoronix Test Suite ' . PTS_VERSION . PHP_EOL . '![Phoronix Test Suite](pts-core/static/images/pts-308x160.png)' . PHP_EOL . PHP_EOL;
		$readme .= pts_documentation::basic_description() . PHP_EOL . PHP_EOL;
		$readme .= pts_file_io::file_get_contents(PTS_PATH . 'documentation/stubs/readme-basics.txt') . PHP_EOL . PHP_EOL;
		$readme = wordwrap($readme, 80, PHP_EOL);
		file_put_contents(PTS_PATH . 'README.md', $readme);

		//$md = new pts_md_template();
		//$md->html_to_md(PTS_PATH . 'documentation/phoronix-test-suite-windows.html');
		//$md->Output(PTS_PATH . 'documentation/phoronix-test-suite-window.md');

	}
}

?>
