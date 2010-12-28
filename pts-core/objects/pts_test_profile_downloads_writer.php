<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010, Phoronix Media
	Copyright (C) 2010, Michael Larabel

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

pts_load_xml_definitions('test-profile-downloads.xml');

class pts_test_profile_downloads_writer
{
	private $xml_writer = null;

	public function __construct()
	{
		$this->xml_writer = new nye_XmlWriter();
	}
	public function get_xml()
	{
		return $this->xml_writer->getXML();
	}
	public function save_xml($to_save)
	{
		return $this->xml_writer->saveXMLFile($to_save);
	}
	public function rebuild_download_file($download_xml_file)
	{
		$xml_parser = new pts_test_downloads_nye_XmlReader($download_xml_file);
		$package_url = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_URL);
		$package_md5 = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_MD5);
		$package_filename = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_FILENAME);
		$package_filesize = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_FILESIZE);
		$package_platform = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_PLATFORMSPECIFIC);
		$package_architecture = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_ARCHSPECIFIC);

		foreach(array_keys($package_url) as $i)
		{
			$this->add_download($package_url[$i], $package_md5[$i], $package_filename[$i], $package_filesize[$i], $package_platform[$i], $package_architecture[$i]);
		}
	}
	public function add_download($url_string, $md5 = null, $file_name = null, $file_size = null, $platform_specific = null, $architecture_specific = null)
	{
		$this->xml_writer->addXmlNode(P_DOWNLOADS_PACKAGE_URL, $url_string);
		$this->xml_writer->addXmlNodeWNE(P_DOWNLOADS_PACKAGE_MD5, $md5);
		$this->xml_writer->addXmlNodeWNE(P_DOWNLOADS_PACKAGE_FILENAME, $file_name);
		$this->xml_writer->addXmlNodeWNE(P_DOWNLOADS_PACKAGE_FILESIZE, $file_size);
		$this->xml_writer->addXmlNodeWNE(P_DOWNLOADS_PACKAGE_PLATFORMSPECIFIC, $platform_specific);
		$this->xml_writer->addXmlNodeWNE(P_DOWNLOADS_PACKAGE_ARCHSPECIFIC, $architecture_specific);
	}
}

?>
