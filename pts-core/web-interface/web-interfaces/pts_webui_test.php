<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2013, Phoronix Media
	Copyright (C) 2013, Michael Larabel

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


class pts_webui_test implements pts_webui_interface
{
	private static $test_profile = false;

	public static function preload($REQUEST)
	{
		$test = implode('/', $REQUEST);

		if(pts_test_profile::is_test_profile($test))
		{
			$test = new pts_test_profile($test);

			if($test->get_title() != null)
			{
				self::$test_profile = $test;
				return true;
			}
		}
		return 'pts_webui_tests';
	}
	public static function page_title()
	{
		return self::$test_profile->get_title() . ' [' . self::$test_profile->get_identifier() . ']';
	}
	public static function page_header()
	{
		return null;
	}
	public static function render_page_process($PATH)
	{
		$test_version = self::$test_profile->get_app_version();
		$test_title = self::$test_profile->get_title() . ($test_version != null ? ' ' . $test_version : null);

		echo '<h1>' . $test_title . '</h1>';

		echo '<div style="float: left; width: 60%;">';

			echo '<p>' . self::$test_profile->get_description() . '</p>';

		echo '</div>';


		echo '<div style="float: right; width: 40%;">';

		$tabular_info = array(
			array('Test Profile', self::$test_profile->get_identifier()),
			array('Maintainer', self::$test_profile->get_maintainer()),
			array('Test Type', self::$test_profile->get_test_hardware_type()),
			array('Software Type', self::$test_profile->get_test_software_type()),
			array('License Type', self::$test_profile->get_license()),
			array('Test Status', self::$test_profile->get_status()),
			);

		$project_url = self::$test_profile->get_project_url();
		$project_url = parse_url($project_url);

		if($project_url != null && isset($project_url['host']))
		{
			array_push($tabular_info, array('Project Site', '<a href="' . self::$test_profile->get_project_url() . '" target="_blank">' . $project_url['host'] . '</a>'));
		}

		echo '<h4>Test Profile Information</h4>';
		pts_webui_2d_array_to_table($tabular_info);

		$tabular_info = array();

		if(self::$test_profile->get_estimated_run_time() > 1)
		{
			array_push($tabular_info, array('Estimated Test Run-Time', pts_strings::plural_handler(ceil(self::$test_profile->get_estimated_run_time() / 60), 'Minute')));
		}

		$download_size = self::$test_profile->get_download_size();
		if(!empty($download_size))
		{
			array_push($tabular_info, array('Download Size', $download_size . ' MB'));
		}

		$environment_size = self::$test_profile->get_environment_size();
		if(!empty($environment_size))
		{
			array_push($tabular_info, array('Environment Size', $environment_size . ' MB'));
		}

		if(self::$test_profile->test_installation != false)
		{
			$last_run = self::$test_profile->test_installation->get_last_run_date();
			$last_run = $last_run == '0000-00-00' ? 'Never' : date('j F Y', strtotime($last_run));

			$avg_time = self::$test_profile->test_installation->get_average_run_time();
			$avg_time = !empty($avg_time) ? pts_strings::format_time($avg_time, 'SECONDS') : null;
			$latest_time = self::$test_profile->test_installation->get_latest_run_time();
			$latest_time = !empty($latest_time) ? pts_strings::format_time($latest_time, 'SECONDS') : 'N/A';

			array_push($tabular_info, array('Last Local Run', $last_run));

			if($last_run != 'Never')
			{
				if(self::$test_profile->test_installation->get_run_count() > 1)
				{
					array_push($tabular_info, array('Average Local Run-Time', $avg_time));
				}

				if($latest_time != null)
				{
					array_push($tabular_info, array('Latest Local Run-Time', $latest_time));
				}
				if(self::$test_profile->test_installation->get_run_count() > 0)
				{
					array_push($tabular_info, array('Times Run Locally', self::$test_profile->test_installation->get_run_count()));
				}
			}
		}

		echo '<h4>Installation Data</h4>';
		pts_webui_2d_array_to_table($tabular_info);

		$dependencies = self::$test_profile->get_dependency_names();
		if(!empty($dependencies) && !empty($dependencies[0]))
		{
			array_unshift($dependencies, 'Test Dependencies');
			pts_webui_1d_array_to_table($dependencies);
		}

		if(self::$test_profile->test_installation == false)
		{
			$files = pts_test_install_request::read_download_object_list(self::$test_profile);

			if(count($files) > 0)
			{
				$download_files = array('Test Files');

				foreach($files as &$file)
				{
					array_push($download_files, $file->get_filename() . ' [' . max(0.1, round($file->get_filesize() / 1048576, 1)) . 'MB]');
				}
				pts_webui_1d_array_to_table($download_files);
			}
		}

		echo '</div>';
	}
}

?>
