<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2014 - 2021, Phoronix Media
	Copyright (C) 2014 - 2021, Michael Larabel

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

class start_phoromatic_server implements pts_option_interface
{
	const doc_section = 'Phoromatic';
	const doc_description = 'Start the Phoromatic web server for controlling local Phoronix Test Suite client systems to facilitate automated and repeated test orchestration and other automated features targeted at the enterprise.';

	public static function run($r)
	{
		if(pts_client::create_lock(PTS_USER_PATH . 'phoromatic_server_lock') == false)
		{
			trigger_error('The Phoromatic Server is already running.', E_USER_ERROR);
			return false;
		}

		if(phodevi::is_windows())
		{
			trigger_error('Running the Phoromatic Server on Windows is experimental and may have issues. Running the Phoromatic client and Phoronix Test Suite itself on Windows is supported but the Phoromatic Server is currently not engineered for Windows but can be under commercial engagement.', E_USER_ERROR);
		}

		pts_file_io::unlink(getenv('PTS_EXT_LAUNCH_SCRIPT_DIR') . '/phoromatic-server-launcher');
		if(PHP_VERSION_ID < 50400)
		{
			echo 'Running an unsupported PHP version. PHP 5.4+ is required to use this feature.' . PHP_EOL . PHP_EOL;
			return false;
		}
		if(!function_exists('socket_create_listen'))
		{
			echo 'PHP Sockets support is needed to use the Phoromatic Server.' . PHP_EOL . PHP_EOL;
			return false;
		}
		// Below code is wise but not vital
		/*if(!function_exists('pcntl_signal'))
		{
			echo 'PHP pcntl_signal support is needed to use the Phoromatic Server.' . PHP_EOL . PHP_EOL;
			return false;
		}*/

		$server_launcher = '#!/bin/sh' . PHP_EOL;
		$web_port = 0;
		$remote_access_override = getenv('PHOROMATIC_HTTP_PORT');
		if($remote_access_override && is_numeric($remote_access_override) && $remote_access_override > 1)
		{
			$remote_access = $remote_access_override;
		}
		else
		{
			$remote_access = pts_config::read_user_config('PhoronixTestSuite/Options/Server/RemoteAccessPort', 'RANDOM');
		}

		if($remote_access == 'RANDOM')
		{
			$remote_access = pts_network::find_available_port();
			echo 'Port ' . $remote_access . ' chosen as random port for this instance. Change the default port via the Phoronix Test Suite user configuration file.' . PHP_EOL;
		}

		$remote_access = is_numeric($remote_access) && $remote_access > 1 ? $remote_access : false;
		$blocked_ports = array(2049, 3659, 4045, 6000, 9000);

		if($remote_access)
		{
			// ALLOWING SERVER TO BE REMOTELY ACCESSIBLE
			$server_ip = '0.0.0.0';
			$errno = null;
			$errstr = null;

			if(($fp = fsockopen('127.0.0.1', $remote_access, $errno, $errstr, 5)) != false)
			{
				fclose($fp);
				trigger_error('Port ' . $remote_access . ' is already in use by another server process. Close that process or change the Phoronix Test Suite server port via' . pts_config::get_config_file_location() . ' to proceed.', E_USER_ERROR);
				return false;
			}
			else
			{
				$web_port = $remote_access;

				if(($ws_port = getenv('PTS_WEBSOCKET_PORT')) != false && is_numeric($ws_port) && $ws_port > 1)
				{
					$web_socket_port = $ws_port;
				}
				else
				{
					$web_socket_port = pts_config::read_user_config('PhoronixTestSuite/Options/Server/WebSocketPort', '');
				}

				if($web_socket_port == null || !is_numeric($web_socket_port))
				{
					$web_socket_port = pts_network::find_available_port();
				}
			}
		}
		else
		{
			echo PHP_EOL . PHP_EOL . 'You must first configure the remote web / Phoromatic settings via:' . PHP_EOL . '    ' . pts_config::get_config_file_location() . PHP_EOL . PHP_EOL . 'The RemoteAccessPort should be a network port to use for HTTP communication while WebSocketPort should be set to another available network port. Set to RANDOM if wishing to use randomly chosen available ports.' . PHP_EOL . PHP_EOL;
			return false;
		}

		if(!extension_loaded('sqlite3'))
		{
			echo PHP_EOL . PHP_EOL . pts_client::cli_just_bold('PHP SQLite3') . ' support must first be enabled before accessing the Phoromatic server (e.g. installing the php-sqlite or php-pdo package depending on the distribution).' . PHP_EOL . PHP_EOL;
			return false;
		}

		// Setup server logger
		define('PHOROMATIC_SERVER', true);
		// Just create the logger so now it will flush it out
		$pts_logger = new pts_logger();
		$pts_logger->clear_log();
		echo pts_core::program_title() . ' starting Phoromatic Server' . PHP_EOL;
		$pts_logger->log(pts_core::program_title() . ' starting Phoromatic Server on ' . phodevi::read_property('network', 'ip'));

		echo 'Phoronix Test Suite User-Data Directory Path: ' . PTS_USER_PATH . PHP_EOL;
		echo 'Phoronix Test Suite Configuration File: ' . pts_config::get_config_file_location() . PHP_EOL;
		echo 'Phoromatic Server Log File: ' . $pts_logger->get_log_file_location() . PHP_EOL;
		$pts_logger->log('PTS_USER_PATH = ' . PTS_USER_PATH);
		$pts_logger->log('PTS_DOWNLOAD_CACHE_PATH = ' . PTS_DOWNLOAD_CACHE_PATH);
		$pts_logger->log('XML Configuration File = ' . pts_config::get_config_file_location());

		// WebSocket Server Setup
		$server_launcher .= 'export PTS_WEB_PORT=' . $web_port . PHP_EOL;
		$server_launcher .= 'export PTS_WEBSOCKET_PORT=' . $web_socket_port . PHP_EOL;
		$server_launcher .= 'export PTS_WEBSOCKET_SERVER=PHOROMATIC' . PHP_EOL;
		$server_launcher .= 'export PTS_NO_FLUSH_LOGGER=1' . PHP_EOL;
		$server_launcher .= 'export PTS_PHOROMATIC_SERVER=1' . PHP_EOL;
		$server_launcher .= 'export PTS_PHOROMATIC_LOG_LOCATION=' . $pts_logger->get_log_file_location() . PHP_EOL;
		$server_launcher .= 'cd ' . getenv('PTS_DIR') . ' && PTS_MODE="CLIENT" ' . getenv('PHP_BIN') . ' pts-core/phoronix-test-suite.php start-ws-server &' . PHP_EOL;
		$server_launcher .= 'websocket_server_pid=$!'. PHP_EOL;
		$pts_logger->log('Starting WebSocket process on port ' . $web_socket_port);
		$server_launcher .= 'cd ' . getenv('PTS_DIR') . ' && PTS_MODE="CLIENT" ' . getenv('PHP_BIN') . ' pts-core/phoronix-test-suite.php start-phoromatic-event-server &' . PHP_EOL;
		$server_launcher .= 'event_server_pid=$!'. PHP_EOL;

		// HTTP Server Setup
		if(getenv('PHOROMATIC_WANTS_APACHE'))
		{
			echo PHP_EOL . PHP_EOL . 'To manually configure Apache, setup the following:' . PHP_EOL;
			echo 'The root web directory: ' . PTS_CORE_PATH . 'phoromatic/public_html/' . PHP_EOL;
			echo 'Set the HTTP port to: ' . $web_port . PHP_EOL;
			echo 'Of course, ensure Apache PHP support is available.' . PHP_EOL . PHP_EOL;
		}
		else if(false && pts_client::executable_in_path('nginx') && is_file('/run/php-fpm/php-fpm.pid'))
		{
			// NGINX
			$nginx_conf = 'error_log /tmp/error.log;
			pid /tmp/nginx.pid;
			worker_processes 1;

			events {
			  worker_connections 1024;
			}

			http {
			  client_body_temp_path /tmp/client_body;
			  fastcgi_temp_path /tmp/fastcgi_temp;
			  proxy_temp_path /tmp/proxy_temp;
			  scgi_temp_path /tmp/scgi_temp;
			  uwsgi_temp_path /tmp/uwsgi_temp;
			  tcp_nopush on;
			  tcp_nodelay on;
			  keepalive_timeout 180;
			  types_hash_max_size 2048;
			  include /etc/nginx/mime.types;
			  index index.php;

			  server {
			    listen ' . $web_port . ';
			    listen [::]:' . $web_port . ' default ipv6only=on;
			    access_log /tmp/access.log;
			    error_log /tmp/error.log;
			    root ' . PTS_CORE_PATH . 'phoromatic/public_html;
				index index.php;
			      try_files $uri $uri/ /index.php;
				location / {
				autoindex on;
				}
				location ~ \.php$ {
				     include        /etc/nginx/fastcgi_params;
				     fastcgi_param  SCRIPT_FILENAME  $document_root/$fastcgi_script_name;
				     fastcgi_split_path_info ^(.+\.php)(/.+)$;
				     fastcgi_pass   127.0.0.1:9000;
				     fastcgi_index  index.php;
				}
			  }
			}';
			$nginx_conf_file = tempnam(PTS_USER_PATH, 'nginx_conf_');
			file_put_contents($nginx_conf_file, $nginx_conf);

			$server_launcher .= 'nginx -c ' . $nginx_conf_file . PHP_EOL . 'rm -f ' . $nginx_conf_file . PHP_EOL;
		}
		else if(($mongoose = pts_client::executable_in_path('mongoose')) && ($php_cgi = pts_client::executable_in_path('php-cgi')))
		{
			// Mongoose Embedded Web Server
			echo PHP_EOL . 'Launching with Mongoose web server.' . PHP_EOL;
			$server_launcher .= $mongoose . ' -p ' . $web_port . ' -r ' . PTS_CORE_PATH . 'phoromatic/public_html/ -I ' . $php_cgi . ' -i index.php > /dev/null 2>> $PTS_PHOROMATIC_LOG_LOCATION &' . PHP_EOL; //2> /dev/null

		}
		else if(strpos(getenv('PHP_BIN'), 'hhvm'))
		{
			echo PHP_EOL . 'Unfortunately, the HHVM built-in web server has abandoned upstream. Users will need to use the PHP binary or other alternatives.' . PHP_EOL . PHP_EOL;
			return;
		}
		else
		{
			// PHP Web Server
			echo PHP_EOL . 'Launching with PHP built-in web server.' . PHP_EOL;
			$server_launcher .= 'export PHP_CLI_SERVER_WORKERS=8' . PHP_EOL;
			$server_launcher .= getenv('PHP_BIN') . ' -S ' . $server_ip . ':' . $web_port . ' -t ' . PTS_CORE_PATH . 'phoromatic/public_html/ > /dev/null 2>> $PTS_PHOROMATIC_LOG_LOCATION &' . PHP_EOL; //2> /dev/null
		}
		$server_launcher .= 'http_server_pid=$!'. PHP_EOL;
		$server_launcher .= 'sleep 1' . PHP_EOL;
		$server_launcher .= 'echo "The Phoromatic Web Interface Is Accessible At: http://localhost:' . $web_port . '"' . PHP_EOL;
		$pts_logger->log('Starting HTTP process @ http://localhost:' . $web_port);

		// Avahi for zeroconf network discovery support
		$did_avahi = false;
		if(pts_config::read_bool_config('PhoronixTestSuite/Options/Server/AdvertiseServiceZeroConf', 'TRUE'))
		{
			if(is_dir('/etc/avahi/services') && is_writable('/etc/avahi/services'))
			{
				file_put_contents('/etc/avahi/services/phoromatic-server.service', '<?xml version="1.0" standalone=\'no\'?>
<!DOCTYPE service-group SYSTEM "avahi-service.dtd">
<service-group>
  <name replace-wildcards="yes">phoromatic-server-%h</name>
  <service>
    <type>_http._tcp</type>
    <port>' . $web_port . '</port>
  </service>
</service-group>');
			}
			else if(pts_client::executable_in_path('avahi-publish'))
			{
				$hostname = phodevi::read_property('system', 'hostname');
				$hostname = $hostname == null ? rand(0, 99) : $hostname;
				$server_launcher .= 'avahi-publish -s phoromatic-server-' . $hostname . ' _http._tcp ' . $web_port . ' "Phoronix Test Suite Phoromatic" > /dev/null 2> /dev/null &' . PHP_EOL;
				$server_launcher .= 'avahi_publish_pid=$!'. PHP_EOL;
				$did_avahi = true;
			}
		}

		// Zeroconf via OpenBenchmarking.org
		if(pts_config::read_bool_config('PhoronixTestSuite/Options/Server/AdvertiseServiceOpenBenchmarkRelay', 'TRUE') && pts_network::internet_support_available())
		{
			pts_openbenchmarking::make_openbenchmarking_request('phoromatic_server_relay', array('local_ip' => phodevi::read_property('network', 'ip'), 'local_port' => $web_port));
		}

		// Wait for input to shutdown process..
		if(!PTS_IS_DAEMONIZED_SERVER_PROCESS)
		{
			$server_launcher .= PHP_EOL . 'echo -n "Press [ENTER] to kill server..."' . PHP_EOL;
			$server_launcher .= PHP_EOL . 'read var_name';
		}
		else
		{
			$server_launcher .= PHP_EOL . 'while [ ! -f "/var/lib/phoronix-test-suite/end-phoromatic-server" ];';
			$server_launcher .= PHP_EOL . 'do';
			$server_launcher .= PHP_EOL . 'sleep 1';
			$server_launcher .= PHP_EOL . 'done';
			$server_launcher .= PHP_EOL . 'rm -f /var/lib/phoronix-test-suite/end-phoromatic-server' . PHP_EOL;
		}

		// Shutdown / Kill Servers
		$server_launcher .= PHP_EOL . 'kill $http_server_pid';
		$server_launcher .= PHP_EOL . 'kill $websocket_server_pid';
		$server_launcher .= PHP_EOL . 'kill $event_server_pid';
		if(is_writable('/etc/avahi/services') && is_file('/etc/avahi/services/phoromatic-server.service'))
		{
			$server_launcher .= PHP_EOL . 'rm -f /etc/avahi/services/phoromatic-server.service';
		}
		else if($did_avahi)
		{
			$server_launcher .= PHP_EOL . 'kill $avahi_publish_pid';
		}
		$server_launcher .= PHP_EOL . 'rm -f ~/.phoronix-test-suite/run-lock*';
		file_put_contents(getenv('PTS_EXT_LAUNCH_SCRIPT_DIR') . '/phoromatic-server-launcher', $server_launcher);
	}
}

?>
