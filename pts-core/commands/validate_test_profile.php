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
	const doc_section = 'Asset Creation';
	const doc_description = "This option can be used for validating a Phoronix Test Suite test profile as being compliant against the OpenBenchmarking.org specification.";

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
				$file_count = 0;

				foreach(pts_test_install_request::read_download_object_list($test_profile) as $download)
				{
					foreach($download->get_download_url_array() as $url)
					{
						$stream_context = pts_network::stream_context_create();
						stream_context_set_params($stream_context, array("notification" => "pts_stream_status_callback"));
						$file_pointer = @fopen($url, 'r', false, $stream_context);

						if($file_pointer == false)
						{
							echo "File Missing: " . $download->get_filename() . " / " . $url . "\n";
							$files_missing++;
						}
						else
						{
							@fclose($file_pointer);
						}
						$file_count++;
					}
				}

				if($files_missing > 0) // && $file_count == $files_missing
				{
					return false;
				}
			}


			// Validate the parser file
			$parser_file = $test_profile->get_file_parser_spec();

			if(empty($parser_file) == false)
			{
				$writer = new pts_test_result_parser_writer();
				$writer->rebuild_parser_file($parser_file);
				$writer->save_xml($parser_file);

				$parser = new pts_parse_results_nye_XmlReader($parser_file);
				$valid = $parser->validate();

				if($valid == false)
				{
					echo "\nErrors occurred parsing the results parser XML.\n";
					pts_validation::process_libxml_errors();
					return false;
				}
				else
				{
					echo "\nTest Results Parser XML Is Valid.\n";
				}
			}

			// Make sure no extra files are in there
			$allowed_files = pts_validation::test_profile_permitted_files();

			foreach(pts_file_io::glob($test_profile->get_resource_dir() . '*') as $tp_file)
			{
				if(!is_file($tp_file) || !in_array(basename($tp_file), $allowed_files))
				{
					echo "\n" . basename($tp_file) . " is not allowed in the test package.\n";
					return false;
				}
			}

			$zip_file = PTS_OPENBENCHMARKING_SCRATCH_PATH . $test_profile->get_identifier() . '-' . $test_profile->get_test_profile_version() . ".zip";
			$zip_created = pts_compression::zip_archive_create($zip_file, pts_file_io::glob($test_profile->get_resource_dir() . '*'));

			if($zip_created == false)
			{
				echo "\nFailed to create zip file.\n";
				return false;
			}

			if(filesize($zip_file) > 104857)
			{
				echo "\nThe test profile package is too big.\n";
				return false;
			}

			// TODO: chmod +x the .sh files, appropriate permissions elsewhere
			unlink($zip_file);
		}
	}
}

?>
