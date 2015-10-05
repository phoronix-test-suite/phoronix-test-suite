<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2014 - 2015, Phoronix Media
	Copyright (C) 2014 - 2015, Michael Larabel

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


class phoromatic_caches implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Download Caches';
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
		$main = '<h1>Cache Settings</h1>
				<h2>Test Profile Download Cache</h2>
				<p>Below are a list of files for verification/debugging purposes that are currently cached by the Phoromatic Server and available for Phoronix Test Suite client systems to download. These are files that are needed by various test profiles in the Phoronix Test Suite. To add more data to this Phoromatic Server cache, from the server run <strong>phoronix-test-suite make-download-cache</strong> while passing the names of any tests/suites you wish to have download and generate a cache for so they can be made available to the Phoronix Test Suite clients on your network.</p>';

		$dc = pts_strings::add_trailing_slash(pts_strings::parse_for_home_directory(pts_config::read_user_config('PhoronixTestSuite/Options/Installation/CacheDirectory', PTS_DOWNLOAD_CACHE_PATH)));
		$dc_exists = is_file($dc . 'pts-download-cache.json');
		if($dc_exists)
		{
			$cache_json = file_get_contents($dc . 'pts-download-cache.json');
			$cache_json = json_decode($cache_json, true);
		}
		if(is_file($dc . 'pts-download-cache.json'))
		{
			if($cache_json && isset($cache_json['phoronix-test-suite']['download-cache']))
			{
				$total_file_size = 0;
				$main .= '<table style="margin: 0 auto;"><tr><th>File</th><th>Size</th></tr>';
				foreach($cache_json['phoronix-test-suite']['download-cache'] as $file_name => $inf)
				{
					$total_file_size += $cache_json['phoronix-test-suite']['download-cache'][$file_name]['file_size'];
					$main .= '<tr><td>' . $file_name . '</td><td>' . round(max(0.1, $cache_json['phoronix-test-suite']['download-cache'][$file_name]['file_size']  / 1000000), 1) . 'MB</td></tr>';
				}
				$main .= '</table>';
				$main .= '<p><strong>' . count($cache_json['phoronix-test-suite']['download-cache']) . ' Files / ' . round($total_file_size / 1000000) . ' MB Cache Size</strong></p>';
			}
		}
		else
		{
			$main .= '<h3>No download cache file could be found; on the Phoromatic Server you should run <strong>phoronix-test-suite make-download-cache</strong>.</h3>'; // TODO XXX implement from the GUI
		}

		$main .= '<hr /><h2>OpenBenchmarking.org Cache Data</h2>';
		$main .= '<p>Below is information pertaining to the OpenBenchmarking.org cache present on the Phoromatic Server. To update this cache, run <strong>phoronix-test-suite make-openbenchmarking-cache</strong> from the server.</p>';

		$index_files = pts_file_io::glob(PTS_OPENBENCHMARKING_SCRATCH_PATH . '*.index');
		$main .= '<table style="margin: 0 auto;"><tr><th>Repository</th><th>Last Updated</th></tr>';
		foreach($index_files as $index_file)
		{
			$index_data = json_decode(file_get_contents($index_file), true);
			$main .= '<tr><td>' . basename($index_file, '.index') . '</td><td>' . date('d F Y H:i', $index_data['main']['generated']) . '</td></tr>';
		}
		$main .= '</table>';

		echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in());
		echo phoromatic_webui_footer();
	}
}

?>
