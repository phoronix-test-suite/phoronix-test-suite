<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2020, Phoronix Media
	Copyright (C) 2009 - 2020, Michael Larabel

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
			$allowed_files[] = 'support-check_' . $os . '.sh';
			$allowed_files[] = 'install_' . $os . '.sh';
			$allowed_files[] = 'pre_' . $os . '.sh';
			$allowed_files[] = 'post_' . $os . '.sh';
			$allowed_files[] = 'interim_' . $os . '.sh';
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
				$append_missing_to[] = $tag_check;
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
		// Validate the XML against the XSD Schemas
		libxml_clear_errors();

		// First rewrite the main XML file to ensure it is properly formatted, elements are ordered according to the schema, etc...
		$valid = $test_suite->validate();

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
		$writer = new nye_XmlWriter();
		$types = pts_validation::process_xsd_types();
		$ret = pts_validation::xsd_to_rebuilt_xml(pts_openbenchmarking::openbenchmarking_standards_path() . 'schemas/test-profile.xsd', $types, $test_profile, $writer);
		$writer->saveXMLFile($test_profile->get_file_location());

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
			$writer = new nye_XmlWriter();
			$types = pts_validation::process_xsd_types();
			$ret = pts_validation::xsd_to_rebuilt_xml(pts_openbenchmarking::openbenchmarking_standards_path() . 'schemas/test-profile-downloads.xsd', $types, $test_profile, $writer);
			$writer->saveXMLFile($download_xml_file);

			$dom = new DOMDocument();
			$dom->load($download_xml_file);
			$valid = $dom->schemaValidate(pts_openbenchmarking::openbenchmarking_standards_path() . 'schemas/test-profile-downloads.xsd');

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

			foreach($test_profile->get_downloads() as $download)
			{
				foreach($download->get_download_url_array() as $url)
				{
					$stream_context = pts_network::stream_context_create();
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
			$writer = new nye_XmlWriter();
			$types = pts_validation::process_xsd_types();
			$tp_def = $test_profile->get_results_definition();
			$ret = pts_validation::xsd_to_rebuilt_xml(pts_openbenchmarking::openbenchmarking_standards_path() . 'schemas/results-parser.xsd', $types, $tp_def, $writer);
			$writer->saveXMLFile($parser_file);

			$dom = new DOMDocument();
			$dom->load($parser_file);
			$valid = $dom->schemaValidate(pts_openbenchmarking::openbenchmarking_standards_path() . 'schemas/results-parser.xsd');

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

		if(is_file($test_profile->get_resource_dir() . 'changelog.json'))
		{
			pts_file_io::unlink($test_profile->get_resource_dir() . 'changelog.json');
		}
		if(is_file($test_profile->get_resource_dir() . 'generated.json'))
		{
			pts_file_io::unlink($test_profile->get_resource_dir() . 'generated.json');
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
	public static function process_xsd_types()
	{
		$doc = new DOMDocument();
		$xsd_file = pts_openbenchmarking::openbenchmarking_standards_path() . 'schemas/types.xsd';
		if(is_file($xsd_file))
		{
			$doc->loadXML(file_get_contents($xsd_file));
		}
		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace('xs', 'http://www.w3.org/2001/XMLSchema');

		$types = array();
		foreach($xpath->evaluate('/xs:schema/xs:simpleType') as $e)
		{
			$name = $e->getAttribute('name');
			$type = $e->getElementsByTagName('restriction')->item(0)->getAttribute('base');
			switch($type)
			{
				case 'xs:integer':
					$type = 'INT';
					break;
				case 'xs:string':
					$type = 'STRING';
					break;
			}
			if($e->getElementsByTagName('restriction')->item(0)->getElementsByTagName('minLength')->length > 0)
			{
				$min_length = $e->getElementsByTagName('restriction')->item(0)->getElementsByTagName('minLength')->item(0)->getAttribute('value');
			}
			else
			{
				$min_length = -1;
			}
			if($e->getElementsByTagName('restriction')->item(0)->getElementsByTagName('maxLength')->length > 0)
			{
				$max_length = $e->getElementsByTagName('restriction')->item(0)->getElementsByTagName('maxLength')->item(0)->getAttribute('value');
			}
			else
			{
				$max_length = -1;
			}
			if($e->getElementsByTagName('restriction')->item(0)->getElementsByTagName('minInclusive')->length > 0)
			{
				$min_value = $e->getElementsByTagName('restriction')->item(0)->getElementsByTagName('minInclusive')->item(0)->getAttribute('value');
			}
			else
			{
				$min_value = -1;
			}
			if($e->getElementsByTagName('restriction')->item(0)->getElementsByTagName('maxInclusive')->length > 0)
			{
				$max_value = $e->getElementsByTagName('restriction')->item(0)->getElementsByTagName('maxInclusive')->item(0)->getAttribute('value');
			}
			else
			{
				$max_value = -1;
			}

			$enums = array();
			for($i = 0; $i < $e->getElementsByTagName('restriction')->item(0)->getElementsByTagName('enumeration')->length; $i++)
			{
				$enums[] = $e->getElementsByTagName('restriction')->item(0)->getElementsByTagName('enumeration')->item($i)->getAttribute('value');
			}


			$types[$name] = new pts_input_type_restrictions($name, $type, $min_length, $max_length, $min_value, $max_value, $enums);
		}
		return $types;
	}
	public static function xsd_to_cli_creator($xsd_file, &$new_object, $types = null)
	{
		$nodes = self::generate_xsd_element_objects($xsd_file, null, $types);
		self::xsd_nodes_to_cli_prompts($nodes, $new_object);
	}
	public static function xsd_nodes_to_cli_prompts($nodes, &$new_object)
	{
		foreach($nodes as $node)
		{
			$path = $node->get_path();
			if($node->get_documentation() == null)
			{
				continue;
			}

			if(in_array('UNCOMMON', $node->get_flags_array()))
			{
				continue;
			}

			echo pts_client::cli_just_bold($node->get_name());

			/*
			if($node->get_value() != null)
			{
				echo ': ' . pts_client::cli_colored_text($node->get_value(), 'cyan');
			}
			*/

			echo PHP_EOL;
			$enums = array();
			$min_value = -1;
			$max_value = -1;
			$type_restrict = null;
			if($node->get_input_type_restrictions() != null)
			{
				$type = $node->get_input_type_restrictions();
				$type_restrict = $type->get_type();
				// echo 'xx' . $type->get_name() . ' ' . $type->get_type() . 'xx' . PHP_EOL;
				$enums = $type->get_enums();
				if(!empty($enums))
				{
					echo pts_client::cli_colored_text('Possible Values: ', 'gray', true) . implode(', ', $enums) . PHP_EOL;
					echo pts_client::cli_colored_text('Multiple Selections Allowed: ', 'gray', true) . ($type->multi_enum_select() ? 'YES' : 'NO') . PHP_EOL;
				}
				$min_value = $type->get_min_value();
				if($min_value > -1)
				{
					echo pts_client::cli_colored_text('Minimum Value: ', 'gray', true) . $min_value . PHP_EOL;
				}
				$max_value = $type->get_max_value();
				if($max_value > 0)
				{
					echo pts_client::cli_colored_text('Maximum Value: ', 'gray', true) . $max_value . PHP_EOL;
				}
			}
			/*if($node->get_api() != null)
			{
				echo pts_client::cli_colored_text('API: ', 'gray', true) . $node->get_api()[0] . '->' . $node->get_api()[1] . '()' . PHP_EOL;
			}*/
			if($node->get_documentation() != null)
			{
				echo $node->get_documentation() . PHP_EOL;
			}
			if($node->get_default_value() != null)
			{
				echo pts_client::cli_colored_text('Default Value: ', 'gray', true) . $node->get_default_value() . PHP_EOL;
			}

			$do_require = in_array('TEST_REQUIRES', $node->get_flags_array());
			if(!empty($enums))
			{
				$input = pts_user_io::prompt_text_menu('Select from the supported options', $enums, $type->multi_enum_select(), false, null);
				if(is_array($input))
				{
					$input = implode(',', $input);
				}
			}
			else
			{
				do
				{
					$input_passes = true;
					$input = pts_user_io::prompt_user_input($path, !($do_require && $node->get_default_value() == null), false);

					if($do_require && $min_value > 0 && strlen($input) < $min_value)
					{
						echo 'Minimum length of ' . $min_value . ' is required.';
						$input_passes = false;
					}
					if($do_require && $max_value > 0 && strlen($input) > $max_value)
					{
						echo 'Maximum length of ' . $max_value . ' is supported.';
						$input_passes = false;
					}
					if(!empty($input) && $type_restrict == 'INT' && !is_numeric($input))
					{
						echo 'Input must be a valid integer number.';
						$input_passes = false;
					}
					if(!empty($input) && $type_restrict == 'xs:decimal' && !is_numeric($input))
					{
						echo 'Input must be a valid number.';
						$input_passes = false;
					}

				}
				while(!$input_passes);

				if(empty($input) && $node->get_default_value() != null)
				{
					$input = $node->get_default_value();
				}
			}

			$new_object->addXmlNodeWNE($path, trim($input));

			echo PHP_EOL;
		}
	}
	public static function xsd_to_html_creator($xsd_file, $types = null)
	{
		$nodes = self::generate_xsd_element_objects($xsd_file, null, $types);
		return self::xsd_nodes_to_html_prompts($nodes);
	}
	public static function xsd_nodes_to_html_prompts($nodes)
	{
		$html = null;

		foreach($nodes as $node)
		{
			$path = $node->get_path();
			if($node->get_documentation() == null)
			{
				continue;
			}

			$uncommon = in_array('UNCOMMON', $node->get_flags_array());
			$html .= '<div style="" class="' . ($uncommon ? 'pts_phoromatic_create_test_option_area_uncommon' : 'pts_phoromatic_create_test_option_area') . '" id="' . str_replace('/', '', $path) . '">';
			$html .= '<h3>' . $node->get_name() . ($uncommon ? ' <sup> Uncommon Option; Hover To Expand</sup>' : '') . '</h3>' . PHP_EOL;

			$enums = array();
			$min_value = -1;
			$max_value = -1;
			$type_restrict = null;
			if($node->get_input_type_restrictions() != null)
			{
				$html .= '<p>';
				$type = $node->get_input_type_restrictions();
				$type_restrict = $type->get_type();
				$enums = $type->get_enums();
				$min_value = $type->get_min_value();
				if($min_value > 0)
				{
					$html .= '<strong>Minimum Value: </strong>' . $min_value;
				}
				$max_value = $type->get_max_value();
				if($max_value > 0)
				{
					$html .= '<strong>Maximum Value: </strong>' . $max_value;
				}
				$html .= '</p>';
			}
			if($node->get_documentation() != null)
			{
				$html .= '<p>' . str_replace($node->get_name(), '<em>' . $node->get_name() . '</em>', $node->get_documentation()) . '</p>';
			}

			$do_require = in_array('TEST_REQUIRES', $node->get_flags_array());
			$html .= '<p>';
			if(!empty($enums))
			{
				$html .= '<select name="' . $path . '" ' . ($type->multi_enum_select() ? ' multiple' : '') . ($do_require ? ' required' : '') . '>' . PHP_EOL;
				foreach($enums as $enum)
				{
					$html .= '<option value="' . $enum . '"' . ($node->get_default_value() == $enum ? 'selected="selected"' : null) . '>' . $enum . '</option>';
				}
				$html .= '</select>';
			}
			else
			{
				if($type_restrict == 'INT' || $type_restrict == 'xs:decimal')
				{
					$html .= '<input type="number" name="' . $path . '" value="' . $node->get_default_value() . '" min="1" ' . ($do_require ? ' required' : '') . ' />';
				}
				else
				{
					$html .= '<input type="text" name="' . $path . '" value="' . $node->get_default_value() . '" ' . ($do_require ? ' required' : '') . ' />';
				}
			}

			$html .= '</p>';
			$html .= '</div>';
		}

		return $html;
	}
	public static function xsd_to_var_array_generate_xml($xsd_file, $types, &$array_to_check, &$writer)
	{
		foreach(self::generate_xsd_element_objects($xsd_file, null, $types) as $node)
		{
			$do_require = in_array('TEST_REQUIRES', $node->get_flags_array());
			$path = $node->get_path();
			$value = isset($array_to_check[$path]) ? $array_to_check[$path] : null;
			if(empty($value))
			{
				$value = $node->get_default_value();
			}
			if(empty($value))
			{
				continue;
			}
			if($do_require && empty($value))
			{
				//return 'The ' . $path . ' value cannot be empty.';
			}
			$writer->addXmlNodeWNE($path, $value);
		}

		return true;
	}
	public static function xsd_to_rebuilt_xml($xsd_file, $types, &$test_profile, &$writer)
	{
		$test_profile->no_fallbacks_on_null = true;
		foreach(self::generate_xsd_element_objects($xsd_file, $test_profile, $types) as $node)
		{
			$do_require = in_array('TEST_REQUIRES', $node->get_flags_array());
			$value = $node->get_value();
			$path = $node->get_path();

			if($value == $node->get_default_value() && in_array('UNCOMMON', $node->get_flags_array()))
			{
				continue;
			}
			//if(empty($value))
			//{
			//	$value = $node->get_default_value();
			//}
			if(empty($value) && $value !== '0')
			{
				continue;
			}
			//if($do_require && empty($value))
			//{
				//return 'The ' . $path . ' value cannot be empty.';
			//}
			$writer->addXmlNodeWNE($path, $value);
		}
		$test_profile->no_fallbacks_on_null = false;

		return true;
	}
	public static function string_to_sanitized_test_profile_base($input)
	{
		return pts_strings::keep_in_string(str_replace(' ', '-', strtolower($input)), pts_strings::CHAR_LETTER | pts_strings::CHAR_NUMERIC | pts_strings::CHAR_DASH);;
	}
	protected static function generate_xsd_element_objects($xsd_file, $obj = null, $types = null)
	{
		$doc = new DOMDocument();
		if(is_file($xsd_file))
		{
			$doc->loadXML(file_get_contents($xsd_file));
		}
		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace('xs', 'http://www.w3.org/2001/XMLSchema');

		$nodes = array();
		$ev = $xpath->evaluate('/xs:schema/xs:element');
		foreach($ev as $e)
		{
			self::xsd_elements_to_objects($nodes, $obj, $xpath, $e, $types, '');
		}

		return $nodes;
	}
	public static function xsd_elements_to_objects(&$append_to_array, $o, $xpath, $el, $types, $path)
	{
		static $unbounded;

		if($el->getElementsByTagName('*')->length > 0 && $el->getElementsByTagName('*')->item(0)->nodeName == 'xs:annotation' && $el->getElementsByTagName('*')->item(0)->getElementsByTagName('documentation')->length > 0)
		{
			$name = $el->getAttribute('name');
			$value = null;
			$get_api = null;
			$set_api = null;
			$default_value = null;
			$flags = null;
			$class = null;
			$dynamic_list_multi = '';
			$nodes_to_match = array('set' => 'set_api', 'get' => 'get_api', 'default' => 'default_value', 'flags' => 'flags', 'dynamic_list_multi' => 'dynamic_list_multi');
			$cnodes = $el->getElementsByTagName('*');
			for($i = 0; $i < $cnodes->length; $i++)
			{
				if(isset($nodes_to_match[$cnodes->item($i)->nodeName]) && ${$nodes_to_match[$cnodes->item($i)->nodeName]} == null)
				{
					${$nodes_to_match[$cnodes->item($i)->nodeName]} = $cnodes->item($i)->nodeValue;
				}
			}

			if($get_api != null && (is_callable(array($o, $get_api)) || (is_array($o) && isset($o[$get_api]))))
			{
				if(is_object($o))
				{
					$class = get_class($o);
					$val = call_user_func(array($o, $get_api));

					if(is_object($val))
					{
						$o = $val;
						$val = null;
					}
				}
				else if(is_array($o))
				{
					$class = null;
					$val = $o[$get_api];
				}

				if($el->getAttribute('maxOccurs') == 'unbounded')
				{
					$o = $val;
					$val = null;
				}
				else if(is_array($val))
				{
					$val = implode(', ', call_user_func(array($o, $get_api)));
				}
				else if($val === true)
				{
					$val = 'TRUE';
				}
				else if($val === false)
				{
					$val = 'FALSE';
				}

				if($val !== null)
				{
					$value = $val;
				}
			}

			$input_type_restrictions =  new pts_input_type_restrictions();
			if($el->getAttribute('type') != null)
			{
				$type = $el->getAttribute('type');
				if(isset($types[$type]))
				{
					$types[$type]->set_required($el->getAttribute('minOccurs') > 0);
					$input_type_restrictions = $types[$type];
				}
			}
			if(is_array($unbounded))
			{
				foreach($unbounded as $ub_check)
				{
					if(strpos($path, $ub_check) !== false)
					{
						$flags .= ' UNBOUNDED';
						break;
					}
				}
			}
			$api = null;
			if(!empty($get_api) && !empty($class))
			{
				$api = array($class, $get_api);
			}
			$documentation = trim($el->getElementsByTagName('annotation')->item('0')->getElementsByTagName('documentation')->item(0)->nodeValue);

			if($input_type_restrictions->is_enums_empty() && !empty($dynamic_list_multi))
			{
				$dynamic_list_multi = explode('.', $dynamic_list_multi);
				if(count($dynamic_list_multi) == 2 && is_callable(array($dynamic_list_multi[0], $dynamic_list_multi[1])))
				{
					$dynamic_list_multi_enums = call_user_func(array($dynamic_list_multi[0], $dynamic_list_multi[1]));

					if(is_array($dynamic_list_multi_enums))
					{
						$input_type_restrictions->set_enums($dynamic_list_multi_enums);
						$input_type_restrictions->set_multi_enum_select(true);
					}
				}

			}

			$append_to_array[] = new pts_element_node($name, $value, $input_type_restrictions, $api, $documentation, $set_api, $default_value, $flags, $path . '/' . $name);
		}
		else
		{
			$name = $el->getAttribute('name');
			$new_el = new pts_element_node($name);
			$new_el->set_path($path . '/' . $name);
			$append_to_array[] = $new_el;
		}

		if($el->getAttribute('maxOccurs') == 'unbounded')
		{
			$unbounded[$path . '/' . $name] =  $path . '/' . $name;
		}

		$els = $xpath->evaluate('xs:complexType/xs:sequence/xs:element', $el);
		if(is_array($o) && !empty($o))
		{
			$path .= (!empty($path) ? '/' : '') . $name;

			foreach($o as $j)
			{
				foreach($els as $e)
				{
					self:: xsd_elements_to_objects($append_to_array, $j, $xpath, $e, $types, $path);
				}
			}
		}
		else
		{
			$path .= (!empty($path) ? '/' : '') . $name;
			foreach($els as $e)
			{
				self:: xsd_elements_to_objects($append_to_array, $o, $xpath, $e, $types, $path);
			}
		}
	}
	public static function process_xsd_display_chart($xsd_file, $obj = null, $types = null)
	{
		$nodes = self::generate_xsd_element_objects($xsd_file, $obj, $types);
		self::xsd_display_cli_from_objects($nodes);
	}
	public static function xsd_display_cli_from_objects($nodes)
	{
		foreach($nodes as $node)
		{
			$path = $node->get_path();
			$depth = count(explode('/', $path)) - 1;
			if($node->get_documentation() == null)
			{
				echo str_repeat('     ', $depth) . pts_client::cli_colored_text($node->get_name(), 'yellow', true);
			}
			else
				echo str_repeat('     ', $depth) . pts_client::cli_just_bold($node->get_name());

			if($node->get_value() != null)
			{
				echo ': ' . pts_client::cli_colored_text($node->get_value(), 'cyan');
			}
			echo PHP_EOL;
			if($node->get_input_type_restrictions() != null)
			{
				$type = $node->get_input_type_restrictions();
				$enums = $type->get_enums();
				if(!empty($enums))
				{
					echo str_repeat('     ', $depth) . pts_client::cli_colored_text('Possible Values: ', 'gray', true) . implode(', ', $enums) . PHP_EOL;
				}
				$min_value = $type->get_min_value();
				if($min_value > -1)
				{
					echo str_repeat('     ', $depth) . pts_client::cli_colored_text('Minimum Value: ', 'gray', true) . $min_value . PHP_EOL;
				}
				$max_value = $type->get_max_value();
				if($max_value > 0)
				{
					echo str_repeat('     ', $depth) . pts_client::cli_colored_text('Maximum Value: ', 'gray', true) . $max_value . PHP_EOL;
				}
			}
			if($node->get_api() != null)
			{
				echo str_repeat('     ', $depth) . pts_client::cli_colored_text('Get API: ', 'gray', true) . $node->get_api()[0] . '->' . $node->get_api()[1] . '()' . PHP_EOL;
			}
			if($node->get_api_setter() != null)
			{
				echo str_repeat('     ', $depth) . pts_client::cli_colored_text('Set API: ', 'gray', true) . $node->get_api_setter() . '()' . PHP_EOL;
			}
			if($node->get_default_value() != null)
			{
				echo str_repeat('     ', $depth) . pts_client::cli_colored_text('Default Value: ', 'gray', true) . $node->get_default_value() . PHP_EOL;
			}
			if($node->get_documentation() != null)
			{
				echo str_repeat('     ', $depth) .  $node->get_documentation() . PHP_EOL;
			}
			echo PHP_EOL;
		}
	}
	public static function generate_test_profile_file_templates($tp_identifier, $tp_path)
	{
		$test_profile = new pts_test_profile($tp_identifier);
		$result_scale = $test_profile->get_result_scale();
		$test_executable = $test_profile->get_test_executable();
		if($test_executable == null)
		{
			$test_executable = $test_profile->get_identifier_base_name();
		}

		if(!is_file($tp_path . '/install.sh'))
		{
			$sample_install_sh = '#!/bin/sh' . PHP_EOL . '# Auto-generated install.sh script for starting/helping the test profile creation process...' . PHP_EOL . PHP_EOL;

			$download_extract_helpers = array();
			foreach($test_profile->get_downloads() as $file)
			{
				$file = $file->get_filename();
				switch(substr($file, strrpos($file, '.') + 1))
				{
					case 'zip':
						$download_extract_helpers[] = 'unzip -o ' . $file;
						break;
					case 'gz':
					case 'bz2':
					case 'xz':
					case 'tar':
						$download_extract_helpers[] = 'tar -xvf ' . $file;
						break;
					case 'exe':
					case 'msi':
					case 'run':
						$download_extract_helpers[] = './' . $file;
						break;
				}
			}

			if(!empty($download_extract_helpers))
			{
				$sample_install_sh . '# Presumably you want to extract/run the downloaded files for setting up the test case...' . PHP_EOL;
				$sample_install_sh .= implode(PHP_EOL, $download_extract_helpers) . PHP_EOL;
			}

			$sample_install_sh .= PHP_EOL . 'echo "#!/bin/sh' . PHP_EOL;
			$sample_install_sh .= '# the actual running/execution of the test, etc... This is called at run-time.' . PHP_EOL;
			$sample_install_sh .= '# The program under test and/or any parsing/wrapper scripts should then pipe the results to \$LOG_FILE for parsing.' . PHP_EOL;
			$sample_install_sh .= '# Passed to the script as arguments are any of the test arguments/options as defined by the test-definition.xml.' . PHP_EOL;
			$sample_install_sh .= PHP_EOL . '# Editing the test profile\'s results-definition.xml controls how the Phoronix Test Suite will capture the program\'s result.' . PHP_EOL;
			$sample_install_sh .= '# STATIC EXAMPLE below coordinated with the stock result-definition.xml.' . PHP_EOL;
			$sample_install_sh .= 'echo \"Result: 55.5\" > \$LOG_FILE' . PHP_EOL;
			$sample_install_sh .= 'echo \$? > ~/test-exit-status' . PHP_EOL;
			$sample_install_sh .= PHP_EOL . '" > ~/' . $test_executable . PHP_EOL;
			$sample_install_sh .= 'chmod +x ~/' . $test_executable . PHP_EOL;

			$sample_install_sh .= PHP_EOL . '# Check out the `phoronix-test-suite debug-run` command when trying to debug your install/run behavior' . PHP_EOL;

			file_put_contents($tp_path . '/install.sh', $sample_install_sh);
		}

		if(!is_file($tp_path . '/results-definition.xml'))
		{
			file_put_contents($tp_path . '/results-definition.xml', '<?xml version="1.0"?>
<PhoronixTestSuite>
  <ResultsParser>
    <OutputTemplate>Result: #_RESULT_#</OutputTemplate>
  </ResultsParser>
</PhoronixTestSuite>');
		}

		if(!is_file($tp_path . '/pre.sh'))
		{
			file_put_contents($tp_path . '/pre.sh', '#!/bin/sh
# pre.sh is called prior to running the test, if needed to setup any sample data / create a test file / seed a cache / related pre-run tasks');
		}

		if(!is_file($tp_path . '/interim.sh'))
		{
			file_put_contents($tp_path . '/interim.sh', '#!/bin/sh
# interim.sh is called in between test runs for when a test profile is set via TimesToRun to execute multiple times. This is useful for restoring a program\'s state or any other changes that need to be made in between runs.');
		}

		if(!is_file($tp_path . '/post.sh'))
		{
			file_put_contents($tp_path . '/post.sh', '#!/bin/sh
# post.sh is called after the test has been run, if needed to flush any cache / temporary files, clean-up anything, etc.');
		}
	}
}

?>
