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

class validate_test_profile implements pts_option_interface
{
	public static function run($r)
	{
		foreach(pts_types::identifiers_to_test_profile_objects($r, true, true) as $test_profile)
		{
			pts_client::$display->generic_heading($test_profile);

			if($test_profile->xml_parser->getFileLocation() == null)
			{
				echo "\nERROR: The file location of the XML test profile source could not be determined.\n";
				return false;
			}

			// Validate the XML against the XSD Schemas
			libxml_clear_errors();

			// First rewrite the main XML file to ensure it is properly formatted, elements are ordered according to the schema, etc...
			$test_profile_writer = new pts_test_profile_writer();
			$test_profile_writer->rebuild_test_profile($test_profile);
			$test_profile_writer->save_xml($test_profile->xml_parser->getFileLocation());

			// Now re-create the pts_test_profile object around the rewritten XML
			$test_profile = new pts_test_profile($test_profile->get_identifier());
			$valid = $test_profile->xml_parser->validate();

			if($valid == false)
			{
				echo "\nErrors occurred parsing the main XML.\n";
				pts_validation::process_libxml_errors();
				return false;
			}
			else
			{
				echo "\nTest Profile XML Is Valid.\n";
			}

			// Validate the downloads file
			$download_xml_file = $test_profile->get_file_download_spec();

			if(empty($download_xml_file) == false)
			{
				$writer = new pts_test_profile_downloads_writer();
				$writer->rebuild_download_file($download_xml_file);
				$writer->save_xml($download_xml_file);

				$downloads_parser = new pts_test_downloads_nye_XmlReader($download_xml_file);
				$valid = $downloads_parser->validate();

				if($valid == false)
				{
					echo "\nErrors occurred parsing the downloads XML.\n";
					pts_validation::process_libxml_errors();
					return false;
				}
				else
				{
					echo "\nTest Downloads XML Is Valid.\n";
				}


				// Validate the individual download files
				echo "\nTesting File Download URLs.\n";
				$files_missing = 0;

				foreach(pts_test_install_request::read_download_object_list($test_profile) as $download)
				{
					foreach($download->get_download_url_array() as $url)
					{
						$stream_context = pts_network::stream_context_create();
						stream_context_set_params($stream_context, array("notification" => "pts_stream_status_callback"));
						$file_pointer = @fopen($url, 'r', false, $stream_context);

						if($file_pointer == false)
						{
							echo "\File Missing: " . $download->get_filename() . " / " . $url . "\n";
							$files_missing++;
						}
						else
						{
							@fclose($file_pointer);
						}

					}
				}

				if($files_missing > 0)
				{
					return false;
				}
			}

echo "\n";
			return;

			$validation_errors = array();
			$validation_warnings = array();

			// Checks for missing tag errors and warnings
			pts_validation::check_xml_tags($test_profile, pts_validation::required_test_tags(), $validation_errors);
			pts_validation::check_xml_tags($test_profile, pts_validation::recommended_test_tags(), $validation_warnings);

			// Check for other test profile problems
			foreach(pts_test_install_request::read_download_object_list($test_profile->get_identifier()) as $package_download)
			{
				$download_urls = $package_download->get_download_url_array();

				if(count($download_urls) < 2)
				{
					array_push($validation_warnings, array($package_download->get_filename(), "Multiple file mirrors (delimited in the downloads.xml tag by a comma) are recommended for redundancy purposes."));
				}
			}

			if(count($validation_errors) == 0 && count($validation_warnings) == 0)
			{
				echo "\nNo errors or warnings found with this test profile.\n\n";
			}
			else
			{
				pts_validation::print_issue("ERROR", $validation_errors);
				pts_validation::print_issue("WARNING", $validation_warnings);
				echo "\n";
			}

			// TODO: hook in validate() call from pts_test_nye_XmlReader
		}
	}
}

?>
