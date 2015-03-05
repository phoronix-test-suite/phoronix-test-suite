<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2015, Phoronix Media
	Copyright (C) 2015, Michael Larabel

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


class phoromatic_local_suites implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Local Test Suite';
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
		$suite_dir = phoromatic_server::phoromatic_account_suite_path($_SESSION['AccountID']);
		$main = '<h1>Local Suites</h1><p>These are test suites created by you or another account within your group. Suites are an easy collection of test profiles. New suits can be trivially made via the <a href="/?build_suite">build suite</a> page.</p>';

		$suite_count = 0;
		foreach(pts_file_io::glob($suite_dir . '*/suite-definition.xml') as $xml_path)
		{
			$suite_count++;
			$id = basename(dirname($xml_path));
			$test_suite = new pts_test_suite($xml_path);

			$main .= '<h1>' . $test_suite->get_title() . ' [' . $id . ']</h1>';
			$main .= '<p><strong>' . $test_suite->get_maintainer() . '</strong></p>';
			$main .= '<p><em>' . $test_suite->get_description() . '</em></p>';
			$main .= '<div style="max-height: 200px; overflow-y: scroll;">';

			foreach($test_suite->get_contained_test_result_objects() as $tro)
			{
				$main .= '<h3>' . $tro->test_profile->get_title() . ' [' . $tro->test_profile->get_identifier() . ']</h3>';
				$main .= '<p>' . $tro->get_arguments_description() . '</p>';
			}
			$main .= '</div>';
			$main .= '<hr />';
		}

		if($suite_count == 0)
			$main .= '<h1>No Test Suites Found</h1>';

		echo phoromatic_webui_header_logged_in();
		echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in());
		echo phoromatic_webui_footer();
	}
}

?>
