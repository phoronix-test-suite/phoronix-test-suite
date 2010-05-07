<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel
	pts-includes-unit_caching.php: Functions that simply call pts-core functions for building their caches

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

function pts_cache_suite_calls()
{
	pts_supported_suites_array();
	// pts_available_suites_array(); // This is already called within pts_supported_suites_array()
	pts_suite_name_to_identifier(-1);
}
function pts_cache_test_calls()
{
	pts_tests::supported_tests();
	pts_test_name_to_identifier(-1);
}
function pts_cache_hardware_calls()
{
	pts_hw_string();
	pts_supported_sensors();
}
function pts_cache_software_calls()
{
	pts_sw_string();
}

?>
