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


class phoromatic_result implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Result';
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
		$main = null;
		if(isset($PATH[0]))
		{
			$upload_ids = explode(',', $PATH[0]);
			$result_file = array();

			foreach($upload_ids as $upload_id)
			{
				$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_results WHERE AccountID = :account_id AND UploadID = :upload_id LIMIT 1');
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$stmt->bindValue(':upload_id', $PATH[0]);
				$result = $stmt->execute();
				$row = $result->fetchArray();

				$composite_xml = phoromatic_server::phoromatic_account_result_path($_SESSION['AccountID'], $upload_id) . 'composite.xml';
				if(!is_file($composite_xml))
				{
					echo 'File Not Found: ' . $composite_xml;
					return false;
				}

				array_push($result_file, new pts_result_merge_select($composite_xml));
			}

			$writer = new pts_result_file_writer(null);
			$attributes = array();
			pts_merge::merge_test_results_process($writer, $result_file, $attributes);

			$result_file = new pts_result_file($writer->get_xml());
			$extra_attributes = array();
			$intent = null;

			$main .= '<h1>' . $result_file->get_title() . '</h1>';

			if($result_file->get_system_count() == 1 || ($intent = pts_result_file_analyzer::analyze_result_file_intent($result_file, $intent, true)))
			{
				$table = new pts_ResultFileCompactSystemsTable($result_file, $intent);
			}
			else
			{
				$table = new pts_ResultFileSystemsTable($result_file);
			}

			$main .= '<p class="result_object">' . pts_render::render_graph_inline_embed($table, $result_file, $extra_attributes) . '</p>';

			foreach($result_file->get_result_objects() as $i => $result_object)
			{
				$main .= '<h2><a name="r-' . $i . '"></a>' . $result_object->test_profile->get_title() . '</h2>';
				$main .= '<p class="result_object">';
				$main .= pts_render::render_graph_inline_embed($result_object, $result_file, $extra_attributes);
				$main .= '</p>';
			}
		}
		else
		{
			// No result
		}

		echo phoromatic_webui_header_logged_in();
		echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in());
		echo phoromatic_webui_footer();
	}
}

?>
