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

class openbenchmarking_setup implements pts_option_interface
{
	const doc_section = 'OpenBenchmarking.org';
	const doc_description = "This option is used for controlling your Phoronix Test Suite client options for OpenBechmarking.org and syncing the client to your account.";

	public static function run($r)
	{
		echo "\nIf you have not already registered for your free Phoronix Global account, you can do so at http://global.phoronix-test-suite.com/\n\nOnce you have registered your account and clicked the link within the verification email, enter your log-in information below.\n\n";
		echo "User-Name: ";
		$username = pts_user_io::read_user_input();
		echo "Password: ";
		$password = md5(pts_user_io::read_user_input());
		$global_success = pts_global::create_account($username, $password);

		if($global_success)
		{
			echo "\nPhoronix Global Account Setup.\nAccount information written to ~/.phoronix-test-suite/user-config.xml.\n\n";
		}
		else
		{
			echo "\nPhoronix Global Account Not Found.\n";
		}
	}
}

?>
