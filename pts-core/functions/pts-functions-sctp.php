<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-functions-sctp.php: Functions For Self-Contained Test Profiles

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

function pts_sctp_test_directory()
{
	return PTS_TEMP_DIR . "sctp/" . basename(SCTP_FILE) . "/";
}
function pts_remove_sctp_test_files()
{
	return pts_remove(pts_sctp_test_directory());
}
function pts_generate_sctp_layer()
{
	$xml_parser = new tandem_XmlReader(SCTP_FILE);
	$test_directory = pts_sctp_test_directory();

	if(!$xml_parser->isDefined(P_TEST_TITLE))
	{
		pts_exit("\n" . SCTP_FILE . " is not a valid self-contained test profile!\n");
	}

	if(!is_dir(PTS_TEMP_DIR . "sctp/"))
	{
		mkdir(PTS_TEMP_DIR . "sctp/");
	}
	if(!is_dir($test_directory))
	{
		mkdir($test_directory);
	}

	$sctp_stages = array("install" => P_TEST_SCTP_INSTALLSCRIPT, "downloads" => P_TEST_SCTP_DOWNLOADS, "parse-results" => P_TEST_SCTP_RESULTSPARSER, "pre" => P_TEST_SCTP_PRERUN, "post" => P_TEST_SCTP_POSTRUN);
	foreach($sctp_stages as $stage_file => $stage_point)
	{
		$object = $xml_parser->getXMLValue($stage_point);

		if(!empty($object))
		{
			$object_type = pts_evaluate_script_type($object);
			$object = trim($object);

			if($stage_file == "downloads")
			{
				$object_type = "XML";
				$download_counter = 0;
				$downloads_xml = new tandem_XmlWriter();

				foreach(explode(",", $object) as $download_segment)
				{
					$downloads_xml->addXmlObject(P_DOWNLOADS_PACKAGE_URL, $download_counter, trim($download_segment));
					$download_counter++;
				}
				$object = $downloads_xml->getXML();
			}

			if($object_type == "PHP")
			{
				file_put_contents($test_directory . $stage_file . ".php", $object);
			}
			else if($object_type == "SH")
			{
				file_put_contents($test_directory . $stage_file . ".sh", $object);
				chmod($test_directory . $stage_file . ".sh", 0755);
			}
			else if($object_type == "XML")
			{
				file_put_contents($test_directory . $stage_file . ".xml", $object);
			}
		}
	}
}

?>
