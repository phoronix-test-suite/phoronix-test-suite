<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2014, Phoronix Media
	Copyright (C) 2008 - 2014, Michael Larabel

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


class phoromatic_system_ssh implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Add Phoromatic Server Information Via SSH';
	}
	public static function page_header()
	{
		return null;
	}
	public static function preload($PAGE)
	{
		return true;
	}
	public static function render_page_process($PATH)
	{
		echo phoromatic_webui_header_logged_in();
		$main = null;

		if(!PHOROMATIC_USER_IS_VIEWER)
		{

if(function_exists('ssh2_connect') && isset($_POST['ip']) && isset($_POST['port']) && isset($_POST['password']) && isset($_POST['username']))
{
	$connection = ssh2_connect($_POST['ip'], $_POST['port']);

	if(ssh2_auth_password($connection, $_POST['username'], $_POST['password']))
	{
		$tmp_local_file = tempnam('/tmp', 'pts-ssh');
		$tmp_remote_file = 'pts-ssh-' . rand(9999, 99999);

		file_put_contents($tmp_local_file, '#!/bin/sh
if [ -w /var/lib/phoronix-test-suite/ ]
then
	PHORO_FILE_PATH=/var/lib/phoronix-test-suite/
elif [ -w $HOME/.phoronix-test-suite/ ]
then
	PHORO_FILE_PATH=$HOME/.phoronix-test-suite/
fi

echo "' . phoromatic_web_socket_server_ip() . '" >> $PHORO_FILE_PATH/phoromatic-servers
mkdir -p $PHORO_FILE_PATH/modules-data/phoromatic
echo "' . phoromatic_web_socket_server_addr() . '" > $PHORO_FILE_PATH/modules-data/phoromatic/last-phoromatic-server
');

		ssh2_scp_send($connection, $tmp_local_file, $tmp_remote_file);
		unlink($tmp_local_file);
		ssh2_exec($connection, 'chmod +x ' . $tmp_remote_file);
		ssh2_exec($connection, './' . $tmp_remote_file);
		ssh2_exec($connection, 'rm' . $tmp_remote_file);
	}
}

			$main .= '<h2>Add Phoromatic Server Info Via SSH</h2>
			<p>If your Phoromatic client systems are SSH-enabled, you can specify their SSH connection information below. In doing so, the Phoromatic Server will do a one-time connection to it immediately to pre-seed the system with the Phoromatic Server account information for this account. This should allow the client systems to then find the server automatically next time the phoronix-test-suite is run. This command assumes the Phoronix Test Suite is already pre-installed on the client system in your desired configuration.</p>';
			$main .= '<hr />';

			if(function_exists('ssh2_connect'))
			{
				$main .= '<h3>Phoromatic Client SSH Information:</h3>';
				$main .= '<form action="' . $_SERVER['REQUEST_URI'] . '" name="ssh_connect" method="post">
				<p><strong>IP Address:</strong> <input type="text" name="ip" /></p>
				<p><strong>SSH Port:</strong> <input type="text" name="port" value="22" /></p>
				<p><strong>Username:</strong> <input type="text" name="username" /></p>
				<p><strong>Password:</strong> <input type="password" name="password" /></p>
				<p><input name="submit" value="Seed Phoromatic Server Account Information" type="submit" /></p>
				</form>';
			}
			else
			{
				$main .= '<h3>PHP SSH2 Must Be Installed For This Feature</h3>';
			}
		}

		$right = null;
		echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in($right));
		echo phoromatic_webui_footer();
	}
}

?>
