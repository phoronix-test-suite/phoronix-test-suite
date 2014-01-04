<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2013 - 2014, Phoronix Media
	Copyright (C) 2013 - 2014, Michael Larabel

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


class pts_webui_result implements pts_webui_interface
{
	static $result_file;
	public static function page_title()
	{
		return 'Test Result';
	}
	public static function page_header()
	{
		return null;
	}
	public static function preload($result)
	{
		if(is_file(PTS_SAVE_RESULTS_PATH . $result[0] . '/composite.xml'))
		{
			self::$result_file = new pts_result_file($result[0]);
			return true;
		}
		return 'pts_webui_results';
	}
	public static function render_page_process($PATH)
	{
		echo '<h1>' . self::$result_file->get_title() . '</h2>';

		$extra_attributes = array();
		$intent = null;

		if(self::$result_file->get_system_count() == 1 || ($intent = pts_result_file_analyzer::analyze_result_file_intent(self::$result_file, $intent, true)))
		{
			$table = new pts_ResultFileCompactSystemsTable(self::$result_file, $intent);

		}
		else
		{
			$table = new pts_ResultFileSystemsTable(self::$result_file);
		}

		echo '<p class="result_object">' . pts_render::render_graph_inline_embed($table, self::$result_file, $extra_attributes) . '</p>';

		foreach(self::$result_file->get_result_objects() as $i => $result_object)
		{
			echo '<h2><a name="r-' . $i . '"></a>' . $result_object->test_profile->get_title() . '</h2>';
			echo '<p class="result_object">';

			echo pts_render::render_graph_inline_embed($result_object, self::$result_file, $extra_attributes);

			echo '</p>';
		}
	}
}

?>
