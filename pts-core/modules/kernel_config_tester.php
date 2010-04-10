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

class bisect extends pts_module_interface
{
	const module_name = "Kernel Configuration Tester";
	const module_version = "0.1.0";
	const module_description = "This module finds your ideal kernel configuration values for your desired workload.";
	const module_author = "Michael Larabel";

	/*
		NOTES:
			- This module currently requires you are using Ubuntu and have everything setup correctly
			- The build process mirrors this approach: https://help.ubuntu.com/community/Kernel/Compile#Alternate%20Build%20Method:%20The%20Old-Fashioned%20Debian%20Way
			- This module assumes you are running the phoronix-test-suite as sudo/root
			- You must be running a SUITE, either a custom one or a mainline one
			- At this time this module is more to show a proof of concept and is not officially supported unless for PTS Commercial clients
			- TODO: Add support for testing all kernel configuration combinations to find the real ideal configuration
	*/

	public static function module_setup()
	{
		return array(
		new pts_module_option("kernel_source_dir", "Enter the path to the local folder of your kernel", "LOCAL_DIRECTORY"),
		new pts_module_option("kernel_config_base", "Enter the path to your default kernel configuration", "LOCAL_FILE"),
		new pts_module_option("kernel_config_ini", "Enter the path to your kernel configuration INI test file", "LOCAL_FILE"),
		new pts_module_option("test_suite", "Enter the INSTALLED suite to run", "INSTALLED_SUITE"),
		new pts_module_option("test_save", "Enter the name for saving the results", "VALID_SAVE_NAME")
		);
	}
	public static function module_setup_validate($options)
	{
		if(pts_package_vendor_identifier() != "ubuntu")
		{
			echo "\nThis module is only supported on Ubuntu currently.\n";
			return array();
		}

		$options["kernel_source_dir"] = pts_add_trailing_slash($options["kernel_source_dir"]);

		if(!is_file($options["kernel_source_dir"] . "Kbuild"))
		{
			echo $options["kernel_source_dir"] . " is not a Linux kernel source directory!\n";
			return array();
		}

		return $options;
	}
	public static function user_commands()
	{
		return array("start" => "start_process", "recover" => "recover_process", "next" => "next_process");
	}

	//
	// User Run Command(s)
	//

	public static function start_process()
	{
		if(!pts_module::is_module_setup())
		{
			echo "\nYou first must run:\n\nphoronix-test-suite module-setup kernel-config-tester\n\n";
			return false;
		}

		$options = pts_module::read_all_options();
		$kernel_config_test_options = array();

		/*
			; Sample INI kernel_config_tester file
			[timer_frequency: 100HZ]
			CONFIG_HZ_100=y
			CONFIG_HZ=100

			[timer_frequency: 250HZ]
			CONFIG_HZ_250=y
			CONFIG_HZ=250

			[timer_frequency: 300HZ]
			CONFIG_HZ_300=y
			CONFIG_HZ=300

			[timer_frequency: 1000HZ]
			CONFIG_HZ_1000=y
			CONFIG_HZ=1000

			[cpu_freq: yes]
			CONFIG_CPU_FREQ=y

			[cpu_freq: no]
			CONFIG_CPU_FREQ=n

			[swap: yes]
			CONFIG_SWAP=y

			[swap: no]
			CONFIG_SWAP=n

			[scheduler: CFQ]
			CONFIG_DEFAULT_DEADLINE=n
			CONFIG_DEFAULT_CFQ=y
			CONFIG_DEFAULT_NOOP=n
			CONFIG_DEFAULT_IOSCHED="cfq"

			[scheduler: Deadline]
			CONFIG_DEFAULT_DEADLINE=y
			CONFIG_DEFAULT_CFQ=n
			CONFIG_DEFAULT_NOOP=n
			CONFIG_DEFAULT_IOSCHED="deadline"

			[scheduler: Noop]
			CONFIG_DEFAULT_DEADLINE=n
			CONFIG_DEFAULT_CFQ=n
			CONFIG_DEFAULT_NOOP=y
			CONFIG_DEFAULT_IOSCHED="noop"
		*/

		$kernel_config_ini = parse_ini_file($options["kernel_config_changes"], true, INI_SCANNER_RAW);

		foreach($kernel_config_ini as $test_title => $test_changes_r)
		{
			array_push($kernel_config_test_options, array($test_title, $test_changes_r));
		}

		if(count($kernel_config_test_options) == 0)
		{
			echo "\nNo kernel configuration options found to test.\n";
			return false;
		}

		$storage_object = new pts_storage_object();
		$storage_object->add_object("kernel_config_options", $kernel_config_test_options);
		$storage_object->add_object("config_pos", -1);
		$storage_object->save_to_file(pts_module::save_dir() . "data.pt2so");

		file_put_contents("/etc/xdg/autostart/pts-kernel-config-tester.desktop", "
[Desktop Entry]
Encoding=UTF-8
Name=Phoronix Test Suite - Kernel Config Tester
Comment=Phoronix Test Suite - Kernel Config Tester
Icon=phoronix-test-suite
Exec=gnome-terminal -e 'phoronix-test-suite kernel-config-tester.recover'
Terminal=false
Type=Application
Name[en_US]=pts-kernel-config-tester.desktop");

		self::setup_new_kernel();
	}
	public static function setup_new_kernel()
	{
		$storage_object = pts_storage_object::recover_from_file(pts_module::save_dir() . "data.pt2so");
		$options = pts_module::read_all_options();
		$kernel_config = file_get_contents($options["kernel_config_base"]);

		$current_pos = $storage_object->read_object("config_pos");

		if($current_pos != -1)
		{
			$config_options = $storage_object->read_object("kernel_config_options");

			foreach($config_options[$current_pos][1] as $replace_key => $replace_value)
			{
				$base_option_pos = strpos($kernel_config, "\n" . $replace_key . '=') + 1;
				$base_option = substr($kernel_config, $base_option_pos, strpos($kernel_config, "\n", $base_option_pos) - $base_option_pos);

				if(isset($replace_value[1]) && !is_numeric($replace_value))
				{
					// Not a number and not a y/n/m char
					$replace_value = "\"$replace_value\"";
				}

				$kernel_config = str_replace($base_option, $replace_key . '=' . $replace_value, $kernel_config);
			}
		}

		file_put_contents($options["kernel_source_dir"] . ".config", $kernel_config);

		echo shell_exec("cd " . $options["kernel_source_dir"] . " && make-kpkg clean && make oldconfig && CONCURRENCY_LEVEL=" . (phodevi::read_property("cpu", "core-count") * 2) . " fakeroot make-kpkg --initrd --append-to-version=-ptskct kernel-image kernel-headers && dpkg -i ../linux-*.deb && reboot");
	}
	public static function recover_process()
	{
		$storage_object = pts_storage_object::recover_from_file(pts_module::save_dir() . "data.pt2so");
		$options = pts_module::read_all_options();

		$current_pos = $storage_object->read_object("config_pos");
		$config_options = $storage_object->read_object("kernel_config_options");

		pts_run_option_next("run_test", $options["test_suite"], array("AUTOMATED_MODE" => true, "AUTO_SAVE_NAME" => $options["test_save"], "AUTO_TEST_RESULTS_IDENTIFIER" => ($current_pos == -1 ? "Base" : $config_options[$current_pos][0]), "KCT_POS" => $current_pos, "KCT_COUNT" => count($config_options)));
		pts_run_option_next("kernel-config-tester.next");
	}
	public static function next_process()
	{
		// Process results and move on to next testing

		if((pts_read_assignment("KCT_POS") + 1) < pts_read_assignment("KCT_COUNT"))
		{
			pts_storage_object::set_in_file(pts_module::save_dir() . "data.pt2so", "config_pos", pts_read_assignment("KCT_POS") + 1);
			self::setup_new_kernel();
		}
		else
		{
			// Done testing
			pts_unlink("/etc/xdg/autostart/pts-kernel-config-tester.desktop");
			// Process results
		}
	}

	//
	// PTS Module API Hooks
	//

	public static function __post_test_run($test_result)
	{
		//$result = $test_result->get_result();
	}
}

?>
