<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2015 - 2021, Phoronix Media
	Copyright (C) 2015 - 2021, Michael Larabel

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

class phoromatic_tests implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Tests';
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
		$identifier_item = isset($PATH[1]) ? $PATH[0] . '/' . $PATH[1] : false;

		if($identifier_item && pts_test_profile::is_test_profile($identifier_item))
		{
			$tp = new pts_test_profile($identifier_item);
			$tp_identifier = $tp->get_identifier(false);
			$main .= pts_web_embed::test_profile_overview($tp);
			$main .= '<h2>Recent Results With This Test</h2>';
			$stmt = phoromatic_server::$db->prepare('SELECT Title, PPRID FROM phoromatic_results WHERE AccountID = :account_id AND UploadID IN (SELECT DISTINCT UploadID FROM phoromatic_results_results WHERE AccountID = :account_id AND TestProfile LIKE :tp) ORDER BY UploadTime DESC LIMIT 30');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$stmt->bindValue(':tp', $tp_identifier . '%');
			$result = $stmt->execute();
			$recent_result_count = 0;
			while($result && $row = $result->fetchArray())
			{
				$recent_result_count++;
				$main .= '<h2><a href="/?result/' . $row['PPRID'] . '">' . $row['Title'] . '</a></h2>';
			}

			if($recent_result_count == 0)
			{
				$main .= '<p>No results found on this Phoromatic Server for the ' . $tp->get_title() . ' test profile.</p>';
			}
			else if(false) // previously: $recent_result_count > 5 TODO broken
			{
				// TODO XXX below code is borked
				$stmt = phoromatic_server::$db->prepare('SELECT UploadID, SystemID, UploadTime FROM phoromatic_results WHERE AccountID = :account_id AND UploadID IN (SELECT DISTINCT UploadID FROM phoromatic_results_results WHERE AccountID = :account_id AND TestProfile LIKE :tp) ORDER BY UploadTime DESC LIMIT 1000');
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$stmt->bindValue(':tp', $tp_identifier . '%');
				$result = $stmt->execute();
				$recent_result_count = 0;
				$result_file = new pts_result_file(null, true);
				while($result && $row = $result->fetchArray())
				{
					$composite_xml = phoromatic_server::phoromatic_account_result_path($_SESSION['AccountID'], $row['UploadID']) . 'composite.xml';
					if(!is_file($composite_xml))
					{
						continue;
					}

					// Add to result file
					$system_name = strtotime($row['UploadTime']) . ': ' . phoromatic_server::system_id_to_name($row['SystemID']);
					$sub_result_file = new pts_result_file($composite_xml, true);
					foreach($sub_result_file->get_result_objects() as $obj)
					{
						if($obj->test_profile->get_identifier(false) == $tp_identifier)
						{
							$obj->test_result_buffer->rename(null, $system_name);
							$result_file->add_result($obj);
						}
					}
				}

				$table = null;
				$extra_attributes = array('multi_way_comparison_invert_default' => false);
				$f = false;
				foreach($result_file->get_result_objects() as $obj)
				{
					$obj->test_profile->set_display_format('SCATTER_PLOT');

					foreach($obj->test_result_buffer->buffer_items as $i => &$item)
					{
						if(!is_numeric(substr($item->get_result_identifier(), 0, strpos($item->get_result_identifier(), ':'))))
						{
							unset($obj->test_result_buffer->buffer_items[$i]);
						}
					}

					$result_file = null;
					$main .= '<p align="center">' . pts_render::render_graph_inline_embed($obj, $result_file, $extra_attributes) . '</p>';
				}


			}
		}
		else
		{
			$dc = pts_client::download_cache_path();
			$dc_exists = is_file($dc . 'pts-download-cache.json');
			if($dc_exists)
			{
				$cache_json = file_get_contents($dc . 'pts-download-cache.json');
				$cache_json = json_decode($cache_json, true);
			}

			$tests_to_show = array();
			foreach(array_merge(pts_tests::local_tests(), pts_openbenchmarking::available_tests(false)) as $test)
			{
				$cache_checked = false;
				if($dc_exists)
				{
					if($cache_json && isset($cache_json['phoronix-test-suite']['cached-tests']))
					{
						$cache_checked = true;
						if(!in_array($test, $cache_json['phoronix-test-suite']['cached-tests']))
						{
							//continue;
						}
					}
				}
				if(!$cache_checked && phoromatic_server::read_setting('show_local_tests_only') && pts_test_install_request::test_files_available_on_local_system($test) == false)
				{
					continue;
				}
				$tp = new pts_test_profile($test);

				if($tp->get_title() == null)
					continue;

				$tests_to_show[] = $tp;
			}
			$main .= pts_web_embed::tests_list($tests_to_show);
		}

		echo phoromatic_webui_header_logged_in();
		echo '<div id="pts_phoromatic_main_area">' . $main . '</div>';
		echo phoromatic_webui_footer();
	}
}

?>
