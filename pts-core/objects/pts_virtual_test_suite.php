<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2022, Phoronix Media
	Copyright (C) 2008 - 2022, Michael Larabel

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

class pts_virtual_test_suite extends pts_test_suite
{
	private $repo;
	private $virtual;
	protected static $external_dependency_suites;

	public function __construct($identifier)
	{
		parent::__construct();
		$this->set_identifier($identifier);

		$identifier = explode('/', $identifier);
		$this->repo = isset($identifier[1]) ? $identifier[0] : null;
		$this->virtual = isset($identifier[1]) ? $identifier[1] : $identifier[0];

		// Read the OpenBenchmarking.org repository index
		if($this->repo == null)
		{
			$repo_index = array('tests' => array());
			foreach(pts_openbenchmarking::linked_repositories() as $repo)
			{
				$temp_index = pts_openbenchmarking::read_repository_index($repo);
				if(isset($temp_index['tests']))
				{
					$repo_index['tests'] = array_merge($temp_index['tests'], $repo_index['tests']);
				}
			}
		}
		else
		{
			$repo_index = pts_openbenchmarking::read_repository_index($this->repo);
		}

		if(!isset($repo_index['tests']) || !is_array($repo_index['tests']) || count($repo_index['tests']) < 1)
		{
			return;
		}

		foreach($repo_index['tests'] as $test_identifier => &$test)
		{
			if((!empty($test['supported_platforms']) && !in_array(phodevi::operating_system(), $test['supported_platforms'])) || empty($test['title']))
			{
				unset($repo_index['tests'][$test_identifier]);
			}
		}

		if($this->virtual == 'installed')
		{
			$this->set_title('Installed Tests');
			$this->set_description('This is a collection of test profiles found within the specified OpenBenchmarking.org repository that are already installed on the system under test.');
			foreach($repo_index['tests'] as $test_identifier => &$test)
			{
				$test_version = array_shift($test['versions']);
				$test_profile = new pts_test_profile($this->repo . '/' . $test_identifier . '-' . $test_version);

				if($test_profile->get_display_format() != 'BAR_GRAPH')
				{
					// Also ignore these tests
					continue;
				}
				if($test_profile->test_installation == false || $test_profile->test_installation->is_installed() == false)
				{
					// Test is not installed
					continue;
				}

				if($test_profile->is_supported(false))
				{
					// All checks passed, add to virtual suite
					$this->add_to_suite($test_profile);
				}
			}
		}
		else if($this->virtual == 'all')
		{
			$this->set_title('All Tests in ' . $this->repo);
			$this->set_description('This is a collection of all supported test profiles found within the specified OpenBenchmarking.org repository.');
			foreach($repo_index['tests'] as $test_identifier => &$test)
			{
				$test_version = array_shift($test['versions']);
				$test_profile = new pts_test_profile($this->repo . '/' . $test_identifier . '-' . $test_version);

				if($test_profile->get_display_format() != 'BAR_GRAPH' || !in_array($test_profile->get_license(), array('Free', 'Non-Free')) || $test_profile->get_status() != 'Verified')
				{
					// Also ignore these tests
					continue;
				}

				if($test_profile->is_supported(false))
				{
					// All checks passed, add to virtual suite
					$this->add_to_suite($test_profile);
				}
			}
		}
		else if($this->virtual == 'everything')
		{
			$this->set_title('Everything in ' . $this->repo);
			$this->set_description('This is a collection of all test profiles found within the specified OpenBenchmarking.org repository, including unsupported tests, etc.');
			foreach($repo_index['tests'] as $test_identifier => &$test)
			{
				$test_version = array_shift($test['versions']);
				$test_profile = new pts_test_profile($this->repo . '/' . $test_identifier . '-' . $test_version);

				$this->add_to_suite($test_profile);
			}
		}
		else if($this->virtual == 'compiler')
		{
			$this->set_title('C/C++ Compiler Benchmark Workloads In ' . $this->repo);
			$this->set_description('This is a collection of test profiles often useful for C/C++ compiler benchmarks and where the test profiles will respect CFLAGS/CXXFLAGS environment variables.');
			foreach($repo_index['tests'] as $test_identifier => &$test)
			{
				if(strtolower($test['test_type']) == 'graphics' || $test['status'] != 'Verified')
				{
					continue;
				}
				$test_version = array_shift($test['versions']);
				$test_profile = new pts_test_profile($this->repo . '/' . $test_identifier . '-' . $test_version);
				if($test_profile->get_display_format() != 'BAR_GRAPH' || !in_array($test_profile->get_license(), array('Free', 'Non-Free')) || !$test_profile->is_supported(false))
				{
					continue;
				}

				$overview_data = $test_profile->get_generated_data(false);
				if(isset($overview_data['capabilities']['honors_cflags']) && $overview_data['capabilities']['honors_cflags'] == 1)
				{
					$this->add_to_suite($test_profile);
				}
			}
		}
		else if($this->virtual == 'multicore')
		{
			$this->set_title('Multi-Core/Multi-Threaded Workloads In ' . $this->repo);
			$this->set_description('This is a collection of test profiles that have been detected to be CPU multi-threaded capable.');
			foreach($repo_index['tests'] as $test_identifier => &$test)
			{
				if(strtolower($test['test_type']) == 'graphics' || $test['status'] != 'Verified')
				{
					continue;
				}
				$test_version = array_shift($test['versions']);
				$test_profile = new pts_test_profile($this->repo . '/' . $test_identifier . '-' . $test_version);
				if($test_profile->get_display_format() != 'BAR_GRAPH' || !in_array($test_profile->get_license(), array('Free', 'Non-Free')) || !$test_profile->is_supported(false))
				{
					continue;
				}

				$overview_data = $test_profile->get_generated_data(false);
				if(isset($overview_data['capabilities']['scales_cpu_cores']) && $overview_data['capabilities']['scales_cpu_cores'] !== null && $overview_data['capabilities']['scales_cpu_cores'])
				{
					$this->add_to_suite($test_profile);
				}
			}
		}
		else if($this->virtual == 'single-threaded')
		{
			$this->set_title('Single-Threaded Workloads In ' . $this->repo);
			$this->set_description('This is a collection of test profiles that have been detected to be single-threaded or only very poorly CPU threaded.');
			foreach($repo_index['tests'] as $test_identifier => &$test)
			{
				if(strtolower($test['test_type']) == 'graphics' || $test['status'] != 'Verified')
				{
					continue;
				}
				$test_version = array_shift($test['versions']);
				$test_profile = new pts_test_profile($this->repo . '/' . $test_identifier . '-' . $test_version);
				if($test_profile->get_display_format() != 'BAR_GRAPH' || !in_array($test_profile->get_license(), array('Free', 'Non-Free')) || !$test_profile->is_supported(false))
				{
					continue;
				}

				$overview_data = $test_profile->get_generated_data(false);
				if(isset($overview_data['capabilities']['scales_cpu_cores']) && $overview_data['capabilities']['scales_cpu_cores'] !== null && !$overview_data['capabilities']['scales_cpu_cores'])
				{
					$this->add_to_suite($test_profile);
				}
			}
		}
		else if($this->virtual == 'riscv' || $this->virtual == 'aarch64')
		{
			switch($this->virtual)
			{
				case 'riscv':
					$arch_friendly = 'RISC-V';
					$arch_strings = array('riscv64', 'riscv32');
					break;
				case 'aarch64':
					$arch_friendly = '64-bit Arm / AArch64';
					$arch_strings = array('aarch64'); // Add 'arm64' for macOS coverage but that includes then Rosetta software...
					break;
			}
			$this->set_title($arch_friendly . ' Tests In ' . $this->repo);
			$this->set_description('This is a collection of test profiles where there have been successful benchmark results submitted to OpenBenchmarking.org from ' . $arch_friendly . ' CPU architecture hardware, i.e. these tests are proven to be ' . $arch_friendly . ' compatible though not necessarily all compatible test profiles for the given architecture - just those with submitted public results previously on OpenBenchmarking.org.');
			foreach($repo_index['tests'] as $test_identifier => &$test)
			{
				if(strtolower($test['test_type']) == 'graphics' || $test['status'] != 'Verified')
				{
					continue;
				}
				$test_version = array_shift($test['versions']);
				$test_profile = new pts_test_profile($this->repo . '/' . $test_identifier . '-' . $test_version);
				if($test_profile->get_display_format() != 'BAR_GRAPH' || !in_array($test_profile->get_license(), array('Free', 'Non-Free')) || !$test_profile->is_supported(false))
				{
					continue;
				}

				$overview_data = $test_profile->get_generated_data(false);
				if(isset($overview_data['overview']))
				{
					$add_to_suite = false;
					foreach($overview_data['overview'] as $d)
					{
						if(isset($d['tested_archs']) && !empty($d['tested_archs']))
						{
							foreach($arch_strings as $arch_check)
							{
								if(in_array($arch_check, $d['tested_archs']))
								{
									$add_to_suite = true;
									break;
								}
							}
							if($add_to_suite)
							{
								break;
							}
						}
					}
					if($add_to_suite)
					{
						$this->add_to_suite($test_profile);
					}
				}
			}
		}
		else if(self::is_selector_os($this->virtual))
		{
			$this->set_title((strlen($this->virtual) < 4 ? strtoupper($this->virtual) : ucwords($this->virtual)) . ' Operating System Tests');
			$this->set_description('This is a collection of test profiles found within the specified OpenBenchmarking.org repository where the test profile is specified as being compatible with the ' . $this->virtual . ' Operating System.');
			foreach($repo_index['tests'] as $test_identifier => &$test)
			{
				if(!in_array($this->virtual, array_map('strtolower', $test['supported_platforms'])))
				{
					// Doing a virtual suite of all tests specific to an OS, but this test profile is not supported there
					continue;
				}

				$test_version = array_shift($test['versions']);
				$test_profile = new pts_test_profile($this->repo . '/' . $test_identifier . '-' . $test_version);

				if($test_profile->get_display_format() != 'BAR_GRAPH' || !in_array($test_profile->get_license(), array('Free', 'Non-Free')))
				{
					// Also ignore these tests
					continue;
				}

				if($test_profile->is_supported(false))
				{
					// All checks passed, add to virtual suite
					$this->add_to_suite($test_profile);
				}
			}
		}
		else if(self::is_selector_subsystem($this->virtual))
		{
			$this->set_title((strlen($this->virtual) < 4 ? strtoupper($this->virtual) : ucwords($this->virtual)) . ' Subsystem Tests');
			$this->set_description('This is a collection of test profiles found within the specified OpenBenchmarking.org repository where the test profile is specified as being a test of the ' . $this->virtual . ' sub-system.');
			foreach($repo_index['tests'] as $test_identifier => &$test)
			{
				if($this->virtual != strtolower($test['test_type']))
				{
					// Doing a virtual suite of all tests specific to a test_type, but this test profile is not supported there
					continue;
				}

				$test_version = array_shift($test['versions']);
				$test_profile = new pts_test_profile($this->repo . '/' . $test_identifier . '-' . $test_version);

				if($test_profile->get_display_format() != 'BAR_GRAPH' || !in_array($test_profile->get_license(), array('Free', 'Non-Free')))
				{
					// Also ignore these tests
					continue;
				}

				if($test_profile->is_supported(false))
				{
					// All checks passed, add to virtual suite
					$this->add_to_suite($test_profile);
				}
			}
		}
		else if(self::is_selector_software_type($this->virtual))
		{
			$this->set_title(ucwords($this->virtual) . ' Tests');
			$this->set_description('This is a collection of test profiles found within the specified OpenBenchmarking.org repository where the test profile is specified as being a ' . $this->virtual . ' software test.');
			foreach($repo_index['tests'] as $test_identifier => &$test)
			{
				if($this->virtual != strtolower($test['software_type']))
				{
					// Doing a virtual suite of all tests specific to a software_type, but this test profile is not supported there
					continue;
				}

				$test_version = array_shift($test['versions']);
				$test_profile = new pts_test_profile($this->repo . '/' . $test_identifier . '-' . $test_version);

				if($test_profile->get_display_format() != 'BAR_GRAPH' || !in_array($test_profile->get_license(), array('Free', 'Non-Free')))
				{
					// Also ignore these tests
					continue;
				}

				if($test_profile->is_supported(false))
				{
					// All checks passed, add to virtual suite
					$this->add_to_suite($test_profile);
				}
			}
		}
		else if(self::is_selector_internal_tag($this->repo, $this->virtual))
		{
			$this->set_title(ucwords($this->virtual) . ' Tests');
			$this->set_description('This is a collection of test profiles found within the specified OpenBenchmarking.org repository where the test profile is specified via an internal tag as testing ' . $this->virtual . '.');
			foreach($repo_index['tests'] as $test_identifier => &$test)
			{
				if(!in_array($this->virtual, array_map('strtolower', $test['internal_tags'])))
				{
					// Doing a virtual suite of all tests matching an internal tag
					continue;
				}

				$test_version = array_shift($test['versions']);
				$test_profile = new pts_test_profile($this->repo . '/' . $test_identifier . '-' . $test_version);

				if($test_profile->get_display_format() != 'BAR_GRAPH' || !in_array($test_profile->get_license(), array('Free', 'Non-Free')))
				{
					// Also ignore these tests
					continue;
				}

				if($test_profile->is_supported(false))
				{
					// All checks passed, add to virtual suite
					$this->add_to_suite($test_profile);
				}
			}
		}
		else if(isset(self::$external_dependency_suites[$this->virtual]))
		{
			$this->set_title(self::$external_dependency_suites[$this->virtual][1] . ' Tests');
			$this->set_description('This is a collection of test profiles having an external dependency on ' . self::$external_dependency_suites[$this->virtual][1]);
			foreach($repo_index['tests'] as $test_identifier => &$test)
			{
				$test_version = array_shift($test['versions']);
				$test_profile = new pts_test_profile($this->repo . '/' . $test_identifier . '-' . $test_version);

				if(!in_array(self::$external_dependency_suites[$this->virtual][0], $test_profile->get_external_dependencies()))
				{
					continue;
				}

				if($test_profile->get_display_format() != 'BAR_GRAPH' || !in_array($test_profile->get_license(), array('Free', 'Non-Free')))
				{
					// Also ignore these tests
					continue;
				}

				if($test_profile->is_supported(false))
				{
					// All checks passed, add to virtual suite
					$this->add_to_suite($test_profile);
				}
			}
		}

		$this->set_maintainer('Virtual Test Suite');
		$this->set_suite_type('System');
	}
	public static function load_external_dependency_suites()
	{
		$exdep = new pts_exdep_generic_parser();
		self::$external_dependency_suites = $exdep->get_virtual_suite_packages();
	}
	public static function get_external_dependency_suites()
	{
		return self::$external_dependency_suites;
	}
	public static function available_virtual_suites($return_as_object = true)
	{
		$virtual_suites = array();

		$possible_identifiers = array_merge(
			array('all', 'installed', 'everything', 'compiler', 'multicore', 'single-threaded', 'riscv', 'aarch64'),
			array_map('strtolower', self::available_operating_systems()),
			array_map('strtolower', pts_types::subsystem_targets()),
			array_map('strtolower', pts_types::test_profile_software_types()),
			array_map('strtolower', array_keys(self::$external_dependency_suites))
			);
		sort($possible_identifiers);

		foreach(pts_openbenchmarking::linked_repositories() as $repo)
		{
			$repo_identifiers = array_merge($possible_identifiers, self::tags_in_repo($repo));

			foreach($repo_identifiers as $id)
			{
				$virt_suite = $repo . '/' . $id;

				if($return_as_object)
				{
					$virtual_suite = new pts_virtual_test_suite($virt_suite);

					if($virtual_suite->get_test_count() > 0)
					{
						$virtual_suites[$virtual_suite->get_identifier()] = $virtual_suite;
					}
				}
				else
				{
					$virtual_suites[] = $virt_suite;
				}
			}
		}

		return $virtual_suites;
	}
	public static function is_virtual_suite($identifier)
	{
		static $virt_suite_cache = null;
		if($virt_suite_cache == null)
		{
			$virt_suite_cache = self::available_virtual_suites(false);
		}

		return in_array($identifier, $virt_suite_cache);
	}
	private static function is_selector_os($id)
	{
		$yes = false;

		foreach(self::available_operating_systems() as $name => $os)
		{
			if($os === $id)
			{
				// virtual suite of all supported tests by a given operating system
				$yes = $name;
				break;
			}
		}

		return $yes;
	}
	private static function available_operating_systems()
	{
		$os = array();

		foreach(pts_types::operating_systems() as $os_r)
		{
			$os[$os_r[0]] = strtolower($os_r[0]);
		}

		return $os;
	}
	private static function is_selector_subsystem($id)
	{
		$yes = false;

		foreach(pts_types::subsystem_targets() as $subsystem)
		{
			if(strtolower($subsystem) === $id)
			{
				// virtual suite of all supported tests by a given TestType / subsystem
				$yes = $subsystem;
				break;
			}
		}

		return $yes;
	}
	private static function is_selector_software_type($id)
	{
		$yes = false;

		foreach(pts_types::test_profile_software_types() as $subsystem)
		{
			if(strtolower($subsystem) === $id && $subsystem != 'BaseTestProfile')
			{
				// virtual suite of all supported tests by a given SoftwareType
				$yes = $subsystem;
				break;
			}
		}

		return $yes;
	}
	private static function is_selector_internal_tag($repo, $id)
	{
		$yes = false;

		if(($i = array_search(strtolower($id), self::tags_in_repo($repo))) !== false)
		{
			// virtual suite of all test profiles matching an internal tag
			$tags = self::tags_in_repo($repo);
			$yes = $tags[$i];
		}

		return $yes;
	}
	public static function tags_in_repo($repo)
	{
		$tags = array();

		// read the repo
		$repo_index = pts_openbenchmarking::read_repository_index($repo);

		if(isset($repo_index['tests']) && is_array($repo_index['tests']))
		{
			foreach($repo_index['tests'] as &$test)
			{
				if(!isset($test['internal_tags']))
				{
					continue;				
				}

				foreach($test['internal_tags'] as $tag)
				{
					$tags[$tag] = strtolower($tag);
				}
			}
		}

		return $tags;
	}
}

pts_virtual_test_suite::load_external_dependency_suites();

?>
