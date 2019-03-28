<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2019, Phoronix Media
	Copyright (C) 2008 - 2019, Michael Larabel

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

class pts_virtual_test_suite
{
	private $identifier;
	private $repo;
	private $virtual;

	private $is_virtual_os_selector = false;
	private $is_virtual_subsystem_selector = false;
	private $is_virtual_software_type = false;
	private $is_virtual_internal_tag = false;
	private $is_virtual_installed = false;
	private $is_virtual_everything = false;

	public function __construct($identifier)
	{
		$this->identifier = $identifier;

		$identifier = explode('/', $identifier);
		$this->repo = $identifier[0];
		$this->virtual = $identifier[1];

		$this->is_virtual_os_selector = self::is_selector_os($identifier[1]);
		$this->is_virtual_subsystem_selector = self::is_selector_subsystem($identifier[1]);
		$this->is_virtual_software_type = self::is_selector_software_type($identifier[1]);
		$this->is_virtual_internal_tag = self::is_selector_internal_tag($this->repo, $this->virtual);
		$this->is_virtual_installed = ($this->virtual == 'installed');
		$this->is_virtual_everything = ($this->virtual == 'everything');
	}
	public function __toString()
	{
		return $this->identifier;
	}
	public static function available_virtual_suites()
	{
		$virtual_suites = array();

		$possible_identifiers = array_merge(
			array('all', 'installed', 'everything'),
			array_map('strtolower', self::available_operating_systems()),
			array_map('strtolower', pts_types::subsystem_targets()),
			array_map('strtolower', pts_types::test_profile_software_types())
			);

		foreach(pts_openbenchmarking::linked_repositories() as $repo)
		{
			$repo_identifiers = array_merge($possible_identifiers, self::tags_in_repo($repo));

			foreach($repo_identifiers as $id)
			{
				$virt_suite = $repo . '/' . $id;

				if(self::is_virtual_suite($virt_suite))
				{
					$virtual_suite = pts_types::identifier_to_object($virt_suite);

					if($virtual_suite instanceof pts_virtual_test_suite)
					{
						$virtual_suites[] = $virtual_suite;
					}
				}
			}
		}

		return $virtual_suites;
	}
	public static function is_virtual_suite($identifier)
	{
		$identifier = explode('/', $identifier);
		$is_virtual_suite = false;

		if(count($identifier) == 2)
		{
			// read the repo
			pts_openbenchmarking::refresh_repository_lists(array($identifier[0]));
			$repo_index = pts_openbenchmarking::read_repository_index($identifier[0]);

			if(isset($repo_index['tests']) && is_array($repo_index['tests']))
			{
				// figure out virtual suites
				if($identifier[1] == 'all')
				{
					// virtual suite of all supported tests
					$is_virtual_suite = true;
				}
				else if($identifier[1] == 'everything')
				{
					// virtual suite of everything -- including UNSUPPORTED TESTS
					$is_virtual_suite = true;
				}
				else if($identifier[1] == 'installed')
				{
					// virtual suite of all installed tests
					$is_virtual_suite = true;
				}
				else if(self::is_selector_os($identifier[1]))
				{
					// virtual suite of all supported tests by a given operating system
					$is_virtual_suite = true;
				}
				else if(self::is_selector_subsystem($identifier[1]))
				{
					// virtual suite of all supported tests by a given TestType / subsystem
					$is_virtual_suite = true;
				}
				else if(self::is_selector_software_type($identifier[1]))
				{
					// virtual suite of all supported tests by a given SoftwareType
					$is_virtual_suite = true;
				}
				else if(self::is_selector_internal_tag($identifier[0], $identifier[1]))
				{
					// virtual suite of all supported tests by a given SoftwareType
					$is_virtual_suite = true;
				}
			}
		}

		return $is_virtual_suite;
	}
	public function get_identifier()
	{
		return $this->identifier;
	}
	public function get_title()
	{
		if($this->is_virtual_os_selector)
		{
			$title = $this->is_virtual_os_selector . ' Operating System Tests';
		}
		else if($this->is_virtual_subsystem_selector)
		{
			$title = $this->is_virtual_subsystem_selector . ' Subsystem Tests';
		}
		else if($this->is_virtual_software_type)
		{
			$title = $this->is_virtual_software_type . ' Tests';
		}
		else if($this->is_virtual_internal_tag)
		{
			$title = ucwords($this->is_virtual_internal_tag) . ' Tests';
		}
		else if($this->is_virtual_installed)
		{
			$title = 'Installed Tests';
		}
		else if(substr($this->identifier, strrpos($this->identifier, '/') + 1) == 'all')
		{
			$title = 'All ' . strtoupper(substr($this->identifier, 0,  strpos($this->identifier, '/'))) . ' Tests';
		}
		else if($this->is_virtual_everything)
		{
			$title = 'Every ' . strtoupper(substr($this->identifier, 0,  strpos($this->identifier, '/'))) . ' Test';
		}
		else
		{
			$title = 'Virtual Suite';
		}

		return $title;
	}
	public function get_description()
	{
		if($this->is_virtual_os_selector)
		{
			$description = 'This is a collection of test profiles found within the specified OpenBenchmarking.org repository where the test profile is specified as being compatible with the ' . $this->is_virtual_os_selector . ' Operating System.';
		}
		else if($this->is_virtual_subsystem_selector)
		{
			$description = 'This is a collection of test profiles found within the specified OpenBenchmarking.org repository where the test profile is specified as being a test of the ' . $this->is_virtual_subsystem_selector . ' sub-system.';
		}
		else if($this->is_virtual_software_type)
		{
			$description = 'This is a collection of test profiles found within the specified OpenBenchmarking.org repository where the test profile is specified as being a ' . $this->is_virtual_software_type . ' software test.';
		}
		else if($this->is_virtual_internal_tag)
		{
			$description = 'This is a collection of test profiles found within the specified OpenBenchmarking.org repository where the test profile is specified via an internal tag as testing ' . $this->is_virtual_internal_tag . '.';
		}
		else if($this->is_virtual_installed)
		{
			$description = 'This is a collection of test profiles found within the specified OpenBenchmarking.org repository that are already installed on the system under test.';
		}
		else if(substr($this->identifier, strrpos($this->identifier, '/') + 1) == 'all')
		{
			$description = 'This is a collection of all test profiles found within the specified OpenBenchmarking.org repository.';
		}
		else if($this->is_virtual_everything)
		{
			$description = 'This is a collection of every test profile found within the specified OpenBenchmarking.org repository, including unsupported tests.';
		}
		else
		{
			$description = 'Virtual Suite';
		}

		return $description;
	}
	public function is_core_version_supported()
	{
		// It's virtual and created by pts-core so it's always supported
		return true;
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
				foreach($test['internal_tags'] as $tag)
				{
					$tags[$tag] = strtolower($tag);
				}
			}
		}

		return $tags;
	}
	public function get_contained_test_profiles()
	{
		$contained = array();

		// read the repo
		$repo_index = pts_openbenchmarking::read_repository_index($this->repo);

		if(isset($repo_index['tests']) && is_array($repo_index['tests']))
		{
			foreach($repo_index['tests'] as $test_identifier => &$test)
			{
				if($this->is_virtual_everything)
				{
					$test_version = array_shift($test['versions']);
					$test_profile = new pts_test_profile($this->repo . '/' . $test_identifier . '-' . $test_version);
					$contained[] = $test_profile;
					continue;
				}

				if((!empty($test['supported_platforms']) && !in_array(phodevi::operating_system(), $test['supported_platforms'])) || empty($test['title']))
				{
					// Initial check to not do unsupported tests
					continue;
				}

				if($this->is_virtual_os_selector && !in_array($this->virtual, array_map('strtolower', $test['supported_platforms'])))
				{
					// Doing a virtual suite of all tests specific to an OS, but this test profile is not supported there
					continue;
				}
				else if($this->is_virtual_subsystem_selector && $this->virtual != strtolower($test['test_type']))
				{
					// Doing a virtual suite of all tests specific to a test_type, but this test profile is not supported there
					continue;
				}
				else if($this->is_virtual_software_type && $this->virtual != strtolower($test['software_type']))
				{
					// Doing a virtual suite of all tests specific to a software_type, but this test profile is not supported there
					continue;
				}
				else if($this->is_virtual_internal_tag && !in_array($this->virtual, array_map('strtolower', $test['internal_tags'])))
				{
					// Doing a virtual suite of all tests matching an internal tag
					continue;
				}

				$test_version = array_shift($test['versions']);
				$test_profile = new pts_test_profile($this->repo . '/' . $test_identifier . '-' . $test_version);

				if($test_profile->get_display_format() != 'BAR_GRAPH' || ($this->is_virtual_installed == false && !in_array($test_profile->get_license(), array('Free', 'Non-Free'))))
				{
					// Also ignore these tests
					continue;
				}

				if($this->is_virtual_installed && $test_profile->is_test_installed() == false)
				{
					// Test is not installed
					continue;
				}

				if($test_profile->is_supported(false))
				{
					// All checks passed, add to virtual suite
					$contained[] = $test_profile;
					continue;
				}
			}
		}

		return $contained;
	}
}

?>
