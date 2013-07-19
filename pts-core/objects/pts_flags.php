<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2011 - 2013, Phoronix Media
	Copyright (C) 2011 - 2013, Michael Larabel

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

pts_flags::init();

class pts_flags
{
	private static $os_identifier_sha1 = null;
	private static $flags;

	// Flags
	private static $is_live_cd;
	private static $no_network_communication;
	private static $no_openbenchmarking_reporting;
	private static $user_agreement_skip;
	private static $skip_md5_checks;
	private static $remove_test_on_completion;
	private static $no_phodevi_cache;
	private static $no_external_dependencies;
	private static $upload_to_openbenchmarking;

	public static function init()
	{
		self::$flags = 0;
		self::$os_identifier_sha1 = sha1(phodevi::read_property('system', 'vendor-identifier'));

		self::$is_live_cd = (1 << 1);
		self::$no_network_communication = (1 << 2);
		self::$no_openbenchmarking_reporting = (1 << 3);
		self::$user_agreement_skip = (1 << 4);
		self::$skip_md5_checks = (1 << 5);
		self::$remove_test_on_completion = (1 << 6);
		self::$no_phodevi_cache = (1 << 7);
		self::$no_external_dependencies = (1 << 8);
		self::$upload_to_openbenchmarking = (1 << 9);

		switch(self::$os_identifier_sha1)
		{
			case 'b28d6a7148b34595c5b397dfcf5b12ac7932b3dc': // Moscow 2011-04 client
				self::$flags = self::$is_live_cd | self::$no_network_communication | self::$no_openbenchmarking_reporting | self::$user_agreement_skip | self::$skip_md5_checks | self::$remove_test_on_completion;
				break;
		}

		if(pts_client::read_env('NO_FILE_HASH_CHECKS') != false || pts_client::read_env('NO_MD5_CHECKS') != false)
		{
			self::$flags |= self::$skip_md5_checks;
		}
		if(pts_config::read_bool_config('PhoronixTestSuite/Options/Testing/RemoveTestInstallOnCompletion', 'FALSE'))
		{
			self::$flags |= self::$remove_test_on_completion;
		}
		if(pts_config::read_bool_config('PhoronixTestSuite/Options/Testing/AlwaysUploadResultsToOpenBenchmarking', 'FALSE'))
		{
			self::$flags |= self::$upload_to_openbenchmarking;
		}
		if(pts_client::read_env('NO_PHODEVI_CACHE') != false)
		{
			self::$flags |= self::$no_phodevi_cache;
		}
		if(pts_client::read_env('NO_EXTERNAL_DEPENDENCIES') != false || pts_client::read_env('SKIP_EXTERNAL_DEPENDENCIES') == 1)
		{
			// NO_EXTERNAL_DEPENDENCIES was deprecated in PTS 3.6 and replaced by more versatile SKIP_EXTERNAL_DEPENDENCIES
			self::$flags |= self::$no_external_dependencies;
		}
	}
	public static function os_identifier_hash()
	{
		return self::$os_identifier_sha1;
	}
	public static function is_live_cd()
	{
		return self::$flags & self::$is_live_cd;
	}
	public static function no_network_communication()
	{
		return self::$flags & self::$no_network_communication;
	}
	public static function no_openbenchmarking_reporting()
	{
		return self::$flags & self::$no_openbenchmarking_reporting;
	}
	public static function user_agreement_skip()
	{
		return self::$flags & self::$user_agreement_skip;
	}
	public static function skip_md5_checks()
	{
		return self::$flags & self::$skip_md5_checks;
	}
	public static function remove_test_on_completion()
	{
		return self::$flags & self::$remove_test_on_completion;
	}
	public static function no_phodevi_cache()
	{
		return self::$flags & self::$no_phodevi_cache;
	}
	public static function no_external_dependencies()
	{
		return self::$flags & self::$no_external_dependencies;
	}
	public static function upload_to_openbenchmarking()
	{
		return self::$flags & self::$upload_to_openbenchmarking;
	}
}

?>
