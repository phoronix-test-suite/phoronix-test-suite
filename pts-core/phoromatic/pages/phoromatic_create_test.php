<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2018, Phoronix Media
	Copyright (C) 2018, Michael Larabel

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

class phoromatic_create_test implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Create Test Profile';
	}
	public static function page_header()
	{
		return null;
	}
	public static function preload($PATH)
	{
		if(PHOROMATIC_USER_IS_VIEWER)
		{
			return true;
		}

		$types = pts_validation::process_xsd_types();

		if(isset($_POST['tp_update']) && isset($_POST['test-definition_xml']))
		{
			$tp = new pts_test_profile($_POST['test-definition_xml']);
			$tp->set_identifier($_POST['tp_update']);
			$tp_path = PTS_TEST_PROFILE_PATH . $tp->get_identifier(false) . '-' . $tp->get_test_profile_version();
			pts_file_io::mkdir($tp_path);

			foreach(pts_validation::test_profile_permitted_files() as $permitted_file)
			{
				$pfs = str_replace('.', '_', $permitted_file);
				if(isset($_POST[$pfs]))
				{
					/* Replaces DOS line-endings of the POST request with platform compatible ones */
					$fc = str_replace("\r\n", PHP_EOL, $_POST[$pfs]);
					file_put_contents($tp_path . '/' . $permitted_file, $fc);
				}
			}
			header('Location: /?create_test/' . $tp->get_identifier(false) . '-' . $tp->get_test_profile_version());
		}

		if(isset($_POST['test_profile_base']))
		{
			$tp_identifier = 'local/' . pts_validation::string_to_sanitized_test_profile_base(str_replace('local/', '', $_POST['test_profile_base']));

			$writer = new nye_XmlWriter();
			$ret = pts_validation::xsd_to_var_array_generate_xml(pts_openbenchmarking::openbenchmarking_standards_path() . 'schemas/test-profile.xsd', $types, $_POST, $writer);
			$passed = true;
			if($ret !== true)
			{
				echo '<p>ERROR: ' . $ret . '</p>';
				$passed = false;
			}
			else
			{
				$tp = new pts_test_profile($writer->getXML());
				$tp_path = PTS_TEST_PROFILE_PATH . $tp_identifier . '-' . $tp->get_test_profile_version();
				pts_file_io::mkdir($tp_path);
				$writer->saveXMLFile($tp_path . '/test-definition.xml');
			}

			$writer = new nye_XmlWriter();
			$ret = pts_validation::xsd_to_var_array_generate_xml(pts_openbenchmarking::openbenchmarking_standards_path() . 'schemas/test-profile-downloads.xsd', $types, $_POST, $writer);
			$writer->saveXMLFile($tp_path . '/downloads.xml');

			if($passed)
			{
				pts_validation::generate_test_profile_file_templates($tp_identifier, $tp_path);
				header('Location: /?create_test/' . $tp_identifier . '-' . $tp->get_test_profile_version());
			}
		}
		if(isset($_POST['dc_select_item']))
		{
			$to_add = false;

			foreach(phoromatic_server::download_cache_items() as $file_name => $info)
			{
				if($file_name == $_POST['dc_select_item'])
				{
					$to_add = $info;
					break;
				}
			}

			if($to_add)
			{
				$identifier_item = isset($PATH[1]) ? $PATH[0] . '/' . $PATH[1] : false;
				if($identifier_item && pts_test_profile::is_test_profile($identifier_item))
				{
					$tp = new pts_test_profile($identifier_item);
					$tdw = new nye_XmlWriter();

					// TODO adapt former code:
					/*
					$tdw->add_download($info['file_name'], $info['md5'], $info['sha256'], $info['file_name'], $info['file_size'], null, null);
					
					INTO:
					
					$tp's get_downloads() with new pts_test_file_download entries
					*/

					$ret = pts_validation::xsd_to_rebuilt_xml(pts_openbenchmarking::openbenchmarking_standards_path() . 'schemas/test-profile-downloads.xsd', $types, $tp, $tdw);
					$tdw->saveXMLFile(PTS_TEST_PROFILE_PATH . $tp->get_identifier(false) . '-' . $tp->get_test_profile_version() . '/downloads.xml');
				}
			}
		}

		if(isset($PATH[1]) && strpos($PATH[1], '&delete') !== false)
		{
			$identifier_item = isset($PATH[1]) ? $PATH[0] . '/' . str_replace('&delete', '', $PATH[1]) : false;
			if($identifier_item && pts_test_profile::is_test_profile($identifier_item))
			{
				$tp = new pts_test_profile($identifier_item);
				if($tp->get_identifier() != null)
				{
					pts_file_io::delete($tp->get_resource_dir(), null, true);
					header('Location: /?tests');
				}
			}
		}

		return true;
	}
	public static function render_page_process($PATH)
	{
		if(phoromatic_server::read_setting('allow_test_profile_creation') != 1)
		{
			exit;
		}
		$main = null;
		if(PHOROMATIC_USER_IS_VIEWER)
		{
			goto RENDER_PAGE;
		}

		$identifier_item = isset($PATH[1]) ? $PATH[0] . '/' . $PATH[1] : false;
		if($identifier_item && pts_test_profile::is_test_profile($identifier_item))
		{
			$tp = new pts_test_profile($identifier_item);
			$main .= '<h1>Test Profile Editor: ' . $tp->get_identifier() . '</h1>';

			if(phoromatic_server::find_download_cache())
			{
				$main .= '<h3>Add File From Download Cache To Test</h3>';
				$dc_items = phoromatic_server::download_cache_items();
				if(!empty($dc_items))
				{
					$main .= '<form action="' . $_SERVER['REQUEST_URI'] . '" name="add_dc_file" id="add_dc_file" method="post"><a href="/?caches">Manage Download Cache</a> - Add File From Download Cache: <select name="dc_select_item">';
					foreach($dc_items as $file_name => $info)
					{
						$main .= '<option value="' . $file_name . '">' . $file_name . '</option>';
					}
					$main .= '</select> <input type="submit" value="Add File" /></form>';
				}
			}

			$main .= '<form action="?create_test/' . $tp->get_identifier() . '" name="create_test" id="create_test" method="post" enctype="multipart/form-data"><input type="hidden" name="tp_update" value="' . $tp->get_identifier() . '" />';
			foreach(pts_file_io::glob($tp->get_resource_dir() . '/*') as $file)
			{
				$file_name = basename($file);
				$contents = file_get_contents($file);
				$extension = substr($file_name, strrpos($file_name, '.') + 1);
				$main .= '<p><strong>' . $file_name . ':</strong></p>';
				if($extension == 'xml')
				{
					$contents = htmlentities($contents, ENT_COMPAT | ENT_XML1, 'UTF-8', false);
				}
				$main .= '<p><textarea style="min-height: 160px; height: auto; width: 100%;" rows="' . ceil(count(explode("\n", $contents)) * 1.05) . '" name="' . $file_name . '">' . $contents . '</textarea></p>';
					$main .= '</p>';
			}
			$main .= '<input name="submit" value="Save Test Profile" type="submit" /></form>';
			goto RENDER_PAGE;
		}

		$main .= '<form action="' . $_SERVER['REQUEST_URI'] . '" name="create_test" id="create_test" method="post">';
		$main .= '<h1>Test Profile Creator</h1>';
		$main .= '<p>Name of test the test profile, used as the unique identifier for calling the test profile, etc. The input will automatically be made lower-case and spaces turned into dashes in generating the actual test profile identifier.</p><p><em>local/</em><input type="text" name="test_profile_base" value="" required /></p>';
		$main .= '<p>Fill out the below fields to create the XML meta-data used to define a Phoronix Test Suite / OpenBenchmarking.org test profile.</p>';
		$types = pts_validation::process_xsd_types();
		$main .= '<h2>test-definition.xml</h2>';
		$main .= pts_validation::xsd_to_html_creator(pts_openbenchmarking::openbenchmarking_standards_path() . 'schemas/test-profile.xsd', $types);

		//pts_client::$display->generic_heading('downloads.xml Creation');
		//do
		//{
		$main .= '<h2>downloads.xml</h2>';
		$main .= pts_validation::xsd_to_html_creator(pts_openbenchmarking::openbenchmarking_standards_path() . 'schemas/test-profile-downloads.xsd', $types);
		//}
		//while(pts_user_io::prompt_bool_input('Add another file/download?', -1));
		//pts_validation::generate_test_profile_file_templates($tp_identifier, $tp_path);
		$main .= '<input name="submit" value="Save" type="submit" /></form>';

		RENDER_PAGE:
		echo phoromatic_webui_header_logged_in();
		echo '<div id="pts_phoromatic_main_area">' . $main . '</div>';
		echo phoromatic_webui_footer();
	}
}

?>
