<?php


//
// SYSTEM RELATED
//

function cpu_core_count()
{
	if(is_file("/proc/cpuinfo"))
	{
		$info = file_get_contents("/proc/cpuinfo");
		$info = substr($info, strrpos($info, "\nprocessor"));
		$info = trim(substr($info, strpos($info, ":") + 1, strpos($info, "\n") - strpos($info, ":")));
	}
	else
		$info = 0;

	return intval($info) + 1;
}
function cpu_job_count()
{
	return cpu_core_count() + 1;
}

function processor_string()
{
	//TODO: Support Multiple CPUs

	if(is_file("/proc/cpuinfo"))
	{
		$info = file_get_contents("/proc/cpuinfo");
		$info = substr($info, strpos($info, "model name"));
		$info = trim(substr($info, strpos($info, ":") + 1, strpos($info, "\n") - strpos($info, ":")));
		$info = str_replace(array("Corporation ", "Technologies ", "(R)", "(TM)"), "", $info);
	}
	else
		$info = "Unknown";

	return $info;
}
function memory_mb_capacity()
{
	if(is_file("/proc/meminfo"))
	{
		$info = file_get_contents("/proc/meminfo");
		$info = substr($info, strpos($info, "MemTotal:") + 9);
		$info = intval(trim(substr($info, 0, strpos($info, "kB"))));
		$info = floor($info / 1024);
	}
	else
		$info = "Unknown";

	return $info;
}
function current_screen_resolution()
{
	$info = shell_exec("xrandr");

	if(($pos = strrpos($info, "*")) == FALSE)
	{
		$info = array("Unknown", "Unknown");
	}
	else
	{
		$info = substr($info, 0, $pos);
		$info = trim(substr($info, strrpos($info, "\n")));
		$info = substr($info, 0, strpos($info, " "));
		$info = explode("x", $info);
	}

	return $info;
}
function current_screen_width()
{
	$resolution = current_screen_resolution();
	return $resolution[0];
}
function current_screen_height()
{
	$resolution = current_screen_resolution();
	return $resolution[1];
}
function parse_lsb_output($desc)
{
	if(is_file("/etc/lsb-release"))
	{
		$info = file_get_contents("/etc/lsb-release");

		if(($pos = strpos($info, $desc)) == FALSE)
			$info = "Unknown";
		else
		{
			$info = substr($info, $pos + strlen($desc));
			$info = str_replace(array("\"", " " ), "", trim(substr($info, 0, strpos($info, "\n"))));
		}
	}
	else
		$info = "Unknown";

	return $info;
}
function os_vendor()
{
	return parse_lsb_output("DISTRIB_ID=");
}
function os_version()
{
	return parse_lsb_output("DISTRIB_RELEASE=");
}
function kernel_string()
{
	return trim(shell_exec("uname -r"));
}
function kernel_arch()
{
	return trim(shell_exec("uname -m"));
}
function graphics_processor_string()
{
	return parse_lspci_output("VGA compatible controller:");
}
function motherboard_chipset_string()
{
	return parse_lspci_output("Host bridge:");
}
function parse_lspci_output($desc)
{
	$info = shell_exec("lspci");

	if(($pos = strpos($info, $desc)) === FALSE)
	{
		$info = "Unknown";
	}
	else
	{
		$info = substr($info, $pos + strlen($desc));
		$EOL = strpos($info, "\n");

		if(($temp = strpos($info, '/')) < $EOL && $temp > 0)
			if(($temp = strpos($info, ' ', ($temp + 2))) < $EOL && $temp > 0)
				$EOL = $temp;

		if(($temp = strpos($info, '(')) < $EOL && $temp > 0)
			$EOL = $temp;

		if(($temp = strpos($info, '[')) < $EOL && $temp > 0)
			$EOL = $temp;

		$info = trim(substr($info, 0, $EOL));

		if(($strlen = strlen($info)) < 6 || $strlen > 96)
			$info = "N/A";
		else
			$info = str_replace(array("Corporation ", "Technologies ", " Inc ", "processor ", "(R)", "(TM)"), "", $info);
	}

	return $info;
}
function graphics_subsystem_version()
{
	$info = shell_exec("X -version 2>&1");

	if(($pos = strrpos($info, "Release Date")) === FALSE)
	{
		$info = "Unknown";
	}
	else
	{
		$info = trim(substr($info, 0, $pos));
		$info = trim(substr($info, strrpos($info, " ")));
	}

	return $info;
}
function compiler_version()
{
	$info = shell_exec("gcc -v 2>&1");

	if(($pos = strpos($info, "gcc version")) === FALSE)
	{
		$info = "N/A";
	}
	else
	{
		$info = substr($info, $pos + 11);
		$info = trim(substr($info, 0, strpos($info, " ", strpos($info, "."))));
		$info = "GCC " . $info;
	}

	return $info;
}

?>
