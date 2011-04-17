<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2011, Phoronix Media
	Copyright (C) 2010 - 2011, Michael Larabel

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
	public static function install_dependencies(&$test_profiles)
	{
		// PTS External Dependencies install on distribution

		if(phodevi::is_windows() || phodevi::is_macosx() || pts_client::read_env('SKIP_EXTERNAL_DEPENDENCIES_CHECK'))
		{
			// Windows doesn't use any external dependencies
			return true;
		}

		// Find all the tests that need to be checked
		$tests_to_check = array();
		foreach($test_profiles as $test_profile)
		{
			if(!in_array($test_profile, $tests_to_check) && $test_profile->is_supported())
			{
				array_push($tests_to_check, $test_profile);
			}
		}

		// Find all of the POSSIBLE test dependencies
		$required_test_dependencies = array();
		foreach($tests_to_check as &$test_profile)
		{
			foreach($test_profile->get_dependencies() as $test_dependency)
			{
				if(empty($test_dependency))
				{
					continue;
				}

				if(isset($required_test_dependencies[$test_dependency]) == false)
				{
					$required_test_dependencies[$test_dependency] = array();
				}

				array_push($required_test_dependencies[$test_dependency], $test_profile);
			}
		}

		// Make a copy for use to check at end of process to see if all dependencies were actually found
		$required_test_dependencies_copy = $required_test_dependencies;

		// Find the dependencies that are actually missing from the system
		$dependencies_to_install = self::check_dependencies_missing_from_system($required_test_dependencies);

		// If it's automated and can't install without root, return true if there are no dependencies to do otherwise false
		if((pts_c::$test_flags & pts_c::auto_mode) && phodevi::read_property('system', 'username') != 'root')
		{
			return count($dependencies_to_install) == 0;
		}

		// Do the actual dependency install process
		if(count($dependencies_to_install) > 0)
		{
			self::install_packages_on_system($dependencies_to_install);
		}

		// There were some dependencies not supported on this OS or are missing from the distro's XML file
		if(count($required_test_dependencies) > 0 && count($dependencies_to_install) == 0)
		{
			$xml_parser = new nye_XmlReader(PTS_EXDEP_PATH . 'xml/generic-packages.xml');
			$package_name = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ExternalDependencies/Package/GenericName');
			$title = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ExternalDependencies/Package/Title');
			$possible_packages = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ExternalDependencies/Package/PossibleNames');
			$file_check = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ExternalDependencies/Package/FileCheck');
			$required_test_dependencies_names = array_keys($required_test_dependencies);

			$to_report = array();

			foreach(array_keys($package_name) as $i)
			{
				if(isset($required_test_dependencies[$package_name[$i]]))
				{
					array_push($to_report, $title[$i] . PHP_EOL . 'Possible Package Names: ' . $possible_packages[$i]);
				}
			}

			if(count($to_report) > 0)
			{
				echo PHP_EOL . 'Some additional dependencies are required, but they could not be installed automatically for your operating system.\nBelow are the software packages that must be installed.' . PHP_EOL . PHP_EOL;

				foreach($to_report as $report)
				{
					pts_client::$display->generic_heading($report);
				}

				if((pts_c::$test_flags ^ pts_c::batch_mode) && (pts_c::$test_flags ^ pts_c::auto_mode))
				{
					echo 'The above dependencies should be installed before proceeding. Press any key when you\'re ready to continue.';
					pts_user_io::read_user_input();
				}
			}
		}


		// Find the dependencies that are still missing from the system
		if((pts_c::$test_flags ^ pts_c::batch_mode) && (pts_c::$test_flags ^ pts_c::auto_mode))
		{
			$generic_packages_needed = array();
			$required_test_dependencies = $required_test_dependencies_copy;
			$dependencies_to_install = self::check_dependencies_missing_from_system($required_test_dependencies_copy, $generic_packages_needed);

			if(count($generic_packages_needed) > 0)
			{
				echo PHP_EOL . 'There are dependencies still missing from the system:' . PHP_EOL;
				echo pts_user_io::display_text_list(self::generic_names_to_titles($generic_packages_needed));

				$actions = array(
					'IGNORE' => 'Ignore missing dependencies and proceed with installation.',
					'SKIP_TESTS_WITH_MISSING_DEPS' => 'Skip installing the tests with missing dependencies.',
					'REATTEMPT_DEP_INSTALL' => 'Re-attempt to install the missing dependencies.',
					'QUIT' => 'Quit the current Phoronix Test Suite process.'
					);

				$selected_action = pts_user_io::prompt_text_menu('Missing dependencies action', $actions, false, true);

				switch($selected_action)
				{
					case 'IGNORE':
						break;
					case 'SKIP_TESTS_WITH_MISSING_DEPS':
						// Unset the tests that have dependencies still missing
						foreach($generic_packages_needed as $pkg)
						{
							if(isset($required_test_dependencies[$pkg]))
							{
								foreach($required_test_dependencies[$pkg] as $test_with_this_dependency)
								{
									if(($index = array_search($test_with_this_dependency, $test_profiles)) !== false)
									{
										unset($test_profiles[$index]);
									}
								}
							} 
						}
						break;
					case 'REATTEMPT_DEP_INSTALL':
						self::install_packages_on_system($dependencies_to_install);
						break;
					case 'QUIT':
						exit(0);
				}
			}
		}

		return true;
	}
	public static function all_dependency_names()
	{
		$xml_parser = new nye_XmlReader(PTS_EXDEP_PATH . 'xml/generic-packages.xml');

		return $xml_parser->getXMLArrayValues('PhoronixTestSuite/ExternalDependencies/Package/GenericName');
	}
	public static function all_dependency_titles()
	{
		$dependency_names = self::all_dependency_names();
		return self::generic_names_to_titles($dependency_names);
	}
	public static function missing_dependency_names()
	{
		$all_test_dependencies = array();
		$all_missing_dependencies = array();

		foreach(self::all_dependency_names() as $name)
		{
			$all_test_dependencies[$name] = array();
		}

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
	private static function check_dependencies_missing_from_system(&$required_test_dependencies, &$generic_names_of_packages_needed = false)
	{
		$distro_vendor_xml = PTS_EXDEP_PATH . 'xml/' . self::vendor_identifier('package-list') . '-packages.xml';
		$needed_os_packages = array();

		if(is_file($distro_vendor_xml))
		{
			$xml_parser = new nye_XmlReader($distro_vendor_xml);
			$generic_package = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ExternalDependencies/Package/GenericName');
			$distro_package = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ExternalDependencies/Package/PackageName');
			$file_check = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ExternalDependencies/Package/FileCheck');
			$arch_specific = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ExternalDependencies/Package/ArchitectureSpecific');
			$kernel_architecture = phodevi::read_property('system', 'kernel-architecture');

			foreach(array_keys($generic_package) as $i)
			{
				if(empty($generic_package[$i]))
				{
					continue;
				}

				if(isset($required_test_dependencies[$generic_package[$i]]))
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

					unset($required_test_dependencies[$generic_package[$i]]);
				}
			}
		}

		if(count($required_test_dependencies) > 0)
		{
			$xml_parser = new nye_XmlReader(PTS_EXDEP_PATH . 'xml/generic-packages.xml');
			$generic_package_name = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ExternalDependencies/Package/GenericName');
			$generic_file_check = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ExternalDependencies/Package/FileCheck');

			foreach(array_keys($generic_package_name) as $i)
			{
				if(empty($generic_package_name[$i]))
				{
					continue;
				}

				if(isset($required_test_dependencies[$generic_package_name[$i]]))
				{
					$file_present = !empty($generic_file_check[$i]) && !self::file_missing_check($generic_file_check[$i]);

					if($file_present)
					{
						unset($required_test_dependencies[$generic_package_name[$i]]);
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
			$file = explode('OR', $file);

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
		$vendor_install_file = PTS_EXDEP_PATH . 'scripts/install-' . self::vendor_identifier('installer') . '-packages.sh';

		// Rebuild the array index since some OS package XML tags provide multiple package names in a single string
		$os_packages_to_install = explode(' ', implode(' ', $os_packages_to_install));

		if(is_file($vendor_install_file))
		{
			// hook into pts_client::$display here if it's desired
			echo PHP_EOL . 'The following dependencies are needed and will be installed: ' . PHP_EOL . PHP_EOL;
			echo pts_user_io::display_text_list($os_packages_to_install);
			echo PHP_EOL . 'This process may take several minutes.' . PHP_EOL;

			echo shell_exec('sh ' . $vendor_install_file . ' ' . implode(' ', $os_packages_to_install));
		}
		else
		{
			if(phodevi::is_macosx() == false)
			{
				echo 'Distribution install script not found!';
			}
		}
	}
	private static function vendor_identifier($type)
	{
		$os_vendor = phodevi::read_property('system', 'vendor-identifier');

		switch($type)
		{
			case 'package-list':
				$file_check_success = is_file(PTS_EXDEP_PATH . 'xml/' . $os_vendor . '-packages.xml');
				break;
			case 'installer':
				$file_check_success = is_file(PTS_EXDEP_PATH . 'scripts/install-' . $os_vendor . '-packages.sh');
				break;
			default:
				return false;
		}

		if($file_check_success == false)
		{
			$os_vendor = false;
			$vendors_alias_file = pts_file_io::file_get_contents(PTS_CORE_STATIC_PATH . 'lists/software-vendor-aliases.list');
			$vendors_r = explode("\n", $vendors_alias_file);

			foreach($vendors_r as &$vendor)
			{
				$vendor_r = pts_strings::trim_explode('=', $vendor);

				if(count($vendor_r) == 2)
				{
					if($os_vendor == $vendor_r[0])
					{
						$os_vendor = $vendor_r[1];
						break;
					}
				}
			}

			if($os_vendor == false && is_file('/etc/debian_version'))
			{
				// A simple last fall-back
				$os_vendor = 'ubuntu';
			}
		}

		return $os_vendor;
	}
	private static function generic_names_to_titles($names)
	{
		$titles = array();
		$xml_parser = new nye_XmlReader(PTS_EXDEP_PATH . 'xml/generic-packages.xml');
		$package_name = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ExternalDependencies/Package/GenericName');
		$title = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ExternalDependencies/Package/Title');

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
