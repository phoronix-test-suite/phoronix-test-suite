<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel

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
		$this->is_virtual_installed = ($this->virtual == "installed");
	}
	public function __toString()
	{
		return $this->identifier;
	}
	public static function available_virtual_suites()
	{
		$virtual_suites = array();

		$possible_identifiers = array_merge(
			array('all', 'installed'),
			array_map('strtolower', self::available_operating_systems()),
			array_map('strtolower', pts_types::subsystem_targets()),
			array_map('strtolower', pts_types::test_profile_software_types())
			);

		foreach(pts_openbenchmarking_client::linked_repositories() as $repo)
		{
			$repo_identifiers = array_merge($possible_identifiers, self::tags_in_repo($repo));

			foreach($repo_identifiers as $id)
			{
				$virt_suite = $repo . '/' . $id;

				if(self::is_virtual_suite($virt_suite))
				{
					$virtual_suite = new pts_virtual_test_suite($virt_suite);
					$size = count($virtual_suite->get_contained_test_profiles());

					if($size > 0)
					{
						array_push($virtual_suites, array($virt_suite, $size));
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
			$repo_index = pts_openbenchmarking::read_repository_index($identifier[0]);

			if(isset($repo_index['tests']) && is_array($repo_index['tests']))
			{
				// figure out virtual suites
				if($identifier[1] == "all")
				{
					// virtual suite of all supported tests
					$is_virtual_suite = true;
				}
				else if($identifier[1] == "installed")
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
	private static function is_selector_os($id)
	{
		$yes = false;

		foreach(self::available_operating_systems() as $os)
		{
			if($os === $id)
			{
				// virtual suite of all supported tests by a given operating system
				$yes = true;
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
			array_push($os, strtolower($os_r[0]));
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
				$yes = true;
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
			if(strtolower($subsystem) === $id)
			{
				// virtual suite of all supported tests by a given SoftwareType
				$yes = true;
				break;
			}
		}

		return $yes;
	}
	private static function is_selector_internal_tag($repo, $id)
	{
		$yes = false;

		if(in_array(strtolower($id), self::tags_in_repo($repo)))
		{
			// virtual suite of all test profiles matching an internal tag
			$yes = true;
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
					pts_arrays::unique_push($tags, strtolower($tag));
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
				if(!in_array(OPERATING_SYSTEM, $test['supported_platforms']) || empty($test['title']))
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

				if($test_profile->get_display_format() != "BAR_GRAPH" || !in_array($test_profile->get_license(), array("Free", "Non-Free")))
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
					array_push($contained, $test_profile);
					continue;
				}
			}
		}

		return $contained;
	}
	public function get_title()
	{
		return "Virtual Suite";
	}
	public function get_description()
	{
		return "N/A"; // TODO: auto-generate description
	}
	public function is_core_version_supported()
	{
		// It's virtual and created by pts-core so it's always supported
		return true;
	}
}

?>
