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

class pts_openbenchmarking
{
	public static function stats_hardware_list()
	{
		return array(
			"cpu" => array("cpu", "model"),
			"cpu_count" => array("cpu", "core-count"),
			"cpu_speed" => array("cpu", "mhz-default-frequency"),
			"chipset" => array("chipset"),
			"motherboard" => array("motherboard"),
			"gpu" => array("gpu", "model")
			);
	}
	public static function stats_software_list()
	{
		return array(
			"os" => array("system", "operating-system"),
			"os_architecture" => array("system", "kernel-architecture"),
			"display_server" => array("system", "display-server"),
			"display_driver" => array("system", "display-driver-string"),
			"desktop" => array("system", "desktop-environment"),
			"compiler" => array("system", "compiler"),
			"file_system" => array("system", "filesystem"),
			"screen_resolution" => array("gpu", "screen-resolution-string")
			);
	}

}

?>
