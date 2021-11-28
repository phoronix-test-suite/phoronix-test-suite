<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2016, Phoronix Media
	Copyright (C) 2016, Michael Larabel

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

class html_results_export extends pts_module_interface
{
	const module_name = 'Result Exporter To HTML';
	const module_version = '1.0.0';
	const module_description = 'This module allows basic exporting of results to HTML for saving either to a file locally (specified using the EXPORT_RESULTS_HTML_FILE_TO environment variable) or to a mail account (specified using the EXPORT_RESULTS_HTML_EMAIL_TO environment variable). EXPORT_RESULTS_HTML_EMAIL_TO supports multiple email addresses delimited by a comma.';
	const module_author = 'Michael Larabel';

	public static function module_environment_variables()
	{
		return array('EXPORT_RESULTS_HTML_EMAIL_TO', 'EXPORT_RESULTS_HTML_FILE_TO');
	}
	public static function __event_results_saved($test_run_manager)
	{
		$html_file = pts_env::read('EXPORT_RESULTS_HTML_FILE_TO');
		$emails = pts_strings::comma_explode(pts_env::read('EXPORT_RESULTS_HTML_EMAIL_TO'));

		$html_contents = pts_result_file_output::result_file_to_html($test_run_manager->result_file);

		if(!empty($html_file))
		{
			file_put_contents($html_file, $html_contents);
			echo 'HTML Result File To: ' . $html_file . PHP_EOL;
		}

		if(!empty($emails))
		{
			//$pdf_contents = pts_result_file_output::result_file_to_pdf($test_run_manager->result_file, 'pts-test-results.pdf', 'S');
			//$pdf_contents = chunk_split(base64_encode($pdf_contents));

			foreach($emails as $email)
			{
				$headers = "MIME-Version: 1.0\r\n";
				$headers .= "Content-type:text/html;charset=UTF-8\r\n";
				$headers .= "From: Phoromatic - Phoronix Test Suite <no-reply@phoromatic.com>\r\n";
				mail($email, 'Phoronix Test Suite Result File: ' . $test_run_manager->result_file->get_title(), $html_contents, $headers);
				echo 'HTML Results Emailed To: ' . $email . PHP_EOL;
			}
		}
	}
}

?>
