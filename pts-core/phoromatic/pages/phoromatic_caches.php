<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2014 - 2018, Phoronix Media
	Copyright (C) 2014 - 2018, Michael Larabel

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

		if(($dc = phoromatic_server::find_download_cache()))
		{
			$dc_path = dirname($dc) . '/';

			if(is_writable($dc_path))
			{
				if(isset($_POST['dc_submit']))
				{
					$dc_upload_file = basename($_FILES['dc_upload']['name']);
					if(is_file($dc_path . $dc_upload_file))
					{
						$main .= '<p>ERROR: Upload of ' . $dc_upload_file . ' failed; file already exists.</p>';
					}
					else
					{
						if(move_uploaded_file($_FILES['dc_upload']['tmp_name'], $dc_path . $dc_upload_file))
						{
							$main .= '<p>File uploaded: ' . $dc_upload_file . '</p>';
						} else {
							$main .= '<p>ERROR: Upload of ' . $dc_upload_file . ' failed.</p>';
						}
					}
				}
				$main .= '<form action="/?caches" method="post" enctype="multipart/form-data"><p align="center">Add file to download cache: <input type="file" name="dc_upload" id="dc_upload" /> <input type="submit" value="Upload" name="dc_submit"></p></form>';
			}

			$dc_items = phoromatic_server::download_cache_items();

			if(!empty($dc_items))
			{
				$total_file_size = 0;
				$main .= '<table style="margin: 0 auto;"><tr><th>File</th><th>Size</th><th>SHA256</th></tr>';
				foreach($dc_items as $file_name => $info)
				{
					$total_file_size += $info['file_size'];
					$main .= '<tr><td><a href="/download-cache.php?m=1&download=' . $file_name . '">' . $file_name . '</a></td><td>' . round(max(0.1, $info['file_size']  / 1000000), 1) . 'MB</td><td>' . $info['sha256'] . '</td></tr>';
				}
				$main .= '</table>';
				$main .= '<p><strong>' . count($dc_items) . ' Files / ' . round($total_file_size / 1000000) . ' MB Cache Size</strong><br />';
				$main .= '<strong>Download Cache Location:</strong> ' . $dc . '</p>';
			}
		}
		else
		{
			$main .= '<h3>No download cache file could be found; on the Phoromatic Server you should run <strong>phoronix-test-suite make-download-cache</strong>. See the <a href="https://github.com/phoronix-test-suite/phoronix-test-suite/tree/master/documentation">documentation</a> for more information on download-cache setup.</h3>'; // TODO XXX implement from the GUI
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
