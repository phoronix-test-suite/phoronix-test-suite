<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2017, Phoronix Media
	Copyright (C) 2008 - 2017, Michael Larabel

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
	private $xml;
	private $footnote_override = null;
	private $install_path = null;

	public function __construct(&$test_profile)
	{
		$this->install_path = $test_profile->get_install_dir();
		$read_xml = is_file($this->install_path . 'pts-install.xml') ? $this->install_path . 'pts-install.xml' : null;
		$xml_options = LIBXML_COMPACT | LIBXML_PARSEHUGE;
		$this->xml = simplexml_load_file($read_xml, 'SimpleXMLElement', $xml_options);
	}
	public function get_install_date_time()
	{
		return isset($this->xml->TestInstallation->History->InstallTime) ? $this->xml->TestInstallation->History->InstallTime->__toString() : null;
	}
	public function get_install_date()
	{
		return substr($this->get_install_date_time(), 0, 10);
	}
	public function get_last_run_date_time()
	{
		return isset($this->xml->TestInstallation->History->LastRunTime) ? $this->xml->TestInstallation->History->LastRunTime->__toString() : null;
	}
	public function get_last_run_date()
	{
		return substr($this->get_install_date_time(), 0, 10);
	}
	public function get_installed_version()
	{
		return isset($this->xml->TestInstallation->Environment->Version) ? $this->xml->TestInstallation->Environment->Version->__toString() : null;
	}
	public function get_average_run_time()
	{
		return isset($this->xml->TestInstallation->History->AverageRunTime) ? $this->xml->TestInstallation->History->AverageRunTime->__toString() : null;
	}
	public function get_latest_run_time()
	{
		return isset($this->xml->TestInstallation->History->LatestRunTime) ? $this->xml->TestInstallation->History->LatestRunTime->__toString() : null;
	}
	public function get_latest_install_time()
	{
		return isset($this->xml->TestInstallation->History->InstallTimeLength) ? $this->xml->TestInstallation->History->InstallTimeLength->__toString() : null;
	}
	public function get_run_count()
	{
		return isset($this->xml->TestInstallation->History->TimesRun) ? $this->xml->TestInstallation->History->TimesRun->__toString() : 0;
	}
	public function get_compiler_data()
	{
		return isset($this->xml->TestInstallation->Environment->CompilerData) ? json_decode($this->xml->TestInstallation->Environment->CompilerData->__toString(), true) : null;
	}
	public function get_install_footnote()
	{
		return !empty($this->footnote_override) ? $this->footnote_override : (isset($this->xml->TestInstallation->Environment->InstallFootnote) ? $this->xml->TestInstallation->Environment->InstallFootnote->__toString() : null);
	}
	public function set_install_footnote($f = null)
	{
		return $this->footnote_override = $f;
	}
	public function get_installed_checksum()
	{
		return isset($this->xml->TestInstallation->Environment->CheckSum) ? $this->xml->TestInstallation->Environment->CheckSum->__toString() : null;
	}
	public function get_installed_system_identifier()
	{
		return isset($this->xml->TestInstallation->Environment->SystemIdentifier) ? $this->xml->TestInstallation->Environment->SystemIdentifier->__toString() : null;
	}
	public function get_install_size()
	{
		$install_size = 0;

		if(pts_client::executable_in_path('du'))
		{
			$du = trim(shell_exec('du -sk ' . $this->install_path . ' 2>&1'));
			$du = substr($du, 0, strpos($du, "\t"));
			if(is_numeric($du) && $du > 1)
			{
				$install_size = $du;
			}
		}

		return $install_size;
	}
}

?>
