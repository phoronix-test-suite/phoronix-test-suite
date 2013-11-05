<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2013, Phoronix Media
	Copyright (C) 2008 - 2013, Michael Larabel

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

class pts_installed_test
{
	private $xml_parser;

	public function __construct(&$test_profile)
	{
		$install_path = $test_profile->get_install_dir();
		$read_xml = is_file($install_path . 'pts-install.xml') ? $install_path . 'pts-install.xml' : null;
		$this->xml_parser = new nye_XmlReader($read_xml);
	}
	public function get_install_date_time()
	{
		return $this->xml_parser->getXMLValue('PhoronixTestSuite/TestInstallation/History/InstallTime');
	}
	public function get_install_date()
	{
		return substr($this->get_install_date_time(), 0, 10);
	}
	public function get_last_run_date_time()
	{
		return $this->xml_parser->getXMLValue('PhoronixTestSuite/TestInstallation/History/LastRunTime');
	}
	public function get_last_run_date()
	{
		return substr($this->get_install_date_time(), 0, 10);
	}
	public function get_installed_version()
	{
		return $this->xml_parser->getXMLValue('PhoronixTestSuite/TestInstallation/Environment/Version');
	}
	public function get_average_run_time()
	{
		return $this->xml_parser->getXMLValue('PhoronixTestSuite/TestInstallation/History/AverageRunTime');
	}
	public function get_latest_run_time()
	{
		return $this->xml_parser->getXMLValue('PhoronixTestSuite/TestInstallation/History/LatestRunTime');
	}
	public function get_latest_install_time()
	{
		return $this->xml_parser->getXMLValue('PhoronixTestSuite/TestInstallation/History/InstallTimeLength');
	}
	public function get_run_count()
	{
		return ($times_run = $this->xml_parser->getXMLValue('PhoronixTestSuite/TestInstallation/History/TimesRun')) != false ? $times_run : 0;
	}
	public function get_compiler_data()
	{
		return json_decode($this->xml_parser->getXMLValue('PhoronixTestSuite/TestInstallation/Environment/CompilerData'), true);
	}
	public function get_install_footnote()
	{
		return json_decode($this->xml_parser->getXMLValue('PhoronixTestSuite/TestInstallation/Environment/InstallFootnote'), true);
	}
	public function get_installed_checksum()
	{
		return $this->xml_parser->getXMLValue('PhoronixTestSuite/TestInstallation/Environment/CheckSum');
	}
	public function get_installed_system_identifier()
	{
		return $this->xml_parser->getXMLValue('PhoronixTestSuite/TestInstallation/Environment/SystemIdentifier');
	}
}

?>
