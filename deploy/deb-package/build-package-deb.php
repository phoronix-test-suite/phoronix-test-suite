<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2018, Phoronix Media
	Copyright (C) 2008 - 2018, Michael Larabel

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

if(!is_executable("phoronix-test-suite") || !is_dir("pts-core/"))
{
	echo "\nYou must run this script from the root directory of the phoronix-test-suite/ folder!\n";
	echo "Example: php deploy/deb-package/build-package-deb.php\n";
	exit(0);
}
if(!is_executable('/usr/bin/dpkg'))
{
	echo PHP_EOL . "dpkg must be present on the system to generate the phoronix-test-suite Debian package." . PHP_EOL . PHP_EOL;
	exit;
}
if(!is_executable('/usr/bin/fakeroot'))
{
	echo PHP_EOL . "fakeroot must be present on the system to generate the phoronix-test-suite Debian package." . PHP_EOL . PHP_EOL;
	exit;
}

@require("pts-core/pts-core.php");

if(!defined("PTS_VERSION"))
{
	echo "\nERROR: The Phoronix Test Suite version wasn't found!\n";
	exit(0);
}

shell_exec("rm -rf /tmp/pts-deb-builder/");
shell_exec("mkdir -p /tmp/pts-deb-builder/DEBIAN/");
shell_exec("mkdir -p /tmp/pts-deb-builder/usr/");
shell_exec("./install-sh /tmp/pts-deb-builder/usr");

$pts_version = str_replace("a", "~a", str_replace("b", "~b", PTS_VERSION)); // Fix version

$phoronix_test_suite_bin = file_get_contents("phoronix-test-suite");
$phoronix_test_suite_bin = str_replace("#export PTS_DIR=`pwd`", "export PTS_DIR='/usr/share/phoronix-test-suite/'", $phoronix_test_suite_bin);
file_put_contents("/tmp/pts-deb-builder/usr/bin/phoronix-test-suite", $phoronix_test_suite_bin);
shell_exec("chmod +x /tmp/pts-deb-builder/usr/bin/phoronix-test-suite");

$control_file = "Package: phoronix-test-suite\n";
$control_file .= "Version: " . $pts_version . "\n";
$control_file .= "Section: Utilities\n";
$control_file .= "Installed-Size: " . shell_exec("cd /tmp/pts-deb-builder/; du -s | cut -f 1");
$control_file .= "Priority: optional\n";
$control_file .= "Architecture: all\n";
$control_file .= "Depends: php-cli|php5-cli,php5-cli|php-xml\n";
$control_file .= "Recommends: build-essential, php-gd|php5-gd\n";
$control_file .= "Maintainer: Phoronix Media <trondheim-pts@phoronix-test-suite.com>\n";
$control_file .= "Description: An Automated, Open-Source Testing Framework\n " . @str_replace("\n", " ", file_get_contents('pts-core/static/short-description.txt')) . "\n";
$control_file .= "Homepage: http://www.phoronix-test-suite.com/ \n";
file_put_contents("/tmp/pts-deb-builder/DEBIAN/control", $control_file);

shell_exec("fakeroot dpkg --build /tmp/pts-deb-builder ../phoronix-test-suite_" . $pts_version . "_all.deb");
shell_exec("rm -rf /tmp/pts-deb-builder");

?>
