<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-functions.php: General functions required for Phoronix Test Suite operation.

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


function pts_test_needs_updated_install($identifier)
{
	$needs_install = FALSE;

	if(!is_file(TEST_ENV_DIR . $identifier . "/pts-install.xml")  || !pts_version_comparable(pts_test_profile_version($identifier), pts_test_installed_profile_version($identifier)) || pts_test_checksum_installer($identifier) != pts_test_installed_checksum_installer($identifier))
		$needs_install = TRUE;

	return $needs_install;
}
function pts_test_checksum_installer($identifier)
{
	$md5_checksum = "";

	if(is_file(TEST_RESOURCE_DIR . $identifier . "/install.php"))
		$md5_checksum = md5_file(TEST_RESOURCE_DIR . $identifier . "/install.php");
	else if(is_file(TEST_RESOURCE_DIR . $identifier . "/install.sh"))
		$md5_checksum = md5_file(TEST_RESOURCE_DIR . $identifier . "/install.sh");

	return $md5_checksum;
}
function pts_test_installed_checksum_installer($identifier)
{
	$version = "";

	if(is_file(TEST_ENV_DIR . $identifier . "/pts-install.xml"))
	{
	 	$xml_parser = new tandem_XmlReader(TEST_ENV_DIR . $identifier . "/pts-install.xml", FALSE);
		$version = $xml_parser->getXMLValue(P_INSTALL_TEST_CHECKSUM);
	}

	return $version;
}
function pts_test_profile_version($identifier)
{
	$version = "";

	if(is_file(XML_PROFILE_DIR . $identifier . ".xml"))
	{
	 	$xml_parser = new tandem_XmlReader(XML_PROFILE_DIR . $identifier . ".xml");
		$version = $xml_parser->getXMLValue(P_TEST_PTSVERSION);
	}

	return $version;
}
function pts_test_installed_profile_version($identifier)
{
	$version = "";

	if(is_file(TEST_ENV_DIR . $identifier . "/pts-install.xml"))
	{
	 	$xml_parser = new tandem_XmlReader(TEST_ENV_DIR . $identifier . "/pts-install.xml", FALSE);
		$version = $xml_parser->getXMLValue(P_INSTALL_TEST_VERSION);
	}

	return $version;
}
function pts_test_generate_install_xml($identifier)
{
	$xml_writer = new tandem_XmlWriter();

	$xml_writer->addXmlObject(P_INSTALL_TEST_NAME, 1, $identifier);
	$xml_writer->addXmlObject(P_INSTALL_TEST_VERSION, 1, pts_test_profile_version($identifier));
	$xml_writer->addXmlObject(P_INSTALL_TEST_CHECKSUM, 1, pts_test_checksum_installer($identifier));
	$xml_writer->addXmlObject(P_INSTALL_TEST_INSTALLTIME, 2, date("Y-m-d H:i:s"));
	$xml_writer->addXmlObject(P_INSTALL_TEST_LASTRUNTIME, 2, "0000-00-00 00:00:00");
	$xml_writer->addXmlObject(P_INSTALL_TEST_TIMESRUN, 2, "0");

	file_put_contents(TEST_ENV_DIR . $identifier . "/pts-install.xml", $xml_writer->getXML());
}
function pts_test_refresh_install_xml($identifier)
{
	if(is_file(TEST_ENV_DIR . $identifier . "/pts-install.xml"))
	{
	 	$xml_parser = new tandem_XmlReader(TEST_ENV_DIR . $identifier . "/pts-install.xml", FALSE);
		$xml_writer = new tandem_XmlWriter();

		$xml_writer->addXmlObject(P_INSTALL_TEST_NAME, 1, $xml_parser->getXMLValue(P_INSTALL_TEST_NAME));
		$xml_writer->addXmlObject(P_INSTALL_TEST_VERSION, 1, $xml_parser->getXMLValue(P_INSTALL_TEST_VERSION));
		$xml_writer->addXmlObject(P_INSTALL_TEST_CHECKSUM, 1, $xml_parser->getXMLValue(P_INSTALL_TEST_CHECKSUM));
		$xml_writer->addXmlObject(P_INSTALL_TEST_INSTALLTIME, 2, $xml_parser->getXMLValue(P_INSTALL_TEST_INSTALLTIME));
		$xml_writer->addXmlObject(P_INSTALL_TEST_LASTRUNTIME, 2, date("Y-m-d H:i:s"));
		$xml_writer->addXmlObject(P_INSTALL_TEST_TIMESRUN, 2, $xml_parser->getXMLValue(P_INSTALL_TEST_TIMESRUN) + 1);

		file_put_contents(TEST_ENV_DIR . $identifier . "/pts-install.xml", $xml_writer->getXML());
		return TRUE;
	}
	return FALSE;
}
function pts_test_name_to_identifier($name)
{
	if(empty($name))
		return false;

	$identifier = false;

	foreach(glob(XML_PROFILE_DIR . "*.xml") as $benchmark_file)
	{
	 	$xml_parser = new tandem_XmlReader($benchmark_file);

		if($xml_parser->getXMLValue(P_TEST_TITLE) == $name)
			$identifier = basename($benchmark_file, ".xml");
	}

	return $identifier;
}
function pts_test_identifier_to_name($identifier)
{
	if(empty($identifier))
		return false;

	$name = false;

	if(is_file(XML_PROFILE_DIR . $identifier . ".xml"))
	{
	 	$xml_parser = new tandem_XmlReader(XML_PROFILE_DIR . $identifier . ".xml");
		$name = $xml_parser->getXMLValue(P_TEST_TITLE);
	}

	return $name;
}

?>
