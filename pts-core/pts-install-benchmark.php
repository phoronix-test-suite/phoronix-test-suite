<?php

/*
   Copyright (C) 2008, Michael Larabel.
   Copyright (C) 2008, Phoronix Media.

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

require("pts-core/functions/pts-functions.php");
require("pts-core/functions/pts-functions-install.php");

$TO_INSTALL = strtolower($argv[1]);

if(empty($TO_INSTALL))
{
	echo "\nThe benchmark or suite name to install must be supplied.\n";
	exit;
}

$install_objects = "";
pts_recurse_install_benchmark($TO_INSTALL, $install_objects);

?>
