<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2011, Phoronix Media
	Copyright (C) 2008 - 2011, Michael Larabel
	phodevi_system.php: The PTS Device Interface object for the system software

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

class phodevi_system extends phodevi_device_interface
{
	public static function read_property($identifier)
	{
		switch($identifier)
		{
			case 'username':
				$property = new phodevi_device_property('sw_username', phodevi::std_caching);
				break;
			case 'hostname':
				$property = new phodevi_device_property('sw_hostname', phodevi::smart_caching);
				break;
			case 'vendor-identifier':
				$property = new phodevi_device_property('sw_vendor_identifier', phodevi::smart_caching);
				break;
			case 'filesystem':
				$property = new phodevi_device_property('sw_filesystem', phodevi::no_caching);
				break;
			case 'virtualized-mode':
				$property = new phodevi_device_property('sw_virtualized_mode', phodevi::smart_caching);
				break;
			case 'java-version':
				$property = new phodevi_device_property('sw_java_version', phodevi::std_caching);
				break;
			case 'python-version':
				$property = new phodevi_device_property('sw_python_version', phodevi::std_caching);
				break;
			case 'wine-version':
				$property = new phodevi_device_property('sw_wine_version', phodevi::std_caching);
				break;
			case 'display-server':
				$property = new phodevi_device_property('sw_display_server', phodevi::smart_caching);
				break;
			case 'display-driver':
				$property = new phodevi_device_property(array('sw_display_driver', false), phodevi::smart_caching);
				break;
			case 'display-driver-string':
				$property = new phodevi_device_property(array('sw_display_driver', true), phodevi::smart_caching);
				break;
			case 'dri-display-driver':
				$property = new phodevi_device_property('sw_dri_display_driver', phodevi::smart_caching);
				break;
			case 'opengl-driver':
				$property = new phodevi_device_property('sw_opengl_driver', phodevi::std_caching);
				break;
			case 'opengl-vendor':
				$property = new phodevi_device_property('sw_opengl_vendor', phodevi::smart_caching);
				break;
			case 'desktop-environment':
				$property = new phodevi_device_property('sw_desktop_environment', phodevi::smart_caching);
				break;
			case 'operating-system':
				$property = new phodevi_device_property('sw_operating_system', phodevi::smart_caching);
				break;
			case 'os-version':
				$property = new phodevi_device_property('sw_os_version', phodevi::smart_caching);
				break;
			case 'kernel':
				$property = new phodevi_device_property('sw_kernel', phodevi::smart_caching);
				break;
			case 'kernel-architecture':
				$property = new phodevi_device_property('sw_kernel_architecture', phodevi::smart_caching);
				break;
			case 'kernel-string':
				$property = new phodevi_device_property('sw_kernel_string', phodevi::smart_caching);
				break;
			case 'compiler':
				$property = new phodevi_device_property('sw_compiler', phodevi::std_caching);
				break;
			case 'system-layer':
				$property = new phodevi_device_property('sw_system_layer', phodevi::std_caching);
				break;
		}

		return $property;
	}
	public static function sw_username()
	{
		// Gets the system user's name
		if(function_exists('posix_getpwuid') && function_exists('posix_getuid'))
		{
			$userinfo = posix_getpwuid(posix_getuid());
			$username = $userinfo['name'];
		}
		else
		{
			$username = trim(getenv('USERNAME'));
		}

		return $username;
	}
	public static function sw_system_layer()
	{
		$layer = null;

		if(phodevi::is_windows() && pts_client::executable_in_path('winecfg.exe') && ($wine = phodevi::read_property('system', 'wine-version')))
		{
			$layer = $wine;
		}
		else
		{
			// Report virtualization
			$layer = phodevi::read_property('system', 'virtualized-mode');
		}

		return $layer;
	}
	public static function sw_hostname()
	{
		$hostname = 'Unknown';

		if(($bin = pts_client::executable_in_path('hostname')))
		{
			$hostname = trim(shell_exec($bin . ' 2>&1'));
		}
		else if(phodevi::is_windows())
		{
			$hostname = getenv('USERDOMAIN');
		}

		return $hostname;
	}
	public static function sw_vendor_identifier()
	{
		// Returns the vendor identifier used with the External Dependencies and other distro-specific features
		$vendor = phodevi::is_linux() ? phodevi_linux_parser::read_lsb_distributor_id() : false;

		if(!$vendor)
		{
			$vendor = phodevi::read_property('system', 'operating-system');

			if(($spos = strpos($vendor, ' ')) > 1)
			{
				$vendor = substr($vendor, 0, $spos);
			}
		}

		return str_replace(array(' ', '/'), '', strtolower($vendor));
	}
	public static function sw_filesystem()
	{
		// Determine file-system type
		$fs = null;

		if(phodevi::is_macosx())
		{
			$fs = phodevi_osx_parser::read_osx_system_profiler('SPSerialATADataType', 'FileSystem');
		}
		else if(phodevi::is_bsd())
		{
			if(pts_client::executable_in_path('mount'))
			{
				$mount = shell_exec('mount 2>&1');
				
				if(($start = strpos($mount, 'on / (')) != false)
				{
					/*
					-bash-4.0$ mount
					ROOT on / (hammer, local)
					/dev/da0s1a on /boot (ufs, local)
					/pfs/@@-1:00001 on /var (null, local)
					/pfs/@@-1:00002 on /tmp (null, local)
					/pfs/@@-1:00003 on /usr (null, local)
					/pfs/@@-1:00004 on /home (null, local)
					/pfs/@@-1:00005 on /usr/obj (null, local)
					/pfs/@@-1:00006 on /var/crash (null, local)
					/pfs/@@-1:00007 on /var/tmp (null, local)
					procfs on /proc (procfs, local)
					*/

					// TODO: improve this in case there are other partitions, etc
					$fs = substr($mount, $start + 6);
					$fs = substr($fs, 0, strpos($fs, ','));
				}
			}
		}
		else if(phodevi::is_hurd())
		{
			// Very rudimentary Hurd filesystem detection support but works for at least a clean Debian GNU/Hurd EXT2 install
			if(pts_client::executable_in_path('mount'))
			{
				$mount = shell_exec('mount 2>&1');

				if(($start = strpos($mount, 'on / type')) != false)
				{
					$fs = substr($mount, $start + 10);
					$fs = substr($fs, 0, strpos($fs, ' '));

					if(substr($fs, -2) == 'fs')
					{
						$fs = substr($fs, 0, -2);
					}
				}
			}
		}
		else if(phodevi::is_linux() || phodevi::is_solaris())
		{
			$fs = trim(shell_exec('stat ' . pts_client::test_install_root_path() . ' -L -f -c %T 2> /dev/null'));

			switch($fs)
			{
				case 'ext2/ext3':
					if(is_readable('/proc/mounts'))
					{
						$fstab = file_get_contents('/proc/mounts');
						$fstab = str_replace('/boot ', 'IGNORE', $fstab);

						$using_ext2 = strpos($fstab, ' ext2') !== false;
						$using_ext3 = strpos($fstab, ' ext3') !== false;
						$using_ext4 = strpos($fstab, ' ext4') !== false;

						if(!$using_ext2 && !$using_ext3 && $using_ext4)
						{
							$fs = 'ext4';
						}
						else if(!$using_ext2 && !$using_ext4 && $using_ext3)
						{
							$fs = 'ext3';
						}
						else if(!$using_ext3 && !$using_ext4 && $using_ext2)
						{
							$fs = 'ext2';
						}
					}
					break;
				case 'Case-sensitive Journaled HFS+':
					$fs = 'HFS+';
					break;
				default:
					if(substr($fs, 0, 9) == 'UNKNOWN (')
					{
						$fs_block = substr($fs, 9, -1);
						$known_fs_blocks = array(
							'0x9123683e' => 'Btrfs',
							'0x2fc12fc1' => 'zfs', // KQ Infotech ZFS
							'0x482b' => 'HFS+',
							'0x65735546' => 'FUSE',
							'0x565a4653' => 'ReiserFS',
							'0x52345362' => 'Reiser4',
							'0x3434' => 'NILFS2',
							'0x5346414f' => 'OpenAFS',
							'0x47504653' => 'GPFS',
							'0x5941ff53' => 'YAFFS',
							'0x65735546' => 'SSHFS',
							'0xff534d42' => 'CIFS',
							'0x24051905' => 'UBIFS'
							);

						foreach($known_fs_blocks as $hex => $name)
						{
							if($fs_block == $hex)
							{
								$fs = $name;
								break;
							}
						}
					}
					break;
			}

			if(strpos($fs, 'UNKNOWN') !== false && is_readable('/proc/mounts'))
			{
				$mounts = file_get_contents('/proc/mounts');
				$fs_r = array();

				$fs_checks = array(
					'squashfs' => 'SquashFS',
					'aufs' => 'AuFS',
					'unionfs' => 'UnionFS'
					);

				foreach($fs_checks as $fs_module => $fs_name)
				{
					if(strpos($mounts, $fs_module) != false)
					{
						array_push($fs_r, $fs_name);
					}
				}

				if(count($fs_r) > 0)
				{
					$fs = implode(' + ', $fs_r);
				}
			}
		}
		else if(phodevi::is_windows())
		{
			return null;
		}

		if(empty($fs))
		{
			$fs = 'Unknown';
		}

		return $fs;
	}
	public static function sw_virtualized_mode()
	{
		// Reports if system is running virtualized
		$virtualized = null;
		$gpu = phodevi::read_name('gpu');

		if(strpos(phodevi::read_property('cpu', 'model'), 'QEMU') !== false || (is_readable('/sys/class/dmi/id/bios_vendor') && pts_file_io::file_get_contents('/sys/class/dmi/id/bios_vendor') == 'QEMU'))
		{
			$virtualized = 'QEMU';
		}
		else if(strpos($gpu, 'VMware') !== false || (is_readable('/sys/class/dmi/id/product_name') && stripos(pts_file_io::file_get_contents('/sys/class/dmi/id/product_name'), 'VMware') !== false))
		{
			$virtualized = 'VMware';
		}
		else if(strpos($gpu, 'VirtualBox') !== false || strpos(phodevi::read_name('motherboard'), 'VirtualBox') !== false)
		{
			$virtualized = 'VirtualBox';
		}

		return $virtualized;
	}
	public static function sw_compiler()
	{
		// Returns version of the compiler (if present)
		$compilers = array();

		if($gcc = pts_client::executable_in_path('gcc'))
		{
			if(!is_link($gcc) || strpos(readlink($gcc), 'gcc') !== false)
			{
				// GCC
				// If it's a link, ensure that it's not linking to llvm/clang or something
				$compilers['gcc'] = 'GCC ' . trim(shell_exec('gcc -dumpversion 2>&1'));
			}
		}

		if(pts_client::executable_in_path('opencc'))
		{
			// Open64
			$compilers['opencc'] = 'Open64 ' . trim(shell_exec('opencc -dumpversion 2>&1'));
		}

		if(pts_client::executable_in_path('pathcc'))
		{
			// PathCC / EKOPath / PathScale Compiler Suite
			$compilers['pathcc'] = 'PathScale ' . trim(shell_exec('pathcc -dumpversion 2>&1'));
		}

		if(pts_client::executable_in_path('tcc'))
		{
			// TCC - Tiny C Compiler
			$tcc = explode(' ', trim(shell_exec('tcc -v 2>&1')));

			if($tcc[1] == 'version')
			{
				$compilers['opencc'] = 'TCC ' . $tcc[2];
			}
		}

		if(pts_client::executable_in_path('pcc'))
		{
			// PCC - Portable C Compiler
			$pcc = explode(' ', trim(shell_exec('pcc -version 2>&1')));

			if($pcc[0] == 'pcc')
			{
				$compilers['pcc'] = 'PCC ' . $pcc[1] . (is_numeric($pcc[2]) ? ' ' . $pcc[2] : null);
			}
		}

		if(pts_client::executable_in_path('clang'))
		{
			// Clang
			$compiler_info = 'Clang ' . trim(shell_exec('clang -dumpversion 2>&1'));
			$compilers['clang'] = trim($compiler_info);
		}

		if(pts_client::executable_in_path('llvmc'))
		{
			// LLVM - Low Level Virtual Machine (llvmc)
			$info = trim(shell_exec('llvmc -version 2>&1'));

			if(($s = strpos($info, 'version')) != false)
			{
				$info = substr($info, 0, strpos($info, "\n", $s));
				$info = substr($info, strrpos($info, "\n"));

				$compilers['llvmc'] = trim($info);
			}
		}

		if(pts_client::executable_in_path('suncc'))
		{
			// Sun Studio / SunCC
			$info = trim(shell_exec('suncc -V 2>&1'));

			if(($s = strpos($info, 'Sun C')) != false)
			{
				$info = substr($info, $s);
				$info = substr($info, 0, strpos($info, "\n"));

				$compilers['suncc'] = $info;
			}
		}

		// Try to make the compiler that's used by default to appear first
		if(pts_client::read_env('CC') && isset($compilers[basename(pts_client::read_env('CC'))]))
		{
			$cc_env = basename(pts_client::read_env('CC'));
			$default_compiler = $compilers[$cc_env];
			unset($compilers[$cc_env]);
			array_unshift($compilers, $default_compiler);
		}
		else if(pts_client::executable_in_path('cc') && is_link(pts_client::executable_in_path('cc')))
		{
			$cc_link = basename(readlink(pts_client::executable_in_path('cc')));

			if(isset($compilers[$cc_link]))
			{
				$default_compiler = $compilers[$cc_link];
				unset($compilers[pts_client::read_env('CC')]);
				array_unshift($compilers, $default_compiler);
			}
		}

		return implode(' + ', array_unique($compilers));
	}
	public static function sw_kernel_string()
	{
		return phodevi::read_property('system', 'kernel') . ' (' . phodevi::read_property('system', 'kernel-architecture') . ')';
	}
	public static function sw_kernel()
	{
		return php_uname('r');
	}
	public static function sw_kernel_architecture()
	{
		// Find out the kernel archiecture
		if(phodevi::is_windows())
		{
			//$kernel_arch = strpos($_SERVER['PROCESSOR_ARCHITECTURE'], 64) !== false || strpos($_SERVER['PROCESSOR_ARCHITEW6432'], 64 != false) ? 'x86_64' : 'i686';
			$kernel_arch = $_SERVER['PROCESSOR_ARCHITEW6432'] == 'AMD64' ? 'x86_64' : 'i686';
		}
		else
		{
			$kernel_arch = php_uname('m');

			switch($kernel_arch)
			{
				case 'X86-64':
				case 'amd64':
					$kernel_arch = 'x86_64';
					break;
				case 'i86pc':
				case 'i586':
				case 'i686-AT386':
					$kernel_arch = 'i686';
					break;
			}
		}

		return $kernel_arch;
	}
	public static function sw_os_version()
	{
		// Returns OS version
		if(phodevi::is_macosx())
		{
			$os = phodevi_osx_parser::read_osx_system_profiler('SPSoftwareDataType', 'SystemVersion');
		
			$start_pos = strpos($os, '.');
			$end_pos = strrpos($os, '.');
			$start_pos = strrpos(substr($os, 0, $start_pos), ' ');
			$end_pos = strpos($os, ' ', $end_pos);
		
			$os_version = substr($os, $start_pos + 1, $end_pos - $start_pos);
		}
		else if(phodevi::is_linux())
		{
			$os_version = phodevi_linux_parser::read_lsb('Release');
		}
		else
		{
			$os_version = php_uname('r');
		}
	
		return $os_version;
	}
	public static function sw_operating_system()
	{
		// Determine the operating system release
		if(phodevi::is_linux())
		{
			$vendor = phodevi_linux_parser::read_lsb_distributor_id();
		}
		else if(phodevi::is_hurd())
		{
			$vendor = php_uname('v');
		}
		else
		{
			$vendor = null;
		}

		$version = phodevi::read_property('system', 'os-version');

		if(!$vendor)
		{
			$os = null;

			// Try to detect distro for those not supplying lsb_release
			$files = pts_file_io::glob('/etc/*-version');
			for($i = 0; $i < count($files) && $os == null; $i++)
			{
				$file = file_get_contents($files[$i]);

				if(trim($file) != null)
				{
					$os = substr($file, 0, strpos($file, "\n"));
				}
			}
		
			if($os == null)
			{
				$files = pts_file_io::glob('/etc/*-release');
				for($i = 0; $i < count($files) && $os == null; $i++)
				{
					$file = file_get_contents($files[$i]);

					if(trim($file) != null)
					{
						$proposed_os = substr($file, 0, strpos($file, "\n"));

						if(strpos($proposed_os, '=') == false)
						{
							$os = $proposed_os;
						}
					}
					else if($i == (count($files) - 1))
					{
						$os = ucwords(substr(($n = basename($files[$i])), 0, strpos($n, '-')));
					}			
				}
			}

			if($os == null && is_file('/etc/release'))
			{
				$file = file_get_contents('/etc/release');
				$os = substr($file, 0, strpos($file, "\n"));
			}

			if($os == null && is_file('/etc/palm-build-info'))
			{
				// Palm / webOS Support
				$os = phodevi_parser::parse_equal_delimited_file('/etc/palm-build-info', 'PRODUCT_VERSION_STRING');
			}

			if($os == null)
			{
				if(phodevi::is_windows())
				{
					$os = trim(exec('ver'));
				}
				else
				{
					$os = php_uname('s');
				}
			}
		}
		else
		{
			$os = $vendor . ' ' . $version;
		}

		if(($break_point = strpos($os, ':')) > 0)
		{
			$os = substr($os, $break_point + 1);
		}
		
		if(phodevi::is_macosx())
		{
			$os = phodevi_osx_parser::read_osx_system_profiler('SPSoftwareDataType', 'SystemVersion');
		}

		$os = trim($os);

		return $os;
	}
	public static function sw_desktop_environment()
	{
		$desktop = null;
		$desktop_environment = null;
		$desktop_version = null;

		if(pts_client::is_process_running('gnome-panel'))
		{
			// GNOME
			$desktop_environment = 'GNOME';

			if(pts_client::executable_in_path('gnome-about'))
			{
				$desktop_version = pts_strings::last_in_string(trim(shell_exec('gnome-about --version 2> /dev/null')));
			}
		}
		else if(pts_client::is_process_running('gnome-shell'))
		{
			// GNOME 3.0 / GNOME Shell
			$desktop_environment = 'GNOME Shell';

			if(pts_client::executable_in_path('gnome-shell'))
			{
				$desktop_version = pts_strings::last_in_string(trim(shell_exec('gnome-shell --version 2> /dev/null')));
			}
		}
		else if(pts_client::is_process_running('unity-panel-service'))
		{
			// Canonical / Ubuntu Unity Desktop
			$desktop_environment = 'Unity';

			if(pts_client::executable_in_path('unity'))
			{
				$desktop_version = pts_strings::last_in_string(trim(shell_exec('unity --version 2> /dev/null')));
			}
		}
		else if(pts_client::is_process_running('unity-2d-panel'))
		{
			// Canonical / Ubuntu Unity 2D Desktop
			$desktop_environment = 'Unity 2D';

			if(pts_client::executable_in_path('unity'))
			{
				$desktop_version = pts_strings::last_in_string(trim(shell_exec('unity --version 2> /dev/null')));
			}
		}
		else if(($kde4 = pts_client::is_process_running('kded4')) || pts_client::is_process_running('kded'))
		{
			// KDE 4.x
			$desktop_environment = 'KDE';
			$kde_output = trim(shell_exec(($kde4 ? 'kde4-config' : 'kde-config') . ' --version 2>&1'));
			$kde_lines = explode("\n", $kde_output);

			for($i = 0; $i < count($kde_lines) && empty($desktop_version); $i++)
			{
				$line_segments = pts_strings::colon_explode($kde_lines[$i]);

				if(in_array($line_segments[0], array('KDE', 'KDE Development Platform')) && isset($line_segments[1]))
				{
					$v = trim($line_segments[1]);

					if(($cut = strpos($v, ' ')) > 0)
					{
						$v = substr($v, 0, $cut);
					}

					$desktop_version = $v;
				}
			}
		}
		else if(pts_client::is_process_running('chromeos-wm'))
		{
			$chrome_output = trim(shell_exec('chromeos-wm -version'));

			if($chrome_output == 'chromeos-wm')
			{
				// No version actually reported
				$chrome_output = 'Chrome OS';
			}

			$desktop_environment = $chrome_output;
		}
		else if(pts_client::is_process_running('lxsession'))
		{
			$lx_output = trim(shell_exec('lxpanel --version'));
			$version = substr($lx_output, strpos(' ', $lx_output) + 1);

			$desktop_environment = 'LXDE';

			if(strlen(pts_strings::keep_in_string($version, pts_strings::CHAR_NUMERIC | pts_strings::CHAR_DECIMAL)) == strlen($version))
			{
				$desktop_version = $version;
			}
		}
		else if(pts_client::is_process_running('xfce4-session') || pts_client::is_process_running('xfce-mcs-manager'))
		{
			// Xfce 4.x
			$desktop_environment = 'Xfce';
			$xfce_output = trim(shell_exec('xfce4-session-settings --version 2>&1'));

			if(($open = strpos($xfce_output, '(')) > 0)
			{
				$xfce_output = substr($xfce_output, strpos($xfce_output, ' ', $open) + 1);
				$desktop_version = substr($xfce_output, 0, strpos($xfce_output, ')'));
			}
		}

		if(!empty($desktop_environment))
		{
			$desktop = $desktop_environment;
			$version_check = str_replace(array('.', 1, 2, 3, 4, 5, 6, 7, 8, 9, 0), null, $desktop_version);

			if(!empty($desktop_version) && empty($version_check))
			{
				$desktop .= ' ' . $desktop_version;
			}
		}

		return $desktop;
	}
	public static function sw_display_server()
	{
		if(phodevi::is_windows())
		{
			// TODO: determine what to do for Windows support
			$info = false;
		}
		else
		{
			if(!(($x_bin = pts_client::executable_in_path('Xorg')) || ($x_bin = pts_client::executable_in_path('X'))))
			{
				return false;
			}

			// Find graphics subsystem version
			$info = shell_exec($x_bin . ' ' . (phodevi::is_solaris() ? ':0' : '') . ' -version 2>&1');
			$pos = (($p = strrpos($info, 'Release Date')) !== false ? $p : strrpos($info, 'Build Date'));
			$info = trim(substr($info, 0, $pos));

			if($pos === false || getenv('DISPLAY') == false)
			{
				$info = null;
			}
			else if(($pos = strrpos($info, '(')) === false)
			{
				$info = trim(substr($info, strrpos($info, ' ')));
			}
			else
			{
				$info = trim(substr($info, strrpos($info, 'Server') + 6));
			}

			if($info != null)
			{
				$info = 'X Server ' . $info;
			}
		}

		return $info;
	}
	public static function sw_display_driver($with_version = true)
	{
		if(phodevi::is_windows())
		{
			return null;
		}

		$display_driver = phodevi::read_property('system', 'dri-display-driver');

		if(empty($display_driver))
		{
			if(phodevi::is_ati_graphics() && phodevi::is_linux())
			{
				$display_driver = 'fglrx';
			}
			else if(phodevi::is_nvidia_graphics())
			{
				$display_driver = 'nvidia';
			}
			else if((phodevi::is_mesa_graphics() || phodevi::is_bsd()) && stripos(phodevi::read_property('gpu', 'model'), 'NVIDIA') !== false)
			{
				if(is_file('/sys/class/drm/version'))
				{
					// If there's DRM loaded and NVIDIA, it should be Nouveau
					$display_driver = 'nouveau';
				}
				else
				{
					// The dead xf86-video-nv doesn't use any DRM
					$display_driver = 'nv';
				}
			}
			else
			{
				// Fallback to hopefully detect the module, takes the first word off the GPU string and sees if it is the module
				// This works in at least the case of the Cirrus driver
				$display_driver = strtolower(pts_strings::first_in_string(phodevi::read_property('gpu', 'model')));
			}
		}

		if(!empty($display_driver))
		{
			$driver_version = phodevi_parser::read_xorg_module_version($display_driver . '_drv');

			if($driver_version == false || $driver_version == '1.0.0')
			{
				switch($display_driver)
				{
					case 'radeon':
						// RadeonHD driver also reports DRI driver as 'radeon', so try reading that instead
						$driver_version = phodevi_parser::read_xorg_module_version('radeonhd_drv');

						if($driver_version != false)
						{
							$display_driver = 'radeonhd';
						}
						break;
					case 'nvidia':
						// NVIDIA's binary driver usually ends up reporting 1.0.0
						if(($nvs_value = phodevi_parser::read_nvidia_extension('NvidiaDriverVersion')))
						{
							$driver_version = $nvs_value;
						}
						else
						{
							// NVIDIA's binary driver appends their driver version on the end of the OpenGL version string
							$glxinfo = phodevi_parser::software_glxinfo_version();

							if(($pos = strpos($glxinfo, 'NVIDIA ')) != false)
							{
								$driver_version = substr($glxinfo, ($pos + 7));
							}
						}
						break;
				}
			}

			if($driver_version == false)
			{
				// See if the VESA driver is in use
				$vesa_version = phodevi_parser::read_xorg_module_version('vesa_drv');

				if($vesa_version)
				{
					$display_driver = 'vesa';
					$driver_version = $vesa_version;
				}
			}

			if(!empty($driver_version) && $with_version)
			{
				$display_driver .= ' ' . $driver_version;

				if(phodevi::is_ati_graphics() && strpos($display_driver, 'fglrx') !== false)
				{
					$catalyst_version = phodevi_linux_parser::read_amd_pcsdb('AMDPCSROOT/SYSTEM/LDC,Catalyst_Version');

					if($catalyst_version != null && $catalyst_version > 10.1 && $catalyst_version != 10.5)
					{
						// This option was introduced around Catalyst 10.5 but seems to not be updated properly until Catalyst 10.11/10.12
						$display_driver .= ' Catalyst ' . $catalyst_version . '';
					}
				}
			}
		}

		return $display_driver;
	}
	public static function sw_opengl_driver()
	{
		// OpenGL version
		$info = null;

		if(phodevi::is_windows())
		{
			$info = null; // TODO: Windows support
		}
		else if(pts_client::executable_in_path('nvidia-settings'))
		{
			$info = phodevi_parser::read_nvidia_extension('OpenGLVersion');
		}
		else if(pts_client::executable_in_path('glxinfo'))
		{
			$info = phodevi_parser::software_glxinfo_version();

			if(($pos = strpos($info, ' ')) != false && strpos($info, 'Mesa') === false)
			{
				$info = substr($info, 0, $pos);
			}

			$renderer = phodevi_parser::read_glx_renderer();

			if(($s = strpos($renderer, 'Gallium')) !== false)
			{
				$renderer = substr($renderer, $s);
				$renderer = substr($renderer, 0, strpos($renderer, ' ', strpos($renderer, '.')));
				$info .= ' ' . $renderer . '';
			}
		}

		return $info;
	}
	public static function sw_opengl_vendor()
	{
		// OpenGL version
		$info = null;

		if(pts_client::executable_in_path('glxinfo'))
		{
			$info = shell_exec('glxinfo 2>&1 | grep vendor');

			if(($pos = strpos($info, 'OpenGL vendor string:')) !== false)
			{
				$info = substr($info, $pos + 22);
				$info = trim(substr($info, 0, strpos($info, "\n")));
			}
		}
		else if(is_readable('/dev/nvidia0'))
		{
			$info = 'NVIDIA';
		}
		else if(is_readable('/sys/module/fglrx/initstate') && pts_file_io::file_get_contents('/sys/module/fglrx/initstate') == 'live')
		{
			$info = 'ATI';
		}
		else if(is_readable('/dev/dri/card0'))
		{
			$info = 'Mesa';
		}
		else if(phodevi::is_bsd() && phodevi_bsd_parser::read_sysctl('dev.nvidia.0.%driver'))
		{
			$info = 'NVIDIA';
		}

		return $info;
	}
	public static function sw_dri_display_driver()
	{
		$dri_driver = false;

		if(is_file('/proc/dri/0/name'))
		{
			$driver_info = file_get_contents('/proc/dri/0/name');
			$dri_driver = substr($driver_info, 0, strpos($driver_info, ' '));

			if(in_array($dri_driver, array('i915', 'i965')))
			{
				$dri_driver = 'intel';
			}
		}
		else if(is_file('/sys/class/drm/card0/device/vendor'))
		{
			$vendor_id = file_get_contents('/sys/class/drm/card0/device/vendor');

			switch($vendor_id)
			{
				case 0x1002:
					$dri_driver = 'radeon';
					break;
				case 0x8086:
					$dri_driver = 'intel';
					break;
				case 0x10de:
					// NVIDIA
					$dri_driver = 'nouveau';
					break;
			}
		}

		return $dri_driver;
	}
	public static function sw_java_version()
	{
		$java_version = trim(shell_exec('java -version 2>&1'));

		if(strpos($java_version, 'not found') == false && strpos($java_version, 'Java') !== FALSE)
		{
			$java_version = explode("\n", $java_version);

			if(($cut = count($java_version) - 2) > 0)
			{
				$v = $java_version[$cut];
			}
			else
			{
				$v = array_pop($java_version);
			}

			$java_version = trim($v);
		}
		else
		{
			$java_version = null;
		}

		return $java_version;
	}
	public static function sw_python_version()
	{
		$python_version = null;

		if(pts_client::executable_in_path('python') != false)
		{
			$python_version = trim(shell_exec('python -V 2>&1'));
		}

		return $python_version;
	}
	public static function sw_wine_version()
	{
		$wine_version = null;

		if(pts_client::executable_in_path('wine') != false)
		{
			$wine_version = trim(shell_exec('wine --version 2>&1'));
		}
		else if(pts_client::executable_in_path('winecfg.exe') != false && pts_client::read_env('WINE_VERSION'))
		{
			$wine_version = trim(pts_client::read_env('WINE_VERSION'));

			if(stripos($wine_version, 'wine') === false)
			{
				$wine_version = 'wine-' . $wine_version;
			}
		}

		return $wine_version;
	}
}

?>
