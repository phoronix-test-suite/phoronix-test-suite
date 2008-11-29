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

class install_test
{
	public static function run($r)
	{
		include_once("pts-core/functions/pts-functions-install.php");
		$test = $r[0];

		if(empty($test))
		{
			echo "\nThe test or suite name to install must be supplied.\n";
		}
		else
		{
			if(IS_SCTP_MODE)
			{
				$test = basename($test);
			}

			if(pts_read_assignment("COMMAND") == "force-install")
			{
				pts_set_assignment("PTS_FORCE_INSTALL", 1);
			}

			$test = strtolower($test);

			if(strpos($test, "pcqs") !== false && !is_file(XML_SUITE_LOCAL_DIR . "pcqs-license.txt"))
			{
				// Install the Phoronix Certification & Qualification Suite
				$agreement = wordwrap(file_get_contents("http://www.phoronix-test-suite.com/pcqs/pcqs-license.txt"), 65);

				if(strpos($agreement, "PCQS") == false)
				{
					pts_exit("An error occurred while connecting to the Phoronix Test Suite Server. Please try again later.");
				}

				echo "\n\n" . $agreement;
				$agree = pts_bool_question("Do you agree to these terms in full and wish to proceed (y/n)?", false);

				if($agree)
				{
					pts_download("http://www.phoronix-test-suite.com/pcqs/download-pcqs.php", XML_SUITE_LOCAL_DIR . "pcqs-suite.tar");
					pts_extract_file(XML_SUITE_LOCAL_DIR . "pcqs-suite.tar", true);
					echo pts_string_header("The Phoronix Certification & Qualification Suite is now installed.");
				}
				else
				{
					pts_exit(pts_string_header("In order to run PCQS you must agree to the listed terms."));
				}
			}

			// Any external dependencies?
			echo "\n";
			pts_install_package_on_distribution($test);

			// Install tests
			pts_start_install($test);
		}
	}
}

?>
