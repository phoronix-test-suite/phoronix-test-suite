<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel
	pts-functions_pcqs.php: Functions for the Phoronix Certification & Qualification Suite

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

function pts_pcqs_is_installed()
{
	return is_file(XML_SUITE_LOCAL_DIR . "pcqs-license.txt");
}
function pts_pcqs_user_license()
{
	return pts_network::http_get_contents("http://www.phoronix-test-suite.com/pcqs/pcqs-license.txt");
}
function pts_pcqs_install_package()
{
	pts_network::download_file("http://www.phoronix-test-suite.com/pcqs/download-pcqs.php", XML_SUITE_LOCAL_DIR . "pcqs-suite.tar");
	pts_compression::archive_extract(XML_SUITE_LOCAL_DIR . "pcqs-suite.tar");
	pts_remove(XML_SUITE_LOCAL_DIR . "pcqs-suite.tar");
	echo pts_string_header("The Phoronix Certification & Qualification Suite is now installed.");
}

?>
