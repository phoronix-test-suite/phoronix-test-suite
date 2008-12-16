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

class install_test implements pts_option_interface
{
	public static function run($items_to_install)
	{
		pts_load_function_set("install");

		if(count($items_to_install) == 0)
		{
			echo "\nThe test, suite name, or saved identifier must be supplied.\n";
		}
		else
		{
			if(IS_SCTP_MODE)
			{
				$items_to_install[0] = basename($items_to_install[0]);
			}

			$items_to_install = array_unique(array_map("strtolower", $items_to_install));

			if(pts_read_assignment("COMMAND") == "force-install")
			{
				pts_set_assignment("PTS_FORCE_INSTALL", 1);
			}

			foreach($items_to_install as $this_install)
			{
				if(strpos($this_install, "pcqs-") !== false && !is_file(XML_SUITE_LOCAL_DIR . "pcqs-license.txt"))
				{
					// Install the Phoronix Certification & Qualification Suite
					$agreement = wordwrap(file_get_contents("http://www.phoronix-test-suite.com/pcqs/pcqs-license.txt"), 65);

					if(strpos($agreement, "PCQS") == false)
					{
						echo pts_string_header("An error occurred while connecting to the Phoronix Test Suite server. Try again later.");
						return false;
					}

					echo "\n\n" . $agreement;
					$agree = pts_bool_question("Do you agree to these terms in full and wish to proceed (y/n)?", false);

					if($agree)
					{
						pts_download("http://www.phoronix-test-suite.com/pcqs/download-pcqs.php", XML_SUITE_LOCAL_DIR . "pcqs-suite.tar");
						pts_extract_file(XML_SUITE_LOCAL_DIR . "pcqs-suite.tar", true);
						echo pts_string_header("The Phoronix Certification & Qualification Suite is now installed.");
						break;
					}
					else
					{
						pts_string_header("In order to run PCQS you must agree to the listed terms.");
						return false;
					}
				}
			}

			echo "\n";

			// Any external dependencies?
			pts_install_package_on_distribution($items_to_install);

			// Install tests
			pts_start_install($items_to_install);
		}
	}
}

?>
