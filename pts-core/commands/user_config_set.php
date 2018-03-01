<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2018, Phoronix Media
	Copyright (C) 2009 - 2018, Michael Larabel

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

class user_config_set implements pts_option_interface
{
	const doc_section = 'User Configuration';
	const doc_description = 'This option can be used for setting an XML value in the Phoronix Test Suite user configuration file.';

	public static function run($r)
	{
		if(count($r) == 0)
		{
			echo PHP_EOL . 'You must specify the tag to override along with the value.' . PHP_EOL;
			echo 'Example: phoronix-test-suite user-config-set CacheDirectory=~/cache/' . PHP_EOL . PHP_EOL;
			return false;
		}

		$new_options = array();
		foreach($r as $user_option)
		{
			$user_option_r = explode('=', $user_option);

			if(count($user_option_r) > 1)
			{
				$user_value = substr($user_option, strlen($user_option_r[0]) + 1);

				if(substr($user_value, 0, 1) == '"')
				{
					$user_value = substr($user_value, 1);
				}

				if(substr($user_value, -1) == '"')
				{
					$user_value = substr($user_value, 0, -1);
				}

				if(!in_array(basename($user_option_r[0]), array('AgreementCheckSum', 'GSID'))) // List any XML tags to ignore in this array
				{
					$new_options[$user_option_r[0]] = $user_value;
				}
			}
		}

		pts_config::user_config_generate($new_options);
		echo PHP_EOL . 'New user configuration file written: ' . pts_config::get_config_file_location() . PHP_EOL . PHP_EOL;
	}
}

?>
