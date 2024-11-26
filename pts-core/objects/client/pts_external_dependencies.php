<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2020, Phoronix Media
	Copyright (C) 2010 - 2020, Michael Larabel

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
	protected static $logger = null;

	public static function packages_that_provide($file)
	{
		$pkg_vendor = self::vendor_identifier('package-list');
		$provides = false;
		if($file != null && is_file(pts_exdep_generic_parser::get_external_dependency_path() . 'dependency-handlers/' . $pkg_vendor . '_dependency_handler.php'))
		{
			require_once(pts_exdep_generic_parser::get_external_dependency_path() . 'dependency-handlers/' . $pkg_vendor . '_dependency_handler.php');
			eval("\$provides = {$pkg_vendor}_dependency_handler::what_provides(\$file);");
		}

		if(empty($provides))
		{
			// Fallback to see if it's defined by the XML data
			$f = array($file => '');
			$t = false;
			$x = true;
			$provides = self::check_dependencies_missing_from_system($f, $t, $x);
		}
		return !empty($provides) && is_array($provides) ? $provides : false;
	}
	public static function startup_handler()
	{
		$pkg_vendor = self::vendor_identifier('package-list');
		if(is_file(pts_exdep_generic_parser::get_external_dependency_path() . 'dependency-handlers/' . $pkg_vendor . '_dependency_handler.php'))
		{
			$startup = null;
			require_once(pts_exdep_generic_parser::get_external_dependency_path() . 'dependency-handlers/' . $pkg_vendor . '_dependency_handler.php');
			eval("\$startup = {$pkg_vendor}_dependency_handler::startup_handler();");
			return $startup;
		}
		return false;
	}
	public static function install_dependencies(&$test_profiles, $no_prompts = false, $skip_tests_with_missing_dependencies = false, $report_progress = false)
	{
		// PTS External Dependencies install on distribution
		if(pts_env::read('NO_EXTERNAL_DEPENDENCIES') != false || pts_env::read('SKIP_EXTERNAL_DEPENDENCIES') == 1)
		{
			return true;
		}

		self::$logger = new pts_logger(null, 'phoronix-test-suite-dependencies.log', false);

		// Find all the tests that need to be checked
		$tests_to_check = array();
		foreach($test_profiles as $test_profile)
		{
			if(!in_array($test_profile, $tests_to_check) && $test_profile->is_supported())
			{
				$tests_to_check[] = $test_profile;
			}
		}
		self::$logger->log('Evaluating dependencies needed for: ' . implode(' ', $tests_to_check));

		// Find all of the POSSIBLE test dependencies
		$required_external_dependencies = array();
		$required_system_files = array();
		if($report_progress)
		{
			pts_client::$display->test_install_progress_start('Evaluating External Test Dependencies');
		}
		foreach($tests_to_check as &$test_profile)
		{
			foreach($test_profile->get_external_dependencies() as $test_dependency)
			{
				if(empty($test_dependency))
				{
					continue;
				}

				if(isset($required_external_dependencies[$test_dependency]) == false)
				{
					$required_external_dependencies[$test_dependency] = array();
				}

				$required_external_dependencies[$test_dependency][] = $test_profile;
			}
			foreach($test_profile->get_system_dependencies() as $test_dependency)
			{
				if(empty($test_dependency))
				{
					continue;
				}

				if(isset($required_system_files[$test_dependency]) == false)
				{
					$required_system_files[$test_dependency] = array();
				}

				$required_system_files[$test_dependency][] = $test_profile;
			}
		}

		if($skip_tests_with_missing_dependencies)
		{
			// Remove tests that have external dependencies that aren't satisfied and then return
			$generic_packages_needed = array();
			$required_external_dependencies_copy = $required_external_dependencies;
			$dependencies_to_install = self::check_dependencies_missing_from_system($required_external_dependencies_copy, $generic_packages_needed);
			self::remove_tests_with_missing_dependencies($test_profiles, $generic_packages_needed, $required_external_dependencies);
			return true;
		}

		// Does the user wish to skip any particular dependencies?
		if(($dependencies_to_skip = pts_env::read('SKIP_EXTERNAL_DEPENDENCIES')))
		{
			$dependencies_to_skip = explode(',', $dependencies_to_skip);

			foreach($dependencies_to_skip as $dependency_name)
			{
				if(isset($required_external_dependencies[$dependency_name]))
				{
					unset($required_external_dependencies[$dependency_name]);
				}
				if(isset($required_system_files[$dependency_name]))
				{
					unset($required_system_files[$dependency_name]);
				}
			}
		}

		// Make a copy for use to check at end of process to see if all dependencies were actually found
		$required_external_dependencies_copy = $required_external_dependencies;

		// Find the dependencies that are actually missing from the system
		$skip_warning_on_unmet_deps = false;
		$generic_packages_needed = array();
		$dependencies_to_install = self::check_dependencies_missing_from_system($required_external_dependencies, $generic_packages_needed, $skip_warning_on_unmet_deps, true);
		if($report_progress)
		{
			pts_client::$display->test_install_progress_completed();
		}

		// If it's automated and can't install without root, return true if there are no dependencies to do otherwise false
		if($no_prompts && phodevi::is_root() == false)
		{
			return count($dependencies_to_install) == 0;
		}

		if(!empty($dependencies_to_install))
		{
			// The 'common-dependencies' package is any general non-explicitly-required but nice-to-have packages like mesa-utils for providing glxinfo about the system
			// So if we're going to be installing external dependencies anyways, might as well try to see the common-dependencies are satisfied
			$common_test_dependencies['common-dependencies'] = array();
			$common_to_install = self::check_dependencies_missing_from_system($common_test_dependencies);
			if(!empty($common_to_install))
			{
				$dependencies_to_install = array_merge($dependencies_to_install, $common_to_install);
			}
		}

		$system_dependencies = self::check_for_missing_system_files($required_system_files, $report_progress);

		if(!empty($system_dependencies))
		{
			$dependencies_to_install = array_merge($dependencies_to_install, $system_dependencies);
		}

		$dependencies_to_install = array_unique($dependencies_to_install);

		// Do the actual dependency install process
		if(count($dependencies_to_install) > 0)
		{
			self::$logger->log('External dependencies requested for install: ' . implode(' ', $dependencies_to_install));
			self::install_packages_on_system($dependencies_to_install);
		}

		// There were some dependencies not supported on this OS or are missing from the distro's XML file
		if(count($required_external_dependencies) > 0 && count($dependencies_to_install) == 0 && $skip_warning_on_unmet_deps == false)
		{
			$exdep_generic_parser = new pts_exdep_generic_parser();
			$to_report = array();

			foreach(array_keys($required_external_dependencies) as $dependency)
			{
				$dependency_data = $exdep_generic_parser->get_package_data($dependency);

				if($dependency_data['possible_packages'] != null)
				{
					$to_report[] = $dependency_data['title'] . PHP_EOL . 'Possible Package Names: ' . $dependency_data['possible_packages'];
				}
			}

			if(count($to_report) > 0)
			{
				echo PHP_EOL . 'Some additional dependencies are required, but they could not be installed automatically for your operating system.' . PHP_EOL . 'Below are the software packages that must be installed.' . PHP_EOL . PHP_EOL;

				foreach($to_report as $report)
				{
					pts_client::$display->generic_heading($report);
				}

				if(!$no_prompts)
				{
					echo 'The above dependencies should be installed before proceeding. Press any key when you\'re ready to continue.';
					pts_user_io::read_user_input();
					echo PHP_EOL;
				}
			}
		}


		// Find the dependencies that are still missing from the system
		if(!$no_prompts && !defined('PHOROMATIC_PROCESS') && $skip_warning_on_unmet_deps == false)
		{
			$generic_packages_needed = array();
			$required_external_dependencies = $required_external_dependencies_copy;
			$dependencies_to_install = self::check_dependencies_missing_from_system($required_external_dependencies_copy, $generic_packages_needed);

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
						self::remove_tests_with_missing_dependencies($test_profiles, $generic_packages_needed, $required_external_dependencies);
						break;
					case 'REATTEMPT_DEP_INSTALL':
						// Recalculate needed system dependencies too
						$system_dependencies = self::check_for_missing_system_files($required_system_files, false);
						if(!empty($system_dependencies))
						{
							$dependencies_to_install = array_merge($dependencies_to_install, $system_dependencies);
						}
						self::install_packages_on_system($dependencies_to_install);
						break;
					case 'QUIT':
						exit(0);
				}
			}
		}

		return true;
	}
	protected static function remove_tests_with_missing_dependencies(&$test_profiles, $generic_packages_needed, $required_test_dependencies)
	{
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
	}
	public static function all_dependency_names()
	{
		$exdep_generic_parser = new pts_exdep_generic_parser();
		return $exdep_generic_parser->get_available_packages();
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
	private static function check_dependencies_missing_from_system(&$required_test_dependencies, &$generic_names_of_packages_needed = false, &$skip_warning_on_unmet_deps = false, $report_progress = false)
	{
		$generic_dependencies_parser = new pts_exdep_generic_parser();
		$vendor_dependencies_parser = new pts_exdep_platform_parser(self::vendor_identifier('package-list'));
		$skip_warning_on_unmet_deps = $vendor_dependencies_parser->skip_warning_on_unmet_dependencies();
		$kernel_architecture = phodevi::read_property('system', 'kernel-architecture');
		$needed_os_packages = array();

		$required_test_dep_count = count($required_test_dependencies);
		$i = 0;
		foreach($required_test_dependencies as $package => $dependents)
		{
			if($vendor_dependencies_parser->is_package($package))
			{
				$package_data = $vendor_dependencies_parser->get_package_data($package);
				$arch_compliant = empty($package_data['arch_specific']) || in_array($kernel_architecture, $package_data['arch_specific']);

				if(!empty($package_data['file_check']))
				{
					$add_dependency = self::file_missing_check($package_data['file_check']);
				}
				else if($generic_dependencies_parser->is_package($package))
				{
					// If the OS/platform-specific package didn't supply a file check list, obtain it from the generic listing
					$generic_package_data = $generic_dependencies_parser->get_package_data($package);
					$add_dependency = empty($generic_package_data['file_check']) || self::file_missing_check($generic_package_data['file_check']);
				}
				else
				{
					$add_dependency = true;
				}

				if($add_dependency && $arch_compliant && $package_data['os_package'] != null)
				{
					if(!in_array($package_data['os_package'], $needed_os_packages))
					{
						$needed_os_packages[] = $package_data['os_package'];
					}
					if($generic_names_of_packages_needed !== false && !in_array($package, $generic_names_of_packages_needed))
					{
						$generic_names_of_packages_needed[] = $package;
					}
				}
				else
				{
					unset($required_test_dependencies[$package]);
				}
			}
			$i++;
			if($report_progress)
			{
				pts_client::$display->test_install_progress_update(($i / $required_test_dep_count));
			}
		}

		if(count($required_test_dependencies) > 0)
		{
			foreach($required_test_dependencies as $i => $dependency)
			{
				$package_data = $generic_dependencies_parser->get_package_data($i);
				$file_present = !empty($package_data['file_check']) && !self::file_missing_check($package_data['file_check']);

				if($file_present)
				{
					unset($required_test_dependencies[$i]);
				}
			}
		}

		return $needed_os_packages;
	}
	private static function check_for_missing_system_files(&$required_system_files, $report_progress = false)
	{
		$kernel_architecture = phodevi::read_property('system', 'kernel-architecture');
		$needed_os_packages = array();

		if($report_progress)
		{
			pts_client::$display->test_install_progress_start('Evaluating System Dependencies');
			$system_file_check_count = count($required_system_files);
		}
		$i = 0;
		foreach(array_keys($required_system_files) as $file)
		{
			$present = false;
			if(is_file($file))
			{
				$present = true;
			}
			if(strpos($file, '.h') !== false && is_file('/usr/include/' . $file))
			{
				$present = true;
			}
			else if(strpos($file, '.h') !== false && glob('/usr/include/*-linux-gnu/' . $file) != false)
			{
				$present = true;
			}
			else if(strpos($file, '.so') !== false && glob('/usr/lib*/' . $file) != false)
			{
				$present = true;
			}
			else if(strpos($file, '.so') !== false && glob('/usr/lib*/*/' . $file) != false)
			{
				$present = true;
			}
			else if(pts_client::executable_in_path($file))
			{
				$present = true;
			}

			if(!$present)
			{
				$processed_pkgs = self::packages_that_provide($file);

				if(!empty($processed_pkgs))
				{
					foreach($processed_pkgs as $pkg)
					{
						$needed_os_packages[] = $pkg;
					}
					self::$logger->log('System dependency solver for "' . $file . '" found: ' . implode(' ', $processed_pkgs));
				}
			}
			$i++;
			if($report_progress)
			{
				pts_client::$display->test_install_progress_update(($i / $system_file_check_count));
			}
		}
		if($report_progress)
		{
			pts_client::$display->test_install_progress_completed();
		}

		return $needed_os_packages;
	}
	private static function is_present($file)
	{
		return is_file($file) || (strpos($file, '*') != false && glob($file));
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

				if(is_dir($file[$i]) || self::is_present($file[$i]))
				{
					$file_is_there = true;
				}
				else if(isset($file[$i][1]) && $file[$i][0] != '/')
				{
					// See if it's some relative command/path

					if(substr($file[$i], -2) == '.h' || substr($file[$i], -4) == '.hpp')
					{
						// May just be a relative header file to look for...
						$possible_paths = array_merge(array('/usr/local/include/', '/usr/target/include/', '/usr/include/',  '/opt/homebrew/include/', '/opt/homebrew/opt', '/opt/homebrew/'), pts_file_io::glob('/usr/include/*-linux-gnu/'));
						foreach($possible_paths as $path)
						{
							if(self::is_present($path . '/' . $file[$i]))
							{
								$file_is_there = true;
							}
						}
					}
					else if(strpos($file[$i], '.so') !== false || substr($file[$i], -2) == '.a')
					{
						// May just be a relative shared library to look for...
						$possible_paths = array_merge(array('/usr/local/lib/', '/usr/lib/', '/usr/lib64/', '/usr/lib/arm-linux-gnueabihf/',  '/opt/homebrew/include/', '/opt/homebrew/opt', '/opt/homebrew/'), pts_file_io::glob('/usr/lib/*-linux-gnu/'));

						if(getenv('LD_LIBRARY_PATH'))
						{
							foreach(explode(':', getenv('LD_LIBRARY_PATH')) as $path)
							{
								$possible_paths[] = $path . '/';
							}
						}

						foreach($possible_paths as $path)
						{
							if(self::is_present($path . '/' . $file[$i]))
							{
								$file_is_there = true;
							}
						}
					}
					else if(strpos($file[$i], '/') === false)
					{
						// May just be a command to look for...
						if(pts_client::executable_in_path($file[$i]))
						{
							$file_is_there = true;
						}
					}
				}
			}
			$file_missing = $file_missing || !$file_is_there;
		}
		return $file_missing;
	}
	private static function install_packages_on_system($os_packages_to_install)
	{
		// Do the actual installing process of packages using the distribution's package management system
		$vendor_install_file = pts_exdep_generic_parser::get_external_dependency_path() . 'scripts/install-' . self::vendor_identifier('installer') . '-packages.sh';
		$pkg_vendor = self::vendor_identifier('package-list');

		// Rebuild the array index since some OS package XML tags provide multiple package names in a single string
		$os_packages_to_install = array_unique(explode(' ', implode(' ', $os_packages_to_install)));

		if(is_file($vendor_install_file))
		{
			// hook into pts_client::$display here if it's desired
			echo PHP_EOL . 'The following dependencies are needed and will be installed: ' . PHP_EOL . PHP_EOL;
			echo pts_user_io::display_text_list($os_packages_to_install);
			echo PHP_EOL . 'This process may take several minutes.' . PHP_EOL;

			echo shell_exec('sh ' . $vendor_install_file . ' ' . implode(' ', $os_packages_to_install));
		}
		else if(is_file(pts_exdep_generic_parser::get_external_dependency_path() . 'dependency-handlers/' . $pkg_vendor . '_dependency_handler.php'))
		{
			$installed = null;
			require_once(pts_exdep_generic_parser::get_external_dependency_path() . 'dependency-handlers/' . $pkg_vendor . '_dependency_handler.php');
			eval("\$installed = {$pkg_vendor}_dependency_handler::install_dependencies(\$os_packages_to_install);");
			return $installed;
		}
		else
		{
			echo 'Distribution install script not found!';
		}
	}
	public static function vendor_identifier($type)
	{
		$os_vendor = phodevi::read_property('system', 'vendor-identifier');

		switch($type)
		{
			case 'package-list':
				$file_check_success = is_file(pts_exdep_generic_parser::get_external_dependency_path() . 'xml/' . $os_vendor . '-packages.xml');
				break;
			case 'installer':
				$file_check_success = is_file(pts_exdep_generic_parser::get_external_dependency_path() . 'scripts/install-' . $os_vendor . '-packages.sh');
				break;
		}

		if($file_check_success == false)
		{
			// Check the aliases to figure out the upstream distribution
			$vend_id = $os_vendor;
			$os_vendor = false;
			$exdep_generic_parser = new pts_exdep_generic_parser();
			foreach($exdep_generic_parser->get_vendors_list() as $this_vendor)
			{
				$exdep_platform_parser = new pts_exdep_platform_parser($this_vendor);
				$aliases = $exdep_platform_parser->get_aliases();

				if(in_array($vend_id, $aliases))
				{
					$os_vendor = $this_vendor;
					break;
				}
			}

			if($os_vendor == false)
			{
				// Attempt to match the current operating system by seeing what package manager matches
				foreach($exdep_generic_parser->get_vendors_list() as $this_vendor)
				{
					$exdep_platform_parser = new pts_exdep_platform_parser($this_vendor);
					$package_manager = $exdep_platform_parser->get_package_manager();

					if($package_manager != null && pts_client::executable_in_path($package_manager))
					{
						$os_vendor = $this_vendor;
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
		$generic_dependencies_parser = new pts_exdep_generic_parser();

		foreach($generic_dependencies_parser->get_available_packages() as $package)
		{
			if(in_array($package, $names))
			{
				$package_data = $generic_dependencies_parser->get_package_data($package);
				$titles[] = $package_data['title'];
			}
		}
		sort($titles);

		return $titles;
	}
}

?>
