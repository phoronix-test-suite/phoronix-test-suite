<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2010, Phoronix Media
	Copyright (C) 2009 - 2010, Michael Larabel

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

// formerly known as the pts_result_file_merge_test object
class pts_result_file_result_object
{
	public $result_buffer;
	public $test_result;

	// TODO: In process of transitioning this better to use pts_test_result
	public function __construct($title, $version, $profile_version, $attributes, $scale, $test_identifier, $arguments, $proportion, $format, $result_buffer)
	{
		$test_profile = new pts_test_profile($test_identifier);
		$test_profile->set_test_title($title);
		$test_profile->set_version($version);
		$test_profile->set_test_profile_version($profile_version);
		$test_profile->set_result_scale($scale);
		$test_profile->set_result_proportion($proportion);
		$test_profile->set_result_format($format);

		$this->test_result = new pts_test_result($test_profile);
		$this->test_result->set_used_arguments_description($attributes);
		$this->test_result->set_used_arguments($arguments);
		$this->result_buffer = $result_buffer;
	}
}

?>
