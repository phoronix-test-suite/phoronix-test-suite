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

	public function __construct($identifier)
	{
		$this->identifier = $identifier;

		$identifier = explode('/', $identifier);
		$this->repo = $identifier[0];
		$this->virtual = $identifier[1];
	}
	public function __toString()
	{
		return $this->identifier;
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
				// figure out virtual suites //TODO: add free, local, installed, internal_tags
				if($identifier[1] == "all")
				{
					$is_virtual_suite = true;
				}
			}
		}

		return $is_virtual_suite;
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

				$test_version = array_shift($test['versions']);
				$test_profile = new pts_test_profile($this->repo . '/' . $test_identifier . '-' . $test_version);

				if($test_profile->get_display_format() != "BAR_GRAPH" || !in_array($test_profile->get_license(), array("Free", "Non-Free")))
				{
					// Also ignore these tests
					continue;
				}

				if($this->virtual == "all")
				{
					if($test_profile->is_supported(false))
					{
						array_push($contained, $test_profile);
					}
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
		return "N/A";
	}
	public function is_core_version_supported()
	{
		// It's virtual and created by pts-core so it's always supported
		return true;
	}
}

?>
