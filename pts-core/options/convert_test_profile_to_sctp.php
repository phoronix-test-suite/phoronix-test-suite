<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel

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

class convert_test_profile_to_sctp implements pts_option_interface
{
	public static function run($r)
	{
		$to_convert = $r[0];

		if(pts_is_test($to_convert))
		{
			$test_profile_xml = file_get_contents(pts_location_test($to_convert));

			// TODO: Fix downloads.xml reading
			//if(is_file(($file = pts_location_test_resources($to_convert) . "downloads.xml")))
			//{
			//	$test_downloads = file_get_contents($file);
			//}
			if(is_file(($file = pts_location_test_resources($to_convert) . "install.sh")) || is_file(($file = pts_location_test_resources($to_convert) . "install.php")))
			{
				$test_install = file_get_contents($file);
			}
			if(is_file(($file = pts_location_test_resources($to_convert) . "parse-results.sh")) || is_file(($file = pts_location_test_resources($to_convert) . "parse-results.php")))
			{
				$test_parse_results = file_get_contents($file);
			}
			if(is_file(($file = pts_location_test_resources($to_convert) . "pre.sh")) || is_file(($file = pts_location_test_resources($to_convert) . "pre.php")))
			{
				$test_pre = file_get_contents($file);
			}
			if(is_file(($file = pts_location_test_resources($to_convert) . "interim.sh")) || is_file(($file = pts_location_test_resources($to_convert) . "interim.php")))
			{
				$test_interim = file_get_contents($file);
			}
			if(is_file(($file = pts_location_test_resources($to_convert) . "post.sh")) || is_file(($file = pts_location_test_resources($to_convert) . "post.php")))
			{
				$test_post = file_get_contents($file);
			}
			if(is_file(($file = pts_location_test_resources($to_convert) . "validate-install.sh")) || is_file(($file = pts_location_test_resources($to_convert) . "validate-install.php")))
			{
				$test_validate_install = file_get_contents($file);
			}
			if(is_file(($file = pts_location_test_resources($to_convert) . "validate-result.sh")) || is_file(($file = pts_location_test_resources($to_convert) . "validate-result.php")))
			{
				$test_validate_result = file_get_contents($file);
			}

			$xml_writer = new tandem_XmlWriter();
			$xml_writer->addXmlObject(P_TEST_SCTP_INSTALLSCRIPT, 0, $test_install);
			$xml_writer->addXmlObject(P_TEST_SCTP_VALIDATE_INSTALL, 0, $test_validate_install);
			$xml_writer->addXmlObject(P_TEST_SCTP_DOWNLOADS, 0, $test_downloads);
			$xml_writer->addXmlObject(P_TEST_SCTP_RESULTSPARSER, 0, $test_parse_results);
			$xml_writer->addXmlObject(P_TEST_SCTP_VALIDATE_RESULT, 0, $test_validate_result);
			$xml_writer->addXmlObject(P_TEST_SCTP_PRERUN, 0, $test_pre);
			$xml_writer->addXmlObject(P_TEST_SCTP_INTERIMRUN, 0, $test_interim);
			$xml_writer->addXmlObject(P_TEST_SCTP_POSTRUN, 0, $test_post);
			$sctp_xml = $xml_writer ->getXML();

			$test_profile_xml = substr($test_profile_xml, 0, strrpos($test_profile_xml, "</PhoronixTestSuite>"));
			$sctp_xml = substr($sctp_xml, strpos($sctp_xml, "<PhoronixTestSuite>") + 19);

			$sctp_file = "#!/usr/bin/env /usr/bin/phoronix-test-suite\n" . $test_profile_xml . "\n" . $sctp_xml;

			file_put_contents(SCTP_DIR . "test.sctp", $sctp_file);
			chmod(SCTP_DIR . "test.sctp", 0755);
		
			echo "\n";
		}
		else
		{
			echo "\n" . $to_info . " is not recognized.\n";
		}
	}
}

?>
