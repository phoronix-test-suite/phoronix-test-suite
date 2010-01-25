<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
	pts-functions_net.php: General functions that are network functions for the Phoronix Test Suite

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


function pts_http_stream_context_create($http_parameters = null, $proxy_address = false, $proxy_port = false)
{
	if(!is_array($http_parameters))
	{
		$http_parameters = array();
	}

	if($proxy_address == false && $proxy_port == false && defined("NETWORK_PROXY"))
	{
		$proxy_address = NETWORK_PROXY_ADDRESS;
		$proxy_port = NETWORK_PROXY_PORT;
	}

	if($proxy_address != false && $proxy_port != false && is_numeric($proxy_port))
	{
		$http_parameters["http"]["proxy"] = "tcp://" . $proxy_address . ":" . $proxy_port;
		$http_parameters["http"]["request_fulluri"] = true;
	}

	$http_parameters["http"]["timeout"] = 12;

	$stream_context = stream_context_create($http_parameters);

	return $stream_context;
}
function pts_http_get_contents($url, $override_proxy = false, $override_proxy_port = false)
{
	if(defined("NO_NETWORK_COMMUNICATION"))
	{
		return false;
	}

	$stream_context = pts_http_stream_context_create(null, $override_proxy, $override_proxy_port);
	$contents = pts_file_get_contents($url, 0, $stream_context);

	return $contents;
}
function pts_http_upload_via_post($url, $to_post_data)
{
	if(defined("NO_NETWORK_COMMUNICATION"))
	{
		return false;
	}

	$upload_data = http_build_query($to_post_data);
	$http_parameters = array("http" => array("method" => "POST", "content" => $upload_data));
	$stream_context = pts_http_stream_context_create($http_parameters);
	$opened_url = @fopen($url, "rb", false, $stream_context);
	$response = @stream_get_contents($opened_url);

	return $response;
}
function pts_curl_download($download, $download_to, $connection_timeout = 25)
{
	if(!function_exists("curl_init"))
	{
		return -1;
	}

	// with curl_multi_init we could do multiple downloads at once...
	$cr = curl_init();
	$fh = fopen($download_to, 'w');

	curl_setopt($cr, CURLOPT_FILE, $fh);
	curl_setopt($cr, CURLOPT_URL, $download);
	curl_setopt($cr, CURLOPT_HEADER, false);
	curl_setopt($cr, CURLOPT_USERAGENT, pts_codename(true));
	//curl_setopt($cr, CURLOPT_REFERER, "http://www.phoronix-test-suite.com/"); // Setting the referer causes problems for SourceForge downloads
	curl_setopt($cr, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($cr, CURLOPT_CONNECTTIMEOUT, $connection_timeout);
	curl_setopt($cr, CURLOPT_BUFFERSIZE, 64000);

	if(PHP_VERSION_ID >= 50300)
	{
		curl_setopt($cr, CURLOPT_NOPROGRESS, false);
		curl_setopt($cr, CURLOPT_PROGRESSFUNCTION, "pts_curl_status_callback");
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
function pts_curl_status_callback($download_size, $downloaded)
{
	static $last_float = -1;
	$downloaded_float = $downloaded / $download_size;

	if(abs($downloaded_float - $last_float) < 0.05)
	{
		return;
	}

	$display_mode = pts_display_mode_holder();

	if($display_mode)
	{
		$display_mode->test_install_update_download_status($download_float);
		pts_display_mode_holder($display_mode);
	}

	$last_float = $downloaded_float;
}

?>
