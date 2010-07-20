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

class pts_network
{
	private static $display_mode_holder = null;

	public static function http_get_contents($url, $override_proxy = false, $override_proxy_port = false)
	{
		if(defined("NO_NETWORK_COMMUNICATION"))
		{
			return false;
		}

		$stream_context = pts_network::stream_context_create(null, $override_proxy, $override_proxy_port);
		$contents = @pts_file_io::file_get_contents($url, 0, $stream_context);

		return $contents;
	}
	public static function http_upload_via_post($url, $to_post_data)
	{
		if(defined("NO_NETWORK_COMMUNICATION"))
		{
			return false;
		}

		$upload_data = http_build_query($to_post_data);
		$http_parameters = array("http" => array("method" => "POST", "content" => $upload_data));
		$stream_context = pts_network::stream_context_create($http_parameters);
		$opened_url = @fopen($url, "rb", false, $stream_context);
		$response = @stream_get_contents($opened_url);

		return $response;
	}
	public static function download_file($download, $to, &$display_mode = null)
	{
		self::$display_mode_holder = $display_mode;

		if(function_exists("curl_init"))
		{
			$return_state = pts_network::curl_download($download, $to);
		}
		else
		{
			$return_state = pts_network::stream_download($download, $to);
		}

		//echo "\nPHP CURL must either be installed or you must adjust your PHP settings file to support opening FTP/HTTP streams.\n";
		//return false;

		if($return_state == true)
		{
			if(self::$display_mode_holder)
			{
				self::$display_mode_holder->test_install_download_completed();
			}
		}
	}
	private static function curl_download($download, $download_to)
	{
		if(!function_exists("curl_init"))
		{
			return false;
		}

		// with curl_multi_init we could do multiple downloads at once...
		$cr = curl_init();
		$fh = @fopen($download_to, 'w');

		curl_setopt($cr, CURLOPT_FILE, $fh);
		curl_setopt($cr, CURLOPT_URL, $download);
		curl_setopt($cr, CURLOPT_HEADER, false);
		curl_setopt($cr, CURLOPT_USERAGENT, pts_codename(true));
		//curl_setopt($cr, CURLOPT_REFERER, "http://www.phoronix-test-suite.com/"); // Setting the referer causes problems for SourceForge downloads
		curl_setopt($cr, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($cr, CURLOPT_CONNECTTIMEOUT, (defined("NETWORK_TIMEOUT") ? NETWORK_TIMEOUT : 20));
		curl_setopt($cr, CURLOPT_BUFFERSIZE, 64000);

		if(PHP_VERSION_ID >= 50300)
		{
			// CURLOPT_PROGRESSFUNCTION only seems to work with PHP 5.3+
			curl_setopt($cr, CURLOPT_NOPROGRESS, false);
			curl_setopt($cr, CURLOPT_PROGRESSFUNCTION, array("pts_network", "curl_status_callback"));
		}

		if(defined("NETWORK_PROXY"))
		{
			curl_setopt($cr, CURLOPT_PROXY, NETWORK_PROXY);
		}

		curl_exec($cr);
		curl_close($cr);
		fclose($fh);

		return true;
	}
	private static function stream_download($download, $download_to, $stream_context_parameters = null, $callback_function = array("pts_network", "stream_status_callback"))
	{
		$stream_context = pts_network::stream_context_create($stream_context_parameters);
		stream_context_set_params($stream_context, array("notification" => $callback_function));

		$file_pointer = @fopen($download, 'r', false, $stream_context);

		if(is_resource($file_pointer) && file_put_contents($download_to, $file_pointer))
		{
			return true;
		}

		return false;
	}
	public static function stream_context_create($parameters = null, $proxy_address = false, $proxy_port = false)
	{
		if(!is_array($parameters))
		{
			$parameters = array();
		}

		if($proxy_address == false && $proxy_port == false && defined("NETWORK_PROXY"))
		{
			$proxy_address = NETWORK_PROXY_ADDRESS;
			$proxy_port = NETWORK_PROXY_PORT;
		}

		if($proxy_address != false && $proxy_port != false && is_numeric($proxy_port))
		{
			$parameters["http"]["proxy"] = "tcp://" . $proxy_address . ":" . $proxy_port;
			$parameters["http"]["request_fulluri"] = true;
		}

		$parameters["http"]["timeout"] = defined("NETWORK_TIMEOUT") ? NETWORK_TIMEOUT : 20;
		$parameters["http"]["user_agent"] = pts_codename(true);

		$stream_context = stream_context_create($parameters);

		return $stream_context;
	}

	//
	// Callback Functions
	//

	private static function stream_status_callback($notification_code, $arg1, $message, $message_code, $downloaded, $download_size)
	{
		static $filesize = 0;
		static $last_float = -1;

		switch($notification_code)
		{
			case STREAM_NOTIFY_FILE_SIZE_IS:
				$filesize = $download_size;
				break;
			case STREAM_NOTIFY_PROGRESS:
				$downloaded_float = $filesize == 0 ? 0 : $downloaded / $filesize;

				if(abs($downloaded_float - $last_float) < 0.01)
				{
					return;
				}

				if(self::$display_mode_holder)
				{
					self::$display_mode_holder->test_install_download_status_update($downloaded_float);
				}

				$last_float = $downloaded_float;
				break;
		}
	}
	private static function curl_status_callback($download_size, $downloaded)
	{
		static $last_float = -1;
		$downloaded_float = $download_size == 0 ? 0 : $downloaded / $download_size;

		if(abs($downloaded_float - $last_float) < 0.01)
		{
			return;
		}

		if(self::$display_mode_holder)
		{
			self::$display_mode_holder->test_install_download_status_update($downloaded_float);
		}

		$last_float = $downloaded_float;
	}
}

?>
