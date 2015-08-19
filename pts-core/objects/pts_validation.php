<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2013, Phoronix Media
	Copyright (C) 2009 - 2013, Michael Larabel

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

class pts_validation
{
	public static function process_libxml_errors()
	{
		$error_queue = array();
		$errors = libxml_get_errors();

		foreach($errors as $i => &$error)
		{
			if(isset($error_queue[$error->line]))
			{
				// There's already been an error reported for this line
				unset($errors[$i]);
			}

			switch($error->code)
			{
				case 1840: // Not in enumeration
				case 1839: // Not in pattern
				case 1871: // Missing / invalid element
				case 1833: // Below the minInclusive value
					echo PHP_EOL . $error->message;
					echo 'Line ' . $error->line . ': ' . $error->file . PHP_EOL;
					$error_queue[$error->line] = true;
					unset($errors[$i]);
					break;
			}
		}

		if(count($errors) > 0 && PTS_IS_CLIENT)
		{
			// DEBUG
			print_r($errors);
		}

		libxml_clear_errors();
	}
	public static function test_profile_permitted_files()
	{
		$allowed_files = array('downloads.xml', 'test-definition.xml', 'results-definition.xml', 'install.sh', 'support-check.sh', 'pre.sh', 'post.sh', 'interim.sh', 'post-cache-share.sh');

		foreach(pts_types::operating_systems() as $os)
		{
			$os = strtolower($os[0]);
			array_push($allowed_files, 'support-check_' . $os . '.sh');
			array_push($allowed_files, 'install_' . $os . '.sh');
			array_push($allowed_files, 'pre_' . $os . '.sh');
			array_push($allowed_files, 'post_' . $os . '.sh');
			array_push($allowed_files, 'interim_' . $os . '.sh');
		}

		return $allowed_files;
	}
	public static function check_xml_tags(&$obj, &$tags_to_check, &$append_missing_to)
	{
		foreach($tags_to_check as $tag_check)
		{
			$to_check = $obj->xml_parser->getXMLValue($tag_check[0]);

			if(empty($to_check))
			{
				array_push($append_missing_to, $tag_check);
			}
		}
	}
	public static function print_issue($type, $problems_r)
	{
		foreach($problems_r as $error)
		{
			list($target, $description) = $error;

			echo PHP_EOL . $type . ': ' . $description . PHP_EOL;

			if(!empty($target))
			{
				echo 'TARGET: ' . $target . PHP_EOL;
			}
		}
	}
	public static function validate_test_suite(&$test_suite)
	{
		if($test_suite->xml_parser->getFileLocation() == null)
		{
			echo PHP_EOL . 'ERROR: The file location of the XML test suite source could not be determined.' . PHP_EOL;
			return false;
		}

		// Validate the XML against the XSD Schemas
		libxml_clear_errors();

		// First rewrite the main XML file to ensure it is properly formatted, elements are ordered according to the schema, etc...
		$valid = $test_suite->xml_parser->validate();

		if($valid == false)
		{
			echo PHP_EOL . 'Errors occurred parsing the main XML.' . PHP_EOL;
			pts_validation::process_libxml_errors();
			return false;
		}
		else
		{
			echo PHP_EOL . 'Test Suite XML Is Valid.' . PHP_EOL;
		}

		return true;
	}
	public static function validate_test_profile(&$test_profile)
	{

		if($test_profile->get_file_location() == null)
		{
			echo PHP_EOL . 'ERROR: The file location of the XML test profile source could not be determined.' . PHP_EOL;
			return false;
		}

		// Validate the XML against the XSD Schemas
		libxml_clear_errors();

		// Now re-create the pts_test_profile object around the rewritten XML
		$test_profile = new pts_test_profile($test_profile->get_identifier());
		$valid = $test_profile->validate();

		if($valid == false)
		{
			echo PHP_EOL . 'Errors occurred parsing the main XML.' . PHP_EOL;
			pts_validation::process_libxml_errors();
			return false;
		}

		// Rewrite the main XML file to ensure it is properly formatted, elements are ordered according to the schema, etc...
		$test_profile_writer = new pts_test_profile_writer();
		$test_profile_writer->rebuild_test_profile($test_profile);
		$test_profile_writer->save_xml($test_profile->get_file_location());

		// Now re-create the pts_test_profile object around the rewritten XML
		$test_profile = new pts_test_profile($test_profile->get_identifier());
		$valid = $test_profile->validate();

		if($valid == false)
		{
			echo PHP_EOL . 'Errors occurred parsing the main XML.' . PHP_EOL;
			pts_validation::process_libxml_errors();
			return false;
		}
		else
		{
			echo PHP_EOL . 'Test Profile XML Is Valid.' . PHP_EOL;
		}

		// Validate the downloads file
		$download_xml_file = $test_profile->get_file_download_spec();

		if(empty($download_xml_file) == false)
		{
			$writer = new pts_test_profile_downloads_writer();
			$writer->rebuild_download_file($test_profile);
			$writer->save_xml($download_xml_file);

			$downloads_parser = new pts_test_downloads_nye_XmlReader($download_xml_file);
			$valid = $downloads_parser->validate();

			if($valid == false)
			{
				echo PHP_EOL . 'Errors occurred parsing the downloads XML.' . PHP_EOL;
				pts_validation::process_libxml_errors();
				return false;
			}
			else
			{
				echo PHP_EOL . 'Test Downloads XML Is Valid.' . PHP_EOL;
			}


			// Validate the individual download files
			echo PHP_EOL . 'Testing File Download URLs.' . PHP_EOL;
			$files_missing = 0;
			$file_count = 0;

			foreach(pts_test_install_request::read_download_object_list($test_profile) as $download)
			{
				foreach($download->get_download_url_array() as $url)
				{
					$stream_context = pts_network::stream_context_create();
					stream_context_set_params($stream_context, array('notification' => 'pts_stream_status_callback'));
					$file_pointer = fopen($url, 'r', false, $stream_context);

					if($file_pointer == false)
					{
						echo 'File Missing: ' . $download->get_filename() . ' / ' . $url . PHP_EOL;
						$files_missing++;
					}
					else
					{
						fclose($file_pointer);
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
				echo PHP_EOL . 'Errors occurred parsing the results parser XML.' . PHP_EOL;
				pts_validation::process_libxml_errors();
				return false;
			}
			else
			{
				echo PHP_EOL . 'Test Results Parser XML Is Valid.' . PHP_EOL;
			}
		}

		// Make sure no extra files are in there
		$allowed_files = pts_validation::test_profile_permitted_files();

		foreach(pts_file_io::glob($test_profile->get_resource_dir() . '*') as $tp_file)
		{
			if(!is_file($tp_file) || !in_array(basename($tp_file), $allowed_files))
			{
				echo PHP_EOL . basename($tp_file) . ' is not allowed in the test package.' . PHP_EOL;
				return false;
			}
		}

		return true;
	}
}

?>
