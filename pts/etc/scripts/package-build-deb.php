<?php

if(!is_file("phoronix-test-suite") || !is_dir("pts/") || !is_dir("pts-core/"))
{
	echo "\nYou must run this script from the root directory of the phoronix-test-suite/ folder!\n";
	echo "Example: php5 pts/etc/scripts/package-build-deb.php\n";
	exit(0);
}
@require("pts-core/functions/pts.php");

if(!defined("PTS_VERSION"))
{
	echo "\nERROR: The Phoronix Test Suite version wasn't found!\n";
	exit(0);
}

shell_exec("rm -rf /tmp/pts-deb-builder/");
shell_exec("mkdir /tmp/pts-deb-builder/");
shell_exec("mkdir -p /tmp/pts-deb-builder/DEBIAN/");
shell_exec("mkdir -p /tmp/pts-deb-builder/usr/bin/");
shell_exec("mkdir -p /tmp/pts-deb-builder/usr/share/doc/phoronix-test-suite/");
shell_exec("mkdir -p /tmp/pts-deb-builder/usr/share/phoronix-test-suite/");

shell_exec("cp -va CHANGE-LOG /tmp/pts-deb-builder/usr/share/doc/phoronix-test-suite/");
shell_exec("cp -va README /tmp/pts-deb-builder/usr/share/doc/phoronix-test-suite/");
shell_exec("cp -va COPYING /tmp/pts-deb-builder/usr/share/doc/phoronix-test-suite/");

shell_exec("cp -va LICENSE /tmp/pts-deb-builder/usr/share/phoronix-test-suite/");
shell_exec("cp -va pts/ /tmp/pts-deb-builder/usr/share/phoronix-test-suite/");
shell_exec("rm -f /tmp/pts-deb-builder/usr/share/phoronix-test-suite/pts/etc/scripts/package-build-*");
shell_exec("cp -va pts-core/ /tmp/pts-deb-builder/usr/share/phoronix-test-suite/");

$phoronix_test_suite_bin = file_get_contents("phoronix-test-suite");
$phoronix_test_suite_bin = str_replace("export PTS_DIR=`pwd`", "export PTS_DIR='/usr/share/phoronix-test-suite/'", $phoronix_test_suite_bin);
file_put_contents("/tmp/pts-deb-builder/usr/bin/phoronix-test-suite", $phoronix_test_suite_bin);
shell_exec("chmod +x /tmp/pts-deb-builder/usr/bin/phoronix-test-suite");

$control_file = "Package: phoronix-test-suite\n";
$control_file .= "Version: " . PTS_VERSION . "\n";
$control_file .= "Section: Utilities\n";
$control_file .= "Priority: optional\n";
$control_file .= "Architecture: all\n";
$control_file .= "Depends: php5-cli, php5-gd\n";
$control_file .= "Recommends: build-essential\n";
$control_file .= "Maintainer: Phoronix Media <trondheim-pts@phoronix-test-suite.com>\n";
$control_file .= "Description: The Phoronix Test Suite is the most comprehensive testing and benchmarking platform available for Linux and is designed to carry out qualitative and quantitative benchmarks in a clean, reproducible, and easy-to-use manner.\n";
file_put_contents("/tmp/pts-deb-builder/DEBIAN/control", $control_file);

shell_exec("dpkg --build /tmp/pts-deb-builder phoronix-test-suite_" . PTS_VERSION . "_all.deb");
shell_exec("rm -rf /tmp/pts-deb-builder");

?>
