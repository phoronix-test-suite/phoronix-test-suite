<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2018, Phoronix Media
	Copyright (C) 2010 - 2018, Michael Larabel

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
	public function rebuild_download_file(&$test_profile)
	{
		foreach($test_profile->get_downloads() as $file)
		{
			$this->add_download($file->get_download_url_string(), $file->get_md5(), $file->get_sha256(), $file->get_filename(), $file->get_filesize(), $file->get_platform_string(), $file->get_architecture_string());
		}
	}
	public function add_download($url_string, $md5 = null, $sha256 = null, $file_name = null, $file_size = null, $platform_specific = null, $architecture_specific = null)
	{
		if(empty($url_string))
		{
			$url_string = $file_name;
		}
		$this->xml_writer->addXmlNode('PhoronixTestSuite/Downloads/Package/URL', $url_string);
		$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/Downloads/Package/MD5', $md5);
		$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/Downloads/Package/SHA256', $sha256);

		if(basename($url_string) != $file_name)
		{
			// If the downloaded file is the same name as the file name, pts-core already obtains it, so having it here is redundant.
			$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/Downloads/Package/FileName', $file_name);
		}

		$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/Downloads/Package/FileSize', $file_size);
		$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/Downloads/Package/PlatformSpecific', $platform_specific);
		$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/Downloads/Package/ArchitectureSpecific', $architecture_specific);
	}
}

?>
