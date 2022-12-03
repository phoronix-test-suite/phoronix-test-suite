<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2012 - 2019, Phoronix Media
	Copyright (C) 2012 - 2019, Michael Larabel

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

class gui implements pts_option_interface
{
	const doc_section = 'Web / GUI Support';
	const doc_description = 'Launch the Phoronix Test Suite HTML5 web user-interface in the local GUI mode (no remote web support) and attempt to auto-launch the web-browser. THIS FEATURE IS CURRENTLY EXPERIMENTAL AND NO LONGER IN ACTIVE DEVELOPMENT. See Phoronix Test Suite Phoromatic as an alternative web UI approach.';
		const doc_skip = true;

	public static function command_aliases()
	{
		return array('webui');
	}

	public static function run($r)
	{
		// Lets get stuff ready.
		if(PHP_VERSION_ID < 50400)
		{
			echo 'Running an unsupported PHP version. PHP 5.4+ is required to use this feature.' . PHP_EOL . PHP_EOL;
			return false;
		}

		echo pts_client::cli_just_bold(PHP_EOL . 'THE PHORONIX TEST SUITE WEB GUI IS CURRENTLY DEPRECATED AND UNMAINTAINED. NO FUTURE IMPROVEMENTS TO THIS GUI ARE PLANNED AT THIS TIME UNLESS THERE IS ENTERPRISE SUPPORT INTEREST.' . PHP_EOL . PHP_EOL . 'THOSE WANTING TO MAKE USE OF A PHORONIX TEST SUITE USER-INTERFACE ARE ENCOURAGED TO USE THE PHOROMATIC [https://www.phoromatic.com/] COMPONENT OF THE PHORONIX TEST SUITE. THERE IS ALSO `phoronix-test-suite interactive` and `phoronix-test-suite-shell` FOR A SELF-GUIDED PHORONIX TEST SUITE EXPERIENCE.' . PHP_EOL . PHP_EOL);
		echo 'Continuing in 10 seconds...' . PHP_EOL;
		sleep(10);

		$web_port = 0;
		$blocked_ports = array(2049, 3659, 4045, 6000);

		// SERVER JUST RUNNING FOR LOCAL SYSTEM, SO ALSO COME UP WITH RANDOM FREE PORT
		$server_ip = 'localhost';
		// Randomly choose a port and ensure it's not being used...
		$web_port = pts_network::find_available_port();
		$web_socket_port = pts_network::find_available_port();

		// Check if we are running on Windows or a *nix.
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
		{
			$script_path = PTS_USER_PATH . 'web-server-launcher.bat';
			pts_file_io::unlink($script_path);
		
			// Lets turn off echo so we don't need to see every command.
			$server_launcher = '@echo off' . PHP_EOL;
			$server_launcher .= 'if "%~1"==":run_ws_server" goto :run_ws_server' . PHP_EOL;
			$server_launcher .= 'if "%~1"==":run_local_server" goto :run_local_server' . PHP_EOL;
			$server_launcher .= 'set CURRENT_DIR="%cd%"' . PHP_EOL . PHP_EOL;
			$server_launcher .= 'start cmd /c "%~f0" :run_ws_server' . PHP_EOL;
			$server_launcher .= 'start cmd /c "%~f0" :run_local_server' . PHP_EOL . PHP_EOL;

			// Setup configurations
			$server_launcher .= 'set PTS_WEBSOCKET_PORT=' . $web_socket_port . PHP_EOL;
			$server_launcher .= 'set PTS_MODE="CLIENT"' . PHP_EOL . PHP_EOL;

			// Windows has no sleep so we ping an invalid ip for a second!
			$server_launcher .= 'ping 192.0.2.2 -n 1 -w 1000 > nul' . PHP_EOL;

			// Browser Launching
			if(($browser = pts_client::executable_in_path('chromium-browser')) || ($browser = pts_client::executable_in_path('google-chrome')))
			{
				$server_launcher .= 'echo "Launching Browser"' . PHP_EOL;
				$server_launcher .= $browser . ' --temp-profile --app=http://localhost:' . $web_port . ' -start-maximized';
				// chromium-browser --kiosk URL starts full-screen
			}
			else if(($browser = pts_client::executable_in_path('firefox')) || ($browser = pts_client::executable_in_path('xdg-open')))
			{
				$server_launcher .= 'echo "Launching Browser"' . PHP_EOL;
				$server_launcher .= $browser . ' http://localhost:' . $web_port;

				// XXX: Need this here since Firefox and some other browsers will return right away rather than wait on process....
				$server_launcher .= PHP_EOL . 'echo -n "Press [ENTER] to kill server..."' . PHP_EOL . 'read var_name';
			}
			else
			{
				$server_launcher .= 'echo "Launch: http://localhost:' . $web_port . '"' . PHP_EOL;
				$server_launcher .= PHP_EOL . 'echo "Press any key to kill server..."' . PHP_EOL . 'pause';
			}
			// Shutdown / Kill Servers (Might want to find a cleaner way)
			$server_launcher .= PHP_EOL . 'taskkill /f /im php.exe';
			// For now lets clean this up.
			$server_launcher .= PHP_EOL . 'del /f ' . getenv('PTS_EXT_LAUNCH_SCRIPT_DIR') . 'run*' . PHP_EOL;
			$server_launcher .= 'exit /B' . PHP_EOL . PHP_EOL;

			// HTTP Server Setup
			$server_launcher .= ':run_local_server' . PHP_EOL;
			if(strpos(getenv('PHP_BIN'), 'hhvm'))
			{
				echo PHP_EOL . 'Unfortunately, the HHVM built-in web server has abandoned upstream. Users will need to use the PHP binary or other alternatives.' . PHP_EOL . PHP_EOL;
				return false;
			}
			else
			{
				$server_launcher .= getenv('PHP_BIN') . ' -S ' . $server_ip . ':' . $web_port . ' -t ' . PTS_CORE_PATH . 'web-interface 2> null  &' . PHP_EOL;
			}
			$server_launcher .= 'exit' . PHP_EOL . PHP_EOL;

			// WebSocket Server Setup
			$server_launcher .= ':run_ws_server' . PHP_EOL;
			$server_launcher .= 'cd ' . getenv('PTS_DIR') . PHP_EOL;
			$server_launcher .= getenv('PHP_BIN') . ' pts-core\\phoronix-test-suite.php start-ws-server &' . PHP_EOL;
			$server_launcher .= 'exit';

			// I dont believe this needs to be done for windows?
			//$server_launcher .= PHP_EOL . 'del /f ~\\.phoronix-test-suite\\run-lock*';
			file_put_contents($script_path, $server_launcher);
		} 
		else
		{
			$script_path = getenv('PTS_EXT_LAUNCH_SCRIPT_DIR') . '/web-server-launcher';
			pts_file_io::unlink($script_path);

			$server_launcher = '#!/bin/sh' . PHP_EOL;

			// WebSocket Server Setup
			$server_launcher .= 'export PTS_WEBSOCKET_PORT=' . $web_socket_port . PHP_EOL;
			$server_launcher .= 'export PTS_WEBSOCKET_SERVER=GUI' . PHP_EOL;
			$server_launcher .= 'cd ' . getenv('PTS_DIR') . ' && PTS_MODE="CLIENT" ' . getenv('PHP_BIN') . ' pts-core/phoronix-test-suite.php start-ws-server &' . PHP_EOL;
			$server_launcher .= 'websocket_server_pid=$!'. PHP_EOL;

			// HTTP Server Setup
			if(strpos(getenv('PHP_BIN'), 'hhvm'))
			{
				echo PHP_EOL . 'Unfortunately, the HHVM built-in web server has abandoned upstream. Users will need to use the PHP binary or other alternatives.' . PHP_EOL . PHP_EOL;
				return false;
			}
			else
			{
				$server_launcher .= getenv('PHP_BIN') . ' -S ' . $server_ip . ':' . $web_port . ' -t ' . PTS_CORE_PATH . 'web-interface/ 2> /dev/null  &' . PHP_EOL; //2> /dev/null
			}
			$server_launcher .= 'http_server_pid=$!'. PHP_EOL;
			$server_launcher .= 'sleep 1' . PHP_EOL;

			// Browser Launching
			if(($browser = pts_client::executable_in_path('chromium-browser')) || ($browser = pts_client::executable_in_path('google-chrome')))
			{
				$server_launcher .= 'echo "Launching Browser"' . PHP_EOL;
				$server_launcher .= $browser . ' --temp-profile --app=http://localhost:' . $web_port . ' -start-maximized';
				// chromium-browser --kiosk URL starts full-screen
			}
			else if(($browser = pts_client::executable_in_path('firefox')) || ($browser = pts_client::executable_in_path('xdg-open')))
			{
				$server_launcher .= 'echo "Launching Browser"' . PHP_EOL;
				$server_launcher .= $browser . ' http://localhost:' . $web_port;

				// XXX: Need this here since Firefox and some other browsers will return right away rather than wait on process....
				$server_launcher .= PHP_EOL . 'echo -n "Press [ENTER] to kill server..."' . PHP_EOL . 'read var_name';
			}
			else
			{
				$server_launcher .= 'echo "Launch: http://localhost:' . $web_port . '"' . PHP_EOL;
				$server_launcher .= PHP_EOL . 'echo -n "Press [ENTER] to kill server..."' . PHP_EOL . 'read var_name';
			}
			// Shutdown / Kill Servers
			$server_launcher .= PHP_EOL . 'kill $http_server_pid';
			$server_launcher .= PHP_EOL . 'kill $websocket_server_pid';
			$server_launcher .= PHP_EOL . 'rm -f ~/.phoronix-test-suite/run-lock*';
			file_put_contents($script_path, $server_launcher);
		}

		echo 'To start server run new script: ' . $script_path;
	}
}

?>
