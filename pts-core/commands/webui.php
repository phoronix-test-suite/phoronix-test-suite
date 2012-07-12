<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2012, Phoronix Media
	Copyright (C) 2012, Michael Larabel

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

class webui implements pts_option_interface
{
	const doc_skip = true; // TODO XXX: cleanup this code before formally advertising this...
	const doc_section = 'Web User Interface';
	const doc_description = 'Launch the Phoronix Test Suite web user-interface.';

	public static function run($r)
	{
		return false; // This won't be formally ready for PTS 4.0 Suldal
		if(PHP_VERSION_ID < 50400)
		{
			echo 'Running an unsupported PHP version. PHP 5.4+ is required to use this feature.' . PHP_EOL . PHP_EOL;
			return false;
		}
		if(!function_exists('pcntl_fork'))
		{
			echo 'PCNTL support is required to use this feature' . PHP_EOL . PHP_EOL;
			return false;
		}

		$chrome = pts_client::executable_in_path('chromium-browser');

		if($chrome)
		{
			$pid = pcntl_fork();
			if($pid == -1)
			{
				echo 'ERROR' . PHP_EOL;
			}
			else if($pid)
			{
				shell_exec($chrome . ' --temp-profile --app=http://localhost:2300');
				//pcntl_wait($status);
			}
			else
			{
				echo shell_exec(getenv('PHP_BIN') . ' -S localhost:2300 test.php');
			}
		}
	}
}

?>
