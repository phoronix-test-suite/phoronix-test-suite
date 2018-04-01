<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2011, Phoronix Media
	Copyright (C) 2009 - 2011, Michael Larabel

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

class inspect_test_profile implements pts_option_interface
{
	const doc_section = 'Asset Creation';
	const doc_description = 'This option can be used for inspecting a Phoronix Test Suite test profile with providing inside details on test profiles for debugging / evaluation / learning purposes.';

	public static function run($r)
	{
		foreach(pts_types::identifiers_to_test_profile_objects($r, true, true) as $test_profile)
		{
			pts_client::$display->generic_heading($test_profile);
			$doc = new DOMDocument();
			$doc->loadXML(file_get_contents(pts_openbenchmarking::openbenchmarking_standards_path() . 'schemas/test-profile.xsd'));
			$xpath = new DOMXPath($doc);
			$xpath->registerNamespace('xs', 'http://www.w3.org/2001/XMLSchema');

			$ev = $xpath->evaluate('/xs:schema/xs:element');
			foreach($ev as $e)
			{
				self::display_elements($test_profile, $xpath, $e);
			}
		}
	}
	protected static function display_elements(&$o, $xpath, $el, $depth = 0)
	{
		if($el->getElementsByTagName('*')->length > 0 && $el->getElementsByTagName('*')->item(0)->nodeName == 'xs:annotation' && $el->getElementsByTagName('*')->item(0)->getElementsByTagName('documentation')->length > 0)
		{
			echo str_repeat('     ', $depth) . pts_client::cli_just_bold($el->getAttribute('name'));
			if(($id = $el->getElementsByTagName('*')->item(0)->getAttribute('id')) != null && is_callable(array($o, $id)))
			{
				$val = call_user_func(array($o, $id));
				if(is_array($val))
				{
					$val = '{ ' . implode(', ', call_user_func(array($o, $id))) . ' }';
				}
				else if($val === true)
				{
					$val = 'TRUE';
				}
				else if($val === false)
				{
					$val = 'FALSE';
				}

				if(!empty($val))
				{
					echo ': ' . pts_client::cli_colored_text($val, 'cyan');
				}
			}
			echo PHP_EOL;

			$characteristics = array();
			if($el->getAttribute('minOccurs') > 0)
			{
				$characteristics[] = 'Required Tag';
			}
			if(count($characteristics) > 0)
			{
				echo str_repeat('     ', $depth) . pts_client::cli_just_bold('Characteristics: ') . implode(', ', $characteristics) . PHP_EOL;
			}

			echo str_repeat('     ', $depth) .  trim($el->getElementsByTagName('annotation')->item('0')->getElementsByTagName('documentation')->item(0)->nodeValue) . PHP_EOL;
		}
		else
		{
			echo str_repeat('     ', $depth) . pts_client::cli_colored_text($el->getAttribute('name'), 'yellow', true) . PHP_EOL;
		}

		$els = $xpath->evaluate('xs:complexType/xs:sequence/xs:element', $el);
		foreach($els as $e)
		{
			self::display_elements($o, $xpath, $e, ($depth + 1));
		}
		echo PHP_EOL;
	}
	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_test_profile', 'is_test_profile'), null)
		);
	}
}

?>
