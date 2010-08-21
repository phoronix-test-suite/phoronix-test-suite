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

class pts_external_dependencies
{
	public static function install_dependencies(&$identifiers)
	{
		// PTS External Dependencies install on distribution

		if(IS_WINDOWS || pts_client::read_env("SKIP_EXTERNAL_DEPENDENCIES_CHECK"))
		{
			// Windows doesn't use any external dependencies
			return true;
		}

		// Find all the tests that need to be checked
		$tests_to_check = array();
		foreach(pts_arrays::to_array($identifiers) as $identifier)
		{
			foreach(pts_contained_tests($identifier, true) as $test)
			{
				$test_profile = new pts_test_profile($test);

				if(!in_array($test, $tests_to_check) && $test_profile->is_supported())
				{
					array_push($tests_to_check, $test);
				}
			}
		}

		// Find all of the POSSIBLE test dependencies
		$required_test_dependencies = array();
		foreach($tests_to_check as $identifier)
		{
			foreach(self::required_test_dependencies($identifier) as $test_dependency)
			{
				if(!in_array($test_dependency, $required_test_dependencies) && !empty($test_dependency))
				{
					array_push($required_test_dependencies, $test_dependency);
				}
			}
		}

		// Find the dependencies that are actually missing from the system
		$dependencies_to_install = self::check_dependencies_missing_from_system($required_test_dependencies);

		// If it's automated and can't install without root, return true if there are no dependencies to do otherwise false
		if(pts_is_assignment("AUTOMATED_MODE") && phodevi::read_property("system", "username") != "root")
		{
			return count($dependencies_to_install) == 0;
		}

		// Do the actual dependency install process
		if(count($dependencies_to_install) > 0)
		{
			self::install_packages_on_system($dependencies_to_install);
		}

		// There were some dependencies not supported on this OS or are missing from the distro's XML file
		if(count($required_test_dependencies) > 0)
		{
			echo "\nSome additional dependencies are required, but they could not be installed automatically for your operating system.\nBelow are the software packages that must be installed.\n\n";

			$xml_parser = new pts_external_dependencies_tandem_XmlReader(STATIC_DIR . "distro-xml/generic-packages.xml");
			$package_name = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_GENERIC);
			$title = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_TITLE);
			$possible_packages = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_POSSIBLENAMES);
			$file_check = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_FILECHECK);

			foreach(array_keys($package_name) as $i)
			{
				if(in_array($package_name[$i], $required_test_dependencies))
				{
					pts_client::$display->generic_heading($title[$i] . "\nPossible Package Names: " . $possible_packages[$i]);
				}
			}

			echo "The above dependencies should be installed before proceeding. Press any key when you're ready to continue.";

			if(!pts_read_assignment("IS_BATCH_MODE") && !pts_is_assignment("AUTOMATED_MODE"))
			{		
				pts_user_io::read_user_input();
			}
		}

		// TODO: make better assumption than assume it all worked out since it reached this far
		return true;
	}
	public static function all_dependency_names()
	{
		$all_test_dependencies = array();
		foreach(pts_contained_tests("all", true) as $identifier)
		{
			foreach(self::required_test_dependencies($identifier) as $test_dependency)
			{
				if(!in_array($test_dependency, $all_test_dependencies))
				{
					array_push($all_test_dependencies, $test_dependency);
				}
			}
		}

		return $all_test_dependencies;
	}
	public static function all_dependency_titles()
	{
		$dependency_names = self::all_dependency_names();
		return self::generic_names_to_titles($dependency_names);
	}
	public static function missing_dependency_names()
	{
		$all_test_dependencies = self::all_dependency_names();
		$all_missing_dependencies = array();
		self::check_dependencies_missing_from_system($all_test_dependencies, $all_missing_dependencies);
		sort($all_missing_dependencies);

		return $all_missing_dependencies;
	}
	public static function missing_dependency_titles()
	{
		$dependency_names = self::missing_dependency_names();
		return self::generic_names_to_titles($dependency_names);
	}
	public static function installed_dependency_names()
	{
		$installed_test_dependencies = array_diff(self::all_dependency_names(), self::missing_dependency_names());
		sort($installed_test_dependencies);

		return $installed_test_dependencies;
	}
	public static function installed_dependency_titles()
	{
		$dependency_names = self::installed_dependency_names();
		return self::generic_names_to_titles($dependency_names);
	}
	private static function required_test_dependencies($identifier)
	{
		// Install from a list of external dependencies
		$dependencies_needed = false;
		$test_profile = new pts_test_profile($identifier);

		return $test_profile->get_dependencies();
	}
	private static function check_dependencies_missing_from_system(&$required_test_dependencies, &$generic_names_of_packages_needed = false)
	{
		$distro_vendor_xml = STATIC_DIR . "distro-xml/" . self::vendor_identifier() . "-packages.xml";
		$needed_os_packages = array();

		$xml_parser = new pts_external_dependencies_tandem_XmlReader(STATIC_DIR . "distro-xml/generic-packages.xml");
		$generic_package_name = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_GENERIC);
		$generic_file_check = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_FILECHECK);

		if(is_file($distro_vendor_xml))
		{
			$xml_parser = new pts_external_dependencies_tandem_XmlReader($distro_vendor_xml);
			$generic_package = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_GENERIC);
			$distro_package = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_SPECIFIC);
			$file_check = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_FILECHECK);
			$arch_specific = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_ARCHSPECIFIC);
			$kernel_architecture = phodevi::read_property("system", "kernel-architecture");

			foreach(array_keys($generic_package) as $i)
			{
				if(empty($generic_package[$i]))
				{
					continue;
				}

				if(($key = array_search($generic_package[$i], $required_test_dependencies)) !== false)
				{
					$add_dependency = empty($file_check[$i]) || self::file_missing_check($file_check[$i]);
					$arch_compliant = empty($arch_specific[$i]) || in_array($kernel_architecture, pts_strings::comma_explode($arch_specific[$i]));

					if($add_dependency && $arch_compliant)
					{
						if(!in_array($distro_package[$i], $needed_os_packages))
						{
							array_push($needed_os_packages, $distro_package[$i]);
						}
						if($generic_names_of_packages_needed !== false && !in_array($generic_package[$i], $generic_names_of_packages_needed))
						{
							array_push($generic_names_of_packages_needed, $generic_package[$i]);
						}
					}

					unset($required_test_dependencies[$key]);
				}
				else if(($key = array_search($generic_package[$i], $generic_package_name)) !== false)
				{
					$file_present = !empty($generic_file_check[$i]) && !self::file_missing_check($generic_file_check[$i]);

					if($file_present)
					{
						unset($required_test_dependencies[$key]);
					}					
				}
			}
		}
		else
		{
			foreach(array_keys($generic_package_name) as $i)
			{
				if(empty($generic_package_name[$i]))
				{
					continue;
				}

				if(($key = array_search($generic_package_name[$i], $required_test_dependencies)) !== false)
				{
					$file_present = !empty($generic_file_check[$i]) && !self::file_missing_check($generic_file_check[$i]);

					if($file_present)
					{
						unset($required_test_dependencies[$key]);
					}
				}
			}
		}

		return $needed_os_packages;
	}
	private static function file_missing_check($file_arr)
	{
		// Checks if file is missing
		$file_missing = false;

		if(!is_array($file_arr))
		{
			$file_arr = pts_strings::comma_explode($file_arr);
		}

		foreach($file_arr as $file)
		{
			$file_is_there = false;
			$file = explode("OR", $file);

			for($i = 0; $i < count($file) && $file_is_there == false; $i++)
			{
				$file[$i] = trim($file[$i]);

				if(is_file($file[$i]) || is_dir($file[$i]) || is_link($file[$i]))
				{
					$file_is_there = true;
				}
			}
			$file_missing = $file_missing || !$file_is_there;
		}

		return $file_missing;
	}
	private static function install_packages_on_system($os_packages_to_install)
	{
		// Do the actual installing process of packages using the distribution's package management system
		$vendor_install_file = STATIC_DIR . "distro-scripts/install-" . self::vendor_identifier() . "-packages.sh";

		// Rebuild the array index since some OS package XML tags provide multiple package names in a single string
		$os_packages_to_install = explode(' ', implode(' ', $os_packages_to_install));

		if(is_file($vendor_install_file))
		{
			// hook into pts_client::$display here if it's desired
			echo "\nThe following dependencies are needed and will be installed: \n\n";
			echo pts_user_io::display_text_list($os_packages_to_install);
			echo "\nThis process may take several minutes.\n";

			echo shell_exec("sh " . $vendor_install_file . ' ' . implode(' ', $os_packages_to_install));
		}
		else
		{
			if(!IS_MACOSX)
			{
				echo "Distribution install script not found!";
			}
		}
	}
	private static function vendor_identifier()
	{
		$os_vendor = phodevi::read_property("system", "vendor-identifier");

		if(!is_file(STATIC_DIR . "distro-xml/" . $os_vendor . "-packages.xml") && !is_file(STATIC_DIR . "distro-scripts/install-" . $os_vendor . "-packages.sh"))
		{
			$vendors_alias_file = pts_file_io::file_get_contents(STATIC_DIR . "lists/software-vendor-aliases.list");
			$vendors_r = explode("\n", $vendors_alias_file);

			foreach($vendors_r as &$vendor)
			{
				$vendor_r = pts_strings::trim_explode("=", $vendor);

				if(count($vendor_r) == 2)
				{
					if($os_vendor == $vendor_r[0])
					{
						$os_vendor = $vendor_r[1];
						break;
					}
				}
			}
		}

		return $os_vendor;
	}
	private static function generic_names_to_titles($names)
	{
		$titles = array();
		$xml_parser = new pts_external_dependencies_tandem_XmlReader(STATIC_DIR . "distro-xml/generic-packages.xml");
		$package_name = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_GENERIC);
		$title = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_TITLE);

		foreach(array_keys($package_name) as $i)
		{
			if(in_array($package_name[$i], $names))
			{
				array_push($titles, $title[$i]);
			}
		}
		sort($titles);

		return $titles;
	}
}

?>
