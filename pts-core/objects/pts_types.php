<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2021, Phoronix Media
	Copyright (C) 2010 - 2021, Michael Larabel

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

class pts_types
{
	public static function subsystem_targets()
	{
		return self::parse_xsd_types('TestType');
	}
	public static function software_license_types()
	{
		return self::parse_xsd_types('License');
	}
	public static function test_profile_state_types()
	{
		return self::parse_xsd_types('ProfileStatus');
	}
	public static function test_profile_software_types()
	{
		return self::parse_xsd_types('SoftwareType');
	}
	public static function test_profile_display_formats()
	{
		return self::parse_xsd_types('DisplayFormat');
	}
	public static function test_profile_quantifiers()
	{
		return self::parse_xsd_types('ResultQuantifier');
	}
	public static function operating_systems()
	{
		return array(array('Linux'), array('Solaris', 'Sun'), array('BSD', 'DragonFly'), array('MacOSX', 'Darwin'), array('Windows'), array('Hurd', 'GNU'));
	}
	public static function known_architectures()
	{
		return array('x86_64', 'i686', 'arm', 'ppc', 'sparc', 'ppc64', 'aarch64', '');
	}
	public static function known_operating_systems()
	{
		return array('Linux', 'Windows', 'BSD', 'MacOSX', 'Solaris', '');
	}
	public static function all_possible_external_dependencies()
	{
		$possible_deps = PTS_IS_CLIENT ? pts_external_dependencies::all_dependency_names() : array();
		$possible_deps[] = '';
		return $possible_deps;
	}
	public static function identifiers_to_test_profile_objects($identifiers, $include_extensions = false, $remove_duplicates = true, &$archive_unknown_objects = false)
	{
		$test_profiles = array();

		foreach(pts_types::identifiers_to_objects($identifiers, $archive_unknown_objects) as $object)
		{
			if($object instanceof pts_test_profile)
			{
				$test_profiles[] = $object;
			}
			else if($object instanceof pts_test_suite)
			{
				foreach($object->get_contained_test_profiles() as $test_profile)
				{
					$test_profiles[] = $test_profile;
				}
			}
			else if($object instanceof pts_result_file)
			{
				foreach($object->get_contained_test_profiles() as $test_profile)
				{
					$test_profiles[] = $test_profile;
				}
			}
		}

		if($include_extensions)
		{
			$extended_test_profiles = array();

			for($i = 0; $i < count($test_profiles); $i++)
			{
				foreach(array_reverse($test_profiles[$i]->extended_test_profiles()) as $test_profile)
				{
					if(!in_array($test_profile, $extended_test_profiles))
					{
						$extended_test_profiles[] = $test_profile;
					}
				}

				$extended_test_profiles[] = $test_profiles[$i];
			}

			// We end up doing this swapping around so the extended test profiles always end up before the tests extending them
			$test_profiles = $extended_test_profiles;
			unset($extended_test_profiles);
		}

		if($remove_duplicates)
		{
			$test_profiles = array_unique($test_profiles);
		}

		return $test_profiles;
	}
	public static function identifiers_to_objects($identifiers, &$archive_unknown_objects = false)
	{
		// Provide an array containing the location(s) of all test(s) for the supplied object name
		$objects = array();

		if(PTS_IS_CLIENT && !defined('CACHE_CHECK_FORCED'))
		{
			define('CACHE_CHECK_FORCED', true);
			pts_openbenchmarking::refresh_repository_lists(null);
		}

		foreach(pts_arrays::to_array($identifiers) as $identifier_item)
		{
			if(!self::eval_identifier_to_obj_array($objects, $identifier_item))
			{
				if(is_array($archive_unknown_objects))
				{
					// Unknown / nothing / broken
					$archive_unknown_objects[] = $identifier_item;
				}
			}
		}

		return $objects;
	}
	protected static function eval_identifier_to_obj_array(&$objects, &$identifier_item)
	{
		if($identifier_item instanceof pts_test_profile || $identifier_item instanceof pts_test_suite || $identifier_item instanceof pts_result_file)
		{
			$objects[] = $identifier_item;
		}
		else if(($tp_identifier = pts_test_profile::is_test_profile($identifier_item)))
		{
			// Object is a test
			$objects[] = new pts_test_profile($tp_identifier);
		}
		else if(($s = pts_test_suite::is_suite($identifier_item)))
		{
			// Object is a suite
			$objects[] = new pts_test_suite($s);
		}
		else if(pts_results::is_saved_result_file($identifier_item))
		{
			// Object is a saved results file
			$objects[] = new pts_result_file($identifier_item);
		}
		else if(pts_openbenchmarking::is_openbenchmarking_result_id($identifier_item))
		{
			// Object is an OpenBenchmarking.org result
			// Clone it locally so it's just handled like a pts_result_file
			$success = pts_openbenchmarking::clone_openbenchmarking_result($identifier_item);

			if($success)
			{
				$objects[] = new pts_result_file($identifier_item);
			}
		}
		else if(PTS_IS_CLIENT && pts_openbenchmarking::remote_test_profile_check($identifier_item) && ($tp_identifier = pts_test_profile::is_test_profile($identifier_item)))
		{
			// Object is a test profile fetched from a remote OpenBenchmarking / Phoromatic Server
			$objects[] = new pts_test_profile($tp_identifier);
		}
		else if(PTS_IS_CLIENT && pts_virtual_test_suite::is_virtual_suite($identifier_item))
		{
			// Object is a virtual suite
			$objects[] = new pts_virtual_test_suite($identifier_item);
		}
		else if(PTS_IS_CLIENT && isset($identifier_item[4]) && substr($identifier_item, -4) == '.xml' && is_file($identifier_item))
		{
			// See if it's pointing to a specific file
			$test_obj = new pts_result_file($identifier_item);
			if($test_obj->get_system_count() > 0 && $test_obj->get_test_count() > 0)
			{
				// Result file is an individual file on the file-system
				$objects[] = $test_obj;
			}
		}
		else
		{
			if(PTS_IS_CLIENT && $identifier_item != null && strpos($identifier_item, '/') !== false && ($ei = explode('/', $identifier_item)) && count($ei) == 2)
			{
				if(!pts_openbenchmarking::is_local_repo($ei[0]) && strlen($ei[0]) > 2 && ($ob_info = pts_openbenchmarking::ob_repo_exists($ei[0])))
				{
					echo '    ' . pts_client::cli_just_italic($identifier_item) . ' appears to be associated with the OpenBenchmarking.org account ' . pts_client::cli_just_bold($ob_info[1]) . ' (' . pts_client::cli_just_bold($ob_info[0]) . ') but is not currently enabled.' . PHP_EOL;
					echo '    Enable this OpenBenchmarking.org repository by running: ' . pts_client::cli_just_bold('phoronix-test-suite enable-repo ' . $ob_info[0]) . PHP_EOL;
				}
			}

			return false;
		}

		return true;
	}
	public static function identifier_to_object($identifier)
	{
		$return = pts_types::identifiers_to_objects($identifier);

		return isset($return[0]) ? $return[0] : false;
	}
	public static function is_result_file($identifier)
	{
		return pts_types::identifier_to_object($identifier) instanceof pts_result_file ? true : false;
	}
	public static function is_test_or_suite($identifier)
	{
		return pts_test_profile::is_test_profile($identifier) || pts_test_suite::is_suite($identifier);
	}
	private static function parse_xsd_types($type_name)
	{
		$values = array();
		$dom = new DOMDocument();
		$dom->load(pts_openbenchmarking::openbenchmarking_standards_path() . 'schemas/types.xsd');
		$types = $dom->getElementsByTagName('schema')->item(0)->getElementsByTagName('simpleType');

		for($i = 0; $i < $types->length; $i++)
		{
			if($types->item($i)->attributes->getNamedItem('name')->nodeValue == $type_name)
			{
				$enumerations = $types->item($i)->getElementsByTagName('restriction')->item(0)->getElementsByTagName('enumeration');

				for($j = 0; $j < $enumerations->length; $j++)
				{
					$values[] = $enumerations->item($j)->attributes->getNamedItem('value')->nodeValue;
				}
				break;
			}
		}

		return $values;
	}
}

?>
