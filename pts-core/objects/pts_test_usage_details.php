<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel

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

class pts_test_usage_details
{
	private $identifier;
	private $install_time;
	private $last_run_time;
	private $installed_version;
	private $average_run_time;
	private $times_run;

	public function __construct($identifier)
	{
		$xml_parser = new pts_installed_test_tandem_XmlReader($identifier);
		$this->identifier = $identifier;
		$this->install_time = substr($xml_parser->getXMLValue(P_INSTALL_TEST_INSTALLTIME), 0, 10);
		$this->last_run_time = substr($xml_parser->getXMLValue(P_INSTALL_TEST_LASTRUNTIME), 0, 10);
		$this->installed_version = $xml_parser->getXMLValue(P_INSTALL_TEST_VERSION);
		$this->average_run_time = pts_format_time_string($xml_parser->getXMLValue(P_INSTALL_TEST_AVG_RUNTIME), "SECONDS", false);
		$this->times_run = $xml_parser->getXMLValue(P_INSTALL_TEST_TIMESRUN);

		if($this->last_run_time == "0000-00-00" || empty($this->times_run))
		{
			$this->last_run_time = "NEVER";
			$this->times_run = "";
		}

		if(empty($this->times_run))
		{
			$this->times_run = 0;
		}
		if(empty($this->average_run_time))
		{
			$this->average_run_time = "N/A";
		}
	}
	public function __toString()
	{
		$str = "";

		if(!empty($this->installed_version))
		{
			$str = sprintf("%-18ls - %-8ls %-13ls %-11ls %-13ls %-10ls\n", $this->identifier, $this->installed_version, $this->install_time, $this->last_run_time, $this->average_run_time, $this->times_run);
		}

		return $str;
	}
}

?>
