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

	public static function module_environmental_variables()
	{
		return array('EXPORT_RESULTS_HTML_EMAIL_TO', 'EXPORT_RESULTS_HTML_FILE_TO');
	}
	protected static function generate_html_email_results($result_file)
	{
		$html = '<html><head><title>' . $result_file->get_title() . ' - Phoronix Test Suite</title></head><body>';
		$html .= '<h1>' . $result_file->get_title() . '</h1>';
		$html .= '<p>' . $result_file->get_description() . '</p>';
		$extra_attributes = array();

		// Systems
		$table = new pts_ResultFileSystemsTable($result_file);
		$html .= '<p style="text-align: center; overflow: auto;">' . pts_render::render_graph_inline_embed($table, $result_file, $extra_attributes, true, 'HTML') . '</p>';

		// Result Overview
		$intent = null;
		$table = new pts_ResultFileTable($result_file, $intent);
		$html .= '<p style="text-align: center; overflow: auto;">' . pts_render::render_graph_inline_embed($table, $result_file, $extra_attributes, true, 'HTML') . '</p>';

		// The Results
		foreach($result_file->get_result_objects() as $result_object)
		{
			$res = pts_render::render_graph_inline_embed($result_object, $result_file, $extra_attributes, true, 'HTML');

			if($res == false)
			{
				continue;
			}

			$html .= '<h2>' . $result_object->test_profile->get_title() . '</h2>';
			$html .= '<h3>' . $result_object->get_arguments_description() . '</h3>';
			$html .= '<p align="center">';
			$html .= $res;
			$html .= '</p>';
			unset($result_object);
		}

		// Footer
		$html .= '<hr />
				<p><img src="http://www.phoronix-test-suite.com/web/pts-logo-60.png" /></p>
				<h6><em>The <a href="http://www.phoronix-test-suite.com/">Phoronix Test Suite</a>, <a href="http://www.phoromatic.com/">Phoromatic</a>, and <a href="http://openbenchmarking.org/">OpenBenchmarking.org</a> are products of <a href="http://www.phoronix-media.com/">Phoronix Media</a>.<br />The Phoronix Test Suite is open-source under terms of the GNU GPL. Commercial support, custom engineering, and other services are available by contacting Phoronix Media.<br />&copy; ' . date('Y') . ' Phoronix Media.</em></h6>';
		$html .= '</body></html>';

		return $html;
	}
	public static function __event_results_saved($test_run_manager)
	{
		$html_file = pts_module::read_variable('EXPORT_RESULTS_HTML_FILE_TO');
		$emails = pts_strings::comma_explode(pts_module::read_variable('EXPORT_RESULTS_HTML_EMAIL_TO'));

		$html_contents = self::generate_html_email_results($test_run_manager->result_file);


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

				/*$boundary = md5(uniqid(time()));
				$headers = "From: Phoronix Test Suite <no-reply@phoromatic.com>\r\n";
				$headers .= "MIME-Version: 1.0\r\n";
				$headers .= "Content-Type: multipart/mixed; boundary=\"" . $boundary . "\"\r\n\r\n";
				$message = "This is a multi-part message in MIME format.\r\n";
				$message .= "--" . $boundary . "\r\n";
				$message .= "Content-Type: text/html; charset=utf-8\r\n";
				$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
				$message .= $html_contents . "\r\n\r\n";
				$message .= "--" . $boundary . "\r\n";
				$message .= "Content-Type: application/pdf; name=\"pts-test-results.pdf\"\r\n";
				$message .= "Content-Transfer-Encoding: base64\r\n";
				$message .= "Content-Disposition: attachment; filename=\"pts-test-results.pdf\"\r\n\r\n";
				$message .= $pdf_contents . "\r\n\r\n";
				$message .= "--" . $boundary . "--";

				mail($email, 'Phoronix Test Suite Result File: ' . $test_run_manager->result_file->get_title(), $message, $headers);
				echo 'HTML Results Emailed To: ' . $email . PHP_EOL; */
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
