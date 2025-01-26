<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2023, Phoronix Media
	Copyright (C) 2008 - 2023, Michael Larabel
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
	public static $report_wine_override = false;

	public static function properties()
	{
		return array(
			'username' => new phodevi_device_property('sw_username', phodevi::std_caching),
			'hostname' => new phodevi_device_property('sw_hostname', phodevi::smart_caching),
			'vendor-identifier' => new phodevi_device_property('sw_vendor_identifier', phodevi::smart_caching),
			'filesystem' => new phodevi_device_property('sw_filesystem', phodevi::no_caching),
			'virtualized-mode' => new phodevi_device_property('sw_virtualized_mode', phodevi::smart_caching),
			'java-version' => new phodevi_device_property('sw_java_version', phodevi::std_caching),
			'python-version' => new phodevi_device_property('sw_python_version', phodevi::std_caching),
			'wine-version' => new phodevi_device_property('sw_wine_version', phodevi::std_caching),
			'display-server' => new phodevi_device_property('sw_display_server', phodevi::smart_caching),
			'display-driver' => new phodevi_device_property(array('sw_display_driver', false), phodevi::smart_caching),
			'display-driver-string' => new phodevi_device_property(array('sw_display_driver', true), phodevi::smart_caching),
			'dri-display-driver' => new phodevi_device_property('sw_dri_display_driver', phodevi::smart_caching),
			'opengl-driver' => new phodevi_device_property('sw_opengl_driver', phodevi::std_caching),
			'vulkan-driver' => new phodevi_device_property('sw_vulkan_driver', phodevi::std_caching),
			'opencl-driver' => new phodevi_device_property('sw_opencl_driver', phodevi::std_caching),
			'opengl-vendor' => new phodevi_device_property('sw_opengl_vendor', phodevi::smart_caching),
			'desktop-environment' => new phodevi_device_property('sw_desktop_environment', phodevi::smart_caching),
			'operating-system' => new phodevi_device_property('sw_operating_system', phodevi::smart_caching),
			'os-version' => new phodevi_device_property('sw_os_version', phodevi::smart_caching),
			'kernel' => new phodevi_device_property('sw_kernel', phodevi::smart_caching),
			'kernel-architecture' => new phodevi_device_property('sw_kernel_architecture', phodevi::smart_caching),
			'kernel-date' => new phodevi_device_property('sw_kernel_date', phodevi::smart_caching),
			'kernel-string' => new phodevi_device_property('sw_kernel_string', phodevi::smart_caching),
			'kernel-parameters' => new phodevi_device_property('sw_kernel_parameters', phodevi::std_caching),
			'compiler' => new phodevi_device_property('sw_compiler', phodevi::no_caching),
			'system-layer' => new phodevi_device_property('sw_system_layer', phodevi::no_caching),
			'environment-variables' => new phodevi_device_property('sw_environment_variables', phodevi::std_caching),
			'security-features' => new phodevi_device_property('sw_security_features', phodevi::std_caching),
			'kernel-extra-details' => new phodevi_device_property('sw_kernel_extra_details', phodevi::std_caching),
			'battery' => new phodevi_device_property('battery', phodevi::smart_caching),
			'platform-profile' => new phodevi_device_property('sw_platform_profile', phodevi::std_caching),
			'npu' => new phodevi_device_property('npu', phodevi::std_caching),
			);
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
	public static function sw_platform_profile()
	{
		$platform_profile = '';

		if(phodevi::is_linux())
		{
			if(is_file('/sys/firmware/acpi/platform_profile'))
			{
				$platform_profile = pts_file_io::file_get_contents('/sys/firmware/acpi/platform_profile');
			}
		}

		return $platform_profile;
	}
	public static function npu()
	{
		$npus = array();
		foreach(pts_file_io::glob('/sys/class/accel/accel*/device/enable') as $accel_device)
		{
			$enable = pts_file_io::file_get_contents($accel_device);
			if($enable == '1')
			{
				$accel_dir = dirname($accel_device);
				$vendor = pts_file_io::file_get_contents($accel_dir . '/vendor');
				if($vendor == '0x8086')
				{
					$npus[] = 'Intel NPU';
				}
			}
		}
		if(count(pts_file_io::glob('/sys/memx*/verinfo')) > 0)
		{
			// Unfortunately no better name reporting...
			$npus[] = 'MemryX NPU';
		}
		return implode(' + ', $npus);
	}
	public static function sw_kernel_extra_details()
	{
		$extra = array();

		if(phodevi::is_linux())
		{
			if(is_file('/sys/kernel/mm/transparent_hugepage/enabled'))
			{
				$thp_enabled = file_get_contents('/sys/kernel/mm/transparent_hugepage/enabled');
				if(($x = strpos($thp_enabled, '[')) !== false)
				{
					$thp_enabled = substr($thp_enabled, $x + 1);
					if(($x = strpos($thp_enabled, ']')) !== false)
					{
						$thp_enabled = trim(substr($thp_enabled, 0, $x));
						if(!empty($thp_enabled))
						{
							$extra[] = 'Transparent Huge Pages: ' . $thp_enabled;
						}
					}
				}

			}
		}

		return implode(' - ', $extra);
	}
	public static function sw_system_layer()
	{
		$layer = null;

		if(phodevi::is_windows() && pts_client::executable_in_path('winecfg.exe') && ($wine = phodevi::read_property('system', 'wine-version')))
		{
			$layer = $wine;
		}
		else if((getenv('USE_WINE') || getenv('WINE_VERSION') || self::$report_wine_override) && ($wine = phodevi::read_property('system', 'wine-version')))
		{
			$layer = $wine;
		}
		else
		{
			// Report virtualization
			$layer = phodevi::read_property('system', 'virtualized-mode');
		}

		if(empty($layer) && is_file('/proc/version'))
		{
			if(stripos(file_get_contents('/proc/version'), 'Microsoft') !== false && stripos(file_get_contents('/proc/mounts'), 'lxfs') !== false)
			{
				// Microsoft Windows Subsystem for Linux
				$layer = 'WSL';
			}
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

		if(phodevi::is_macos())
		{
			$fs = phodevi_osx_parser::read_osx_system_profiler('SPSerialATADataType', 'FileSystem', false, array('MS-DOS FAT32'));

			if($fs == null && pts_client::executable_in_path('mount'))
			{
				$mount = shell_exec('mount 2>&1');
				if(stripos($mount, ' on / (hfs, local, journaled)') !== false)
				{
					$fs = 'Journaled HFS+';
				}
				else if(stripos($mount, ' on / (hfs') !== false)
				{
					$fs = 'HFS+';
				}
				else if(stripos($mount, ' on / (apfs') !== false)
				{
					$fs = 'APFS';
				}
			}
		}
		else if(phodevi::is_bsd())
		{
			if(pts_client::executable_in_path('mount'))
			{
				$mount = shell_exec('mount 2>&1');

				if(($start = strpos($mount, 'on / (')) != false)
				{
					// FreeBSD, DragonflyBSD mount formatting
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
				else if(($start = strpos($mount, 'on / type')) != false)
				{
					// OpenBSD 5.0 formatting is slightly different from above FreeBSD example
					// TODO: improve this in case there are other partitions, etc
					$fs = substr($mount, $start + 10);
					$fs = substr($fs, 0, strpos($fs, ' '));
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
			$fs = shell_exec('stat ' . pts_client::test_install_root_path() . ' -L -f -c %T 2> /dev/null');

			if(!empty($fs))
			{
				$fs = trim($fs);
			}

			switch($fs)
			{
				case 'ext2/ext3':
					if(isset(phodevi::$vfs->mounts))
					{
						$fstab = phodevi::$vfs->mounts;
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
						else if(is_dir('/proc/fs/ext4/'))
						{
							$fs = 'ext4';
						}
						else if(is_dir('/proc/fs/ext3/'))
						{
							$fs = 'ext3';
						}
					}
					break;
				case 'Case-sensitive Journaled HFS+':
					$fs = 'HFS+';
					break;
				case 'MS-DOS FAT32':
					$fs = 'FAT32';
					break;
				case 'UFSD_NTFS_COMPR':
					$fs = 'NTFS';
					break;
				case 'ecryptfs':
					if(isset(phodevi::$vfs->mounts))
					{
						// An easy attempt to determine what file-system is underneath ecryptfs if being compared
						// For now just attempt to figure out the root file-system.
						if(($s = strrpos(phodevi::$vfs->mounts, ' / ')) !== false)
						{
							$s = substr(phodevi::$vfs->mounts, ($s + 3));
							$s = substr($s, 0, strpos($s, ' '));


							if($s != null && !isset($s[18]) && $s != 'rootfs'&& pts_strings::string_only_contains($s, pts_strings::CHAR_LETTER | pts_strings::CHAR_NUMERIC))
							{
								$fs = $s . ' (ecryptfs)';
							}
						}
					}
					break;
				default:
					if(!empty($fs) && substr($fs, 0, 9) == 'UNKNOWN (')
					{
						$magic_block = substr($fs, 9, -1);
						$known_magic_blocks = array(
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
							'0xff534d42' => 'CIFS',
							'0x24051905' => 'UBIFS',
							'0x1021994' => 'TMPFS',
							'0x73717368' => 'SquashFS',
							'0xc97e8168' => 'LogFS',
							'0x5346544E' => 'NTFS',
							'0xf15f' => 'eCryptfs',
							'0x61756673' => 'AuFS',
							'0xbd00bd0' => 'Lustre',
							'0xaad7aaea' => 'PanFS', // Panasas FS
							'0xf2f52010' => 'F2FS',
							'0xc36400' => 'CephFS',
							'0x53464846' => 'WSLFS',
							'0xca451a4e' => 'BcacheFS'
							);

						foreach($known_magic_blocks as $hex => $name)
						{
							if($magic_block == $hex)
							{
								$fs = $name;
								break;
							}
						}
					}
					break;
			}

			if(strpos($fs, 'UNKNOWN') !== false && isset(phodevi::$vfs->mounts))
			{
				$mounts = phodevi::$vfs->mounts;
				$fs_r = array();

				$fs_checks = array(
					'squashfs' => 'SquashFS',
					'aufs' => 'AuFS',
					'unionfs' => 'UnionFS',
					'overlay' => 'overlayfs',
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
			// TODO could use better detection to verify if C: or the desired disk under test... but most of the time will be NTFS anyways
			$fs = filter_var(trim(shell_exec('powershell -NoProfile "(Get-WMIObject -Class Win32_Volume | Select DriveLetter,FreeSpace,Capacity,DeviceID,Label,@{Name=\"FileSystemType\";Expression={$_.\"FileSystem\"}})[1].FileSystemType"')), FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_HIGH);
			if(empty($fs) || $fs == 'Unknown' || $fs == 'FAT32')
			{
				$fs = filter_var(trim(shell_exec('powershell -NoProfile "(Get-WMIObject -Class Win32_Volume | Select DriveLetter,FreeSpace,Capacity,DeviceID,Label,@{Name=\"FileSystemType\";Expression={$_.\"FileSystem\"}})[0].FileSystemType"')),FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_HIGH);
			}

			// Fallback for Windows 8
			if(empty($fs) || $fs == 'Unknown' || $fs == 'FAT32' || stripos($fs, 'not'))
			{
				if(strpos(shell_exec('fsutil fsinfo volumeinfo C:'), 'NTFS') !== false)
				{
					$fs = 'NTFS';
				}
			}
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
		$mobo = phodevi::read_name('motherboard');
		$gpu = phodevi::read_name('gpu');
		$cpu = phodevi::read_property('cpu', 'model');

		if(strpos($cpu, 'QEMU') !== false || (is_readable('/sys/class/dmi/id/bios_vendor') && pts_file_io::file_get_contents('/sys/class/dmi/id/bios_vendor') == 'QEMU'))
		{
			$virtualized = 'QEMU';

			if(strpos($cpu, 'QEMU Virtual') !== false)
			{
				$qemu_version = substr($cpu, (strrpos($cpu, ' ') + 1));

				if(pts_strings::is_version($qemu_version))
				{
					$virtualized .= ' ' . $qemu_version;
				}
			}
		}
		else if(stripos($gpu, 'VMware') !== false || (is_readable('/sys/class/dmi/id/product_name') && stripos(pts_file_io::file_get_contents('/sys/class/dmi/id/product_name'), 'VMware') !== false))
		{
			$virtualized = 'VMware';
		}
		else if(stripos($gpu, 'VirtualBox') !== false || stripos(phodevi::read_name('motherboard'), 'VirtualBox') !== false)
		{
			$virtualized = 'VirtualBox';

			if($vbox_manage = pts_client::executable_in_path('VBoxManage'))
			{
				$vbox_manage = trim(shell_exec($vbox_manage . ' --version 2> /dev/null'));

				if(is_numeric(substr($vbox_manage, 0, 1)))
				{
					$virtualized .= ' ' . $vbox_manage;
				}
			}
			else if($modinfo = pts_client::executable_in_path('modinfo'))
			{
				$modinfo = trim(shell_exec('modinfo -F version vboxguest 2> /dev/null'));

				if($modinfo != null && pts_strings::is_version(str_ireplace(array('_', 'RC', 'beta'), '', $modinfo)))
				{
					$virtualized .= ' ' . $modinfo;
				}
			}

		}
		else if(is_file('/sys/class/dmi/id/sys_vendor') && pts_file_io::file_get_contents('/sys/class/dmi/id/sys_vendor') == 'Xen')
		{
			$virtualized = pts_file_io::file_get_contents('/sys/class/dmi/id/product_name');

			if(strpos($virtualized, 'Xen') === false)
			{
				$virtualized = 'Xen ' . $virtualized;
			}

			// version string
			$virtualized .= ' ' . pts_file_io::file_get_contents('/sys/class/dmi/id/product_version');

			// $virtualized should be then e.g. 'Xen HVM domU 4.1.1'
		}
		else if(stripos($gpu, 'Microsoft Hyper-V') !== false)
		{
			$virtualized = 'Microsoft Hyper-V Server';
		}
		else if(stripos($mobo, 'Parallels Software') !== false)
		{
			$virtualized = 'Parallels Virtualization';
		}
		else if(is_file('/sys/hypervisor/type'))
		{
			$type = pts_file_io::file_get_contents('/sys/hypervisor/type');
			$version = array();

			foreach(array('major', 'minor', 'extra') as $v)
			{
				if(is_file('/sys/hypervisor/version/' . $v))
				{
					$v = pts_file_io::file_get_contents('/sys/hypervisor/version/' . $v);
				}
				else
				{
					continue;
				}

				if($v != null)
				{
					if(!empty($version) && substr($v, 0, 1) != '.')
					{
						$v = '.' . $v;
					}
					array_push($version, $v);
				}
			}

			$virtualized = ucwords($type) . ' ' . implode('', $version) . ' Hypervisor';
		}

		if($systemd_virt = pts_client::executable_in_path('systemd-detect-virt'))
		{
			$systemd_virt = trim(shell_exec($systemd_virt . ' 2> /dev/null'));

			if($systemd_virt != null && $systemd_virt != 'none')
			{
				switch($systemd_virt)
				{
					case 'kvm':
						$systemd_virt = 'KVM';
						break;
					case 'oracle':
						$systemd_virt = 'Oracle';
						break;
				}

				if($virtualized != null && stripos($virtualized, $systemd_virt) === false && stripos($systemd_virt, $virtualized) === false)
				{
					$virtualized = $systemd_virt . ' ' . $virtualized;
				}
				else if($virtualized == null)
				{
					$virtualized = $systemd_virt;
				}
			}
		}

		if(empty($virtualized))
		{
			if(is_file('/.dockerenv'))
			{
				$virtualized = 'Docker';
			}
			else if(is_file('/dev/lxc/console'))
			{
				$virtualized = 'lxc';
			}
		}

		return $virtualized;
	}
	public static function sw_environment_variables()
	{
		$check_variables = array('LIBGL', '__GL', 'DRI_', 'DEBUG', 'FLAGS', 'PERF_', 'PERFTEST');
		$to_report = array();

		if(stripos(phodevi::read_property('system', 'opengl-driver'), 'Mesa'))
		{
			array_push($check_variables, 'MESA', 'GALLIUM');
		}

		if(isset($_SERVER))
		{
			foreach($_SERVER as $name => &$value)
			{
				foreach($check_variables as $var)
				{
					if(stripos($name, $var) !== false && $name != '__GL_SYNC_TO_VBLANK' && strpos($name, 'GJS') === false && strpos($name, 'DEBUGINFO') === false)
					{
						$value = trim($value);
						if(strpos($value, ' ') !== false)
						{
							$value = '"' . $value . '"';
						}
						array_push($to_report, $name . '=' . $value);
						break;
					}
				}

			}
		}

		return implode(' ', array_unique($to_report));
	}
	public static function sw_security_features()
	{
		$security = array();
		if(pts_client::executable_in_path('getenforce'))
		{
			$selinux = shell_exec('getenforce 2>&1');
			if(strpos($selinux, 'Enforcing') !== false)
			{
				$security[] = 'SELinux';
			}
		}

		// Meltdown / KPTI check
		if(phodevi::is_linux())
		{
		/*	if(strpos(phodevi::$vfs->dmesg, 'page tables isolation: enabled') !== false)
			{
				// Kernel Page Table Isolation
				$security[] = 'KPTI';
			}
*/
			// Spectre
			foreach(pts_file_io::glob('/sys/devices/system/cpu/vulnerabilities/*') as $vuln)
			{
				$fc = file_get_contents($vuln);
				$fc = str_replace('Mitigation: ', 'Mitigation of ', $fc);
				$fc = str_replace('Speculative Store Bypass', 'SSB', $fc);
				$fc = str_replace(' + ', ' plus ', $fc);
				if(!empty($fc))
				{
					$security[] = basename($vuln) . ': ' . $fc;
				}
			}
		}
		else if(phodevi::is_bsd())
		{
			// FreeBSD
			if(phodevi_bsd_parser::read_sysctl('vm.pmap.pti') == '1')
			{
				$security[] = 'KPTI';
			}
			if(phodevi_bsd_parser::read_sysctl('hw.ibrs_active') == '1')
			{
				$security[] = 'IBRS';
			}

			// DragonFlyBSD
			if(($spectre = phodevi_bsd_parser::read_sysctl('machdep.spectre_mitigation')) != '0' && $spectre != 'NONE' && !empty($spectre))
			{
				$security[] = 'Spectre ' . $spectre . ' Mitigation';
			}
			if(phodevi_bsd_parser::read_sysctl('machdep.meltdown_mitigation') == '1')
			{
				$security[] = 'Meltdown Mitigation';
			}
		}
		else if(phodevi::is_windows())
		{
			$mds_tool = microsoft_dependency_handler::file_download_location() . 'mdstool-cli.exe';
			if(is_file($mds_tool))
			{
				$mds_output = shell_exec($mds_tool);
				if(!empty($mds_output))
				{
					$mds_output = preg_replace('#\\x1b[[][^A-Za-z]*[A-Za-z]#', '', $mds_output);

					foreach(array('__user pointer sanitization: Disabled', 'Retpoline: Full', 'IBPB: Always', 'IBRS: Enabled', 'STIBP: Enabled', 'KPTI Enabled: Yes', 'PTE Inversion: Yes') as $check)
					{
						if(stripos($mds_output, $check) !== false)
						{
							$security[] = $check;
						}
					}
				}
			}

			// Windows 10+ security features: VBS, HVCI
			// https://docs.microsoft.com/en-us/windows/security/threat-protection/device-guard/enable-virtualization-based-protection-of-code-integrity#virtualizationbasedsecuritystatus
			$vbs_status = trim(shell_exec('powershell -NoProfile "(Get-CimInstance -ErrorAction SilentlyContinue –ClassName Win32_DeviceGuard –Namespace root\Microsoft\Windows\DeviceGuard).VirtualizationBasedSecurityStatus"'));
			switch($vbs_status) {
				case '0':
					$security[] = 'VBS: Disabled';
					break;
				case '1':
					$security[] = 'VBS: Enabled but not running';
					break;
				case '2':
					$security[] = 'VBS: Enabled and running';
					break;
			}

			// https://docs.microsoft.com/en-us/windows/security/threat-protection/device-guard/enable-virtualization-based-protection-of-code-integrity#securityservicesconfigured
			$security_services_running = preg_split('/\r\n|\n|\r/', trim(shell_exec('powershell -NoProfile "(Get-CimInstance -ErrorAction SilentlyContinue –ClassName Win32_DeviceGuard –Namespace root\Microsoft\Windows\DeviceGuard).SecurityServicesRunning"')));
			if(in_array('2', $security_services_running)) {
				$security[] = 'HVCI: Running';

				// Mode Based Execution Control (MBEC) is relevant to HVCI performance and is available in Intel Kaby Lake and newer and AMD Zen 2 and newer
				// https://docs.microsoft.com/en-us/windows/security/threat-protection/device-guard/enable-virtualization-based-protection-of-code-integrity#hvci-features
				// https://docs.microsoft.com/en-us/windows/security/threat-protection/device-guard/enable-virtualization-based-protection-of-code-integrity#availablesecurityproperties
				$available_security_properties = preg_split('/\r\n|\n|\r/', trim(shell_exec('powershell -NoProfile "(Get-CimInstance -ErrorAction SilentlyContinue –ClassName Win32_DeviceGuard –Namespace root\Microsoft\Windows\DeviceGuard).AvailableSecurityProperties"')));
				if(in_array('7', $available_security_properties)) {
					$security[] = 'MBEC: Available';
				} else {
					$security[] = 'MBEC: Unavailable';
				}
			}
		}

		return !empty($security) ? implode(' + ',  $security) : null;
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
				$version = trim(shell_exec('gcc -dumpversion 2>&1'));
				$v = shell_exec('gcc -v 2>&1');
				if(pts_strings::is_version($version))
				{

					if(($t = strrpos($v, $version . ' ')) !== false)
					{
						$v = substr($v, ($t + strlen($version) + 1));
						$v = substr($v, 0, strpos($v, ' '));

						if($v != null && is_numeric($v))
						{
							// On development versions the release date is expressed
							// e.g. gcc version 4.7.0 20120314 (prerelease) (GCC)
							$version .= ' ' . $v;
						}
						else
						{
							$v = shell_exec('gcc --version 2>&1');
							if(($t = strrpos($v, $version)) !== false)
							{
								$v = substr($v, $t);
								$v = substr($v, 0, strpos(str_replace(PHP_EOL, ' ', $v), ' '));
								if(($t = strpos($v, ')')) !== false)
								{
									$v = substr($v, 0, $t);
								}

								if(pts_strings::is_version($v))
								{
									$version = $v;
								}
							}
						}
					}

					$compilers['gcc'] = 'GCC ' . $version;
				}
				else if(($t = strpos($v, ' version ')) !== false)
				{
					$v = substr($v, ($t + strlen(' version ')));
					if(($t = strpos($v, ' (')) !== false)
					{
						$v = substr($v, 0, $t);
						$compilers['gcc'] = 'GCC ' . $v;
					}
				}
			}
			// sometimes "copyright" slips into version string
			if(isset($compilers['gcc']))
			{
				$compilers['gcc'] = str_replace('Copyright', '', $compilers['gcc']);
			}
		}

		if(pts_client::executable_in_path('pgcc'))
		{
			// NVIDIA PGI Compiler
			$compilers['pgcc'] = 'PGI Compiler';
			$v = trim(shell_exec('pgcc --version 2>&1'));
			$v = substr($v, strpos($v, 'pgcc ') + 5);
			$v = substr($v, 0, strpos($v, ' '));
			if(pts_strings::is_version(str_replace('-', '', $v)))
			{
				$compilers['pgcc'] .= ' ' . $v;
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

		if(pts_client::executable_in_path('pgcpp') || pts_client::executable_in_path('pgCC'))
		{
			// The Portland Group Compilers
			$compilers['pgcpp'] = 'PGI C-C++ Workstation';
		}

		if(($clang = pts_client::executable_in_path('clang')))
		{
			// Clang
			$compiler_info = shell_exec(escapeshellarg($clang) . ' --version');
			if(!empty($compiler_info) && ($cv_pos = stripos($compiler_info, 'clang version')) !== false)
			{
				// With Clang 3.0 and prior, the --version produces output where the first line is:
				// e.g. clang version 3.0 (branches/release_30 142590)

				$compiler_info = substr($compiler_info, ($cv_pos + 14));
				$compiler_info = str_replace(PHP_EOL, ' ', $compiler_info);
				$clang_version = substr($compiler_info, 0, strpos($compiler_info, ' '));

				// XXX: the below check bypass now because e.g. Ubuntu appends '-ubuntuX', etc that breaks check
				if(pts_strings::is_version($clang_version) || true)
				{
					// Also see if there is a Clang SVN tag to fetch
					$compiler_info = substr($compiler_info, 0, strpos($compiler_info, PHP_EOL));
					if(($cv_pos = strpos($compiler_info, ')')) !== false)
					{
						$compiler_info = substr($compiler_info, 0, $cv_pos);
						$compiler_info = substr($compiler_info, (strrpos($compiler_info, ' ') + 1));

						if(is_numeric($compiler_info))
						{
							// Right now Clang/LLVM uses SVN system and their revisions are only numeric
							$clang_version .= ' (SVN ' . $compiler_info . ')';
						}
					}

					$compiler_info = 'Clang ' . $clang_version;
				}
				else
				{
					$compiler_info = null;
				}
			}
			else if(!empty($compiler_info))
			{
				$compiler_info = substr($compiler_info, 0, strpos($compiler_info, PHP_EOL));
			}

			// Clang
			if(empty($compiler_info) || stripos($compiler_info, 'not found') !== false)
			{
				// At least with Clang ~3.0 the -dumpversion is reporting '4.2.1' ratherthan the useful information...
				// This is likely just for GCC command compatibility, so only use this as a fallback
				$compiler_info = 'Clang ' . trim(shell_exec('clang -dumpversion 2> /dev/null'));
			}

			$compilers['clang'] = $compiler_info;
		}

		// For now at least Intel oneAPI has symlink from clang to icpx so this below code isn't needed in such case
		if(!pts_client::executable_in_path('clang') && ($icpx = pts_client::executable_in_path('icpx')))
		{
			// Intel oneAPI DPC++/C++ Compiler
			$icpx = shell_exec(escapeshellarg($icpx) . ' --version');
			$icpx = substr($icpx, 0, strpos($icpx, PHP_EOL));
			if(stripos($icpx, 'oneAPI') !== false)
			{
				$icpx = str_ireplace(array('(R)'), '', $icpx);
				if(($x = strpos($icpx, ' (')) !== false)
				{
					$icpx = substr($icpx, 0, $x);
				}
			}

			$compilers['icpx'] = $icpx;
		}

		if(($llvm_ld = pts_client::executable_in_path('llvm-link')) || ($llvm_ld = pts_client::executable_in_path('llvm-ld')))
		{
			// LLVM - Low Level Virtual Machine
			// Reading the version from llvm-ld (the LLVM linker) should be safe as well for finding out version of LLVM in use
			// As of LLVM 3.2svn, llvm-ld seems to be llvm-link

			$info = trim(shell_exec($llvm_ld . ' -version 2> /dev/null'));

			if(($s = strpos($info, 'version')) != false)
			{
				$info = substr($info, 0, strpos($info, PHP_EOL, $s));
				$info = substr($info, (strrpos($info, ' ') + 1));

				if(pts_strings::is_version(str_replace('svn', '', $info)))
				{
					$compilers['llvmc'] = 'LLVM ' . $info;
				}
			}
		}
		else if(pts_client::executable_in_path('llvm-config'))
		{
			// LLVM - Low Level Virtual Machine config
			$info = trim(shell_exec('llvm-config --version 2> /dev/null'));
			if(pts_strings::is_version(str_replace('svn', '', $info)))
			{
				$compilers['llvmc'] = 'LLVM ' . $info;
			}
		}
		else if(pts_client::executable_in_path('llvmc'))
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

		if(pts_client::executable_in_path('ioc'))
		{
			// Intel Offline Compiler (IOC) SDK for OpenCL
			// -v e.g. : Intel(R) SDK for OpenCL* - Offline Compiler 2012 Command-Line Client, version 1.0.2
			$info = trim(shell_exec('ioc -version 2>&1')) . ' ';

			if(($s = strpos($info, 'Offline Compiler ')) != false)
			{
				$compilers['ioc'] = 'Intel IOC SDK';
				$sv = substr($info, ($s + 17));
				$sv = substr($sv, 0, strpos($sv, ' '));

				if(is_numeric($sv))
				{
					$compilers['ioc'] .= ' ' . $sv;
				}

				if(($s = strpos($info, 'version ')) != false)
				{
					$sv = substr($info, ($s + 8));
					$sv = substr($sv, 0, strpos($sv, ' '));

					if(pts_strings::is_version($sv))
					{
						$compilers['ioc'] .= ' v' . $sv;
					}
				}
			}
		}

		if(($icc = pts_client::executable_in_path('icc')) || ($icc = pts_client::executable_in_path('icpc')))
		{
			// Intel oneAPI DPC++/C++ Compiler
			$icc = shell_exec(escapeshellarg($icc) . ' --version');
			$icc = substr($icc, 0, strpos($icc, PHP_EOL));
			if(stripos($icc, 'icc') !== false)
			{
				$icc = str_ireplace(array('(R)', '(ICC)', '(C)'), '', $icc);
				if(($x = strpos($icpx, ' (')) !== false)
				{
					$icpx = substr($icc, 0, $x);
				}
			}

			$icc = str_replace('icc', 'ICC', $icc);
			$compilers['icc'] = $icc;
		}

		if(phodevi::is_macos() && pts_client::executable_in_path('xcodebuild'))
		{
			$xcode = phodevi_osx_parser::read_osx_system_profiler('SPDeveloperToolsDataType', 'Xcode');
			$xcode = substr($xcode, 0, strpos($xcode, ' '));

			if($xcode)
			{
				$compilers['Xcode'] = 'Xcode ' . $xcode;
			}
		}

		if(($nvcc = pts_client::executable_in_path('nvcc')) || is_executable(($nvcc = '/usr/local/cuda/bin/nvcc')))
		{
			// Check outside of PATH too since by default the CUDA Toolkit goes to '/usr/local/cuda/' and relies upon user to update system
			// NVIDIA CUDA Compiler Driver
			$nvcc = shell_exec($nvcc . ' --version 2>&1');
			if(($s = strpos($nvcc, 'release ')) !== false)
			{
				$nvcc = str_replace(array(','), '', substr($nvcc, ($s + 8)));
				$nvcc = substr($nvcc, 0, strpos($nvcc, ' '));

				if(pts_strings::is_version($nvcc))
				{
					$compilers['CUDA'] = 'CUDA ' . $nvcc;
				}
			}
		}

		// Try to make the compiler that's used by default to appear first
		if(getenv('CC') && isset($compilers[basename(pts_strings::first_in_string(getenv('CC'), ' '))]))
		{
			$cc_env = basename(pts_strings::first_in_string(getenv('CC'), ' '));
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
				unset($compilers[getenv('CC')]);
				array_unshift($compilers, $default_compiler);
			}
		}

		return implode(' + ', array_unique($compilers));
	}
	public static function sw_kernel_string()
	{
		return trim(phodevi::read_property('system', 'kernel') . ' (' . phodevi::read_property('system', 'kernel-architecture') . ') ' . phodevi::read_property('system', 'kernel-date'));
	}
	public static function sw_kernel_date()
	{
		$date = null;
		$k = phodevi::read_property('system', 'kernel');

		if(strpos($k, '99') !== false || stripos($k, 'rc') !== false)
		{
			// For now at least only report kernel build date when it looks like it's a devel kernel
			$v = php_uname('v');
			if(($x = stripos($v, 'SMP ')) !== false)
			{
				$v = substr($v, ($x + 4));
				$date = strtotime($v);
				if($date != false)
				{
					$date = date('Ymd', $date);
				}
			}
		}

		return $date;
	}
	public static function sw_kernel()
	{
		if(phodevi::is_windows())
		{
			// CurrentBuild and CurrentVersion are available since at least NT 4.0
			// CurrentVersion is frozen at 6.3 (same as Windows 8.1) in Windows 10 & 11
			$current_build = trim(shell_exec('powershell -NoProfile "If (Get-ItemProperty -ErrorAction SilentlyContinue -Path \'Registry::HKEY_LOCAL_MACHINE\Software\Microsoft\Windows NT\CurrentVersion\' CurrentBuild) { (Get-ItemProperty -Path \'Registry::HKEY_LOCAL_MACHINE\Software\Microsoft\Windows NT\CurrentVersion\' CurrentBuild).CurrentBuild } Else { $null }"'));

			// Windows 10 and later add CurrentMajorVersionNumber, CurrentMinorVersionNumber and UBR
			$current_major_version_number = trim(shell_exec('powershell -NoProfile "If (Get-ItemProperty -ErrorAction SilentlyContinue -Path \'Registry::HKEY_LOCAL_MACHINE\Software\Microsoft\Windows NT\CurrentVersion\' CurrentMajorVersionNumber) { (Get-ItemProperty -Path \'Registry::HKEY_LOCAL_MACHINE\Software\Microsoft\Windows NT\CurrentVersion\' CurrentMajorVersionNumber).CurrentMajorVersionNumber } Else { $null }"'));

			// Try using Windows 10+ values
			if (is_numeric($current_major_version_number)) {
				$current_minor_version_number = trim(shell_exec('powershell -NoProfile "If (Get-ItemProperty -ErrorAction SilentlyContinue -Path \'Registry::HKEY_LOCAL_MACHINE\Software\Microsoft\Windows NT\CurrentVersion\' CurrentMinorVersionNumber) { (Get-ItemProperty -Path \'Registry::HKEY_LOCAL_MACHINE\Software\Microsoft\Windows NT\CurrentVersion\' CurrentMinorVersionNumber).CurrentMinorVersionNumber } Else { $null }"'));
				$update_build_revision = trim(shell_exec('powershell -NoProfile "If (Get-ItemProperty -ErrorAction SilentlyContinue -Path \'Registry::HKEY_LOCAL_MACHINE\Software\Microsoft\Windows NT\CurrentVersion\' UBR) { (Get-ItemProperty -Path \'Registry::HKEY_LOCAL_MACHINE\Software\Microsoft\Windows NT\CurrentVersion\' UBR).UBR } Else { $null }"'));

				if(is_numeric($current_minor_version_number) && is_numeric($current_build) && is_numeric($update_build_revision)) {
					return $current_major_version_number . '.' . $current_minor_version_number . '.' . $current_build . '.' . $update_build_revision;
				}
			}

			// Fall back to Windows 8.1 and earlier values
			$current_version = trim(shell_exec('powershell -NoProfile "If (Get-ItemProperty -ErrorAction SilentlyContinue -Path \'Registry::HKEY_LOCAL_MACHINE\Software\Microsoft\Windows NT\CurrentVersion\' CurrentVersion) { (Get-ItemProperty -Path \'Registry::HKEY_LOCAL_MACHINE\Software\Microsoft\Windows NT\CurrentVersion\' CurrentVersion).CurrentVersion } Else { $null }"'));

			if(is_numeric($current_version) && is_numeric($current_build)) {
				return $current_version . '.' . $current_build;
			}

			// Fall back to PHP implementation
			return php_uname('r');
		} else {
			return php_uname('r');
		}
	}
	public static function sw_kernel_parameters()
	{
		$parameters = null;

		if(is_file('/proc/cmdline') && is_file('/proc/modules'))
		{
			$modules = array();

			foreach(explode(PHP_EOL, pts_file_io::file_get_contents('/proc/modules')) as $module_line)
			{
				$module_line = explode(' ', $module_line);

				if(isset($module_line[0]) && !empty($module_line[0]))
				{
					array_push($modules, $module_line[0]);
				}
			}

			if(!empty($modules))
			{
				$to_report = array();
				$cmdline = explode(' ', pts_file_io::file_get_contents('/proc/cmdline'));
				foreach($cmdline as $option)
				{
					if(($t = strpos($option, '.')) !== false)
					{
						if(in_array(substr($option, 0, $t), $modules))
						{
							array_push($to_report, $option);
						}
					}
				}

				if(!empty($to_report))
				{
					$parameters = implode(' ', $to_report);
				}
			}
		}

		return $parameters;
	}
	public static function sw_kernel_architecture()
	{
		// Find out the kernel archiecture
		if(phodevi::is_windows())
		{
			//$kernel_arch = strpos($_SERVER['PROCESSOR_ARCHITECTURE'], 64) !== false || strpos($_SERVER['PROCESSOR_ARCHITEW6432'], 64 != false) ? 'x86_64' : 'i686';
			if(isset($_SERVER['PROCESSOR_ARCHITEW6432']))
			{
				$kernel_arch = $_SERVER['PROCESSOR_ARCHITEW6432'] == 'AMD64' ? 'x86_64' : 'i686';
			}
			else
			{
				$kernel_arch = 'x86_64';
			}
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
		if(phodevi::is_macos())
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

			if($os_version == null)
			{
				if(is_readable('/etc/os-release'))
					$os_release = parse_ini_file('/etc/os-release');
				else if(is_readable('/usr/lib/os-release'))
					$os_release = parse_ini_file('/usr/lib/os-release');
				else
					$os_release = null;

				if(isset($os_release['VERSION_ID']) && !empty($os_release['VERSION_ID']))
				{
					$os_version = $os_release['VERSION_ID'];
				}
				else if(isset($os_release['VERSION']) && !empty($os_release['VERSION']))
				{
					$os_version = $os_release['VERSION'];
				}
				$os_version = pts_strings::keep_in_string($os_version, pts_strings::CHAR_LETTER | pts_strings::CHAR_NUMERIC | pts_strings::CHAR_DECIMAL | pts_strings::CHAR_SPACE | pts_strings::CHAR_DASH | pts_strings::CHAR_UNDERSCORE);
			}
		}
		else if(phodevi::is_windows())
		{
			$os_version = phodevi_windows_parser::get_wmi_object('win32_operatingsystem', 'BuildNumber');
		}
		else
		{
			$os_version = php_uname('r');
		}
	
		return $os_version;
	}
	public static function sw_operating_system()
	{
		if(!PTS_IS_CLIENT)
		{
			// TODO: Figure out why this function is sometimes called from OpenBenchmarking.org....
			return false;
		}

		// Determine the operating system release
		if(phodevi::is_linux())
		{
			$vendor = phodevi_linux_parser::read_lsb_distributor_id();

			if($vendor == null)
			{
				if(is_readable('/etc/os-release'))
					$os_release = parse_ini_file('/etc/os-release');
				else if(is_readable('/usr/lib/os-release'))
					$os_release = parse_ini_file('/usr/lib/os-release');
				else
					$os_release = null;

				if(isset($os_release['PRETTY_NAME']) && !empty($os_release['PRETTY_NAME']))
				{
					$vendor = $os_release['PRETTY_NAME'];
				}
				else if(isset($os_release['NAME']) && !empty($os_release['NAME']))
				{
					$vendor = $os_release['NAME'];
				}

				$vendor = str_replace('é', 'e', $vendor);
			}

			if(($x = stripos($vendor, ' for ')) !== false)
			{
				$vendor = substr($vendor, 0, $x);
			}

			$vendor = str_replace(array(' Software'), '', $vendor);
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
						$proposed_os = substr($file, 0, strpos($file, PHP_EOL));

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
				if(is_file('/etc/debian_version'))
				{
					$os = 'Debian ' . php_uname('s') . ' ' . ucwords(pts_file_io::file_get_contents('/etc/debian_version'));
				}
				else
				{
					$os = php_uname('s');
				}
			}
			else if(strpos($os, ' ') === false)
			{
				// The OS string is only one word, likely a problem...
				if(is_file('/etc/arch-release') && stripos($os, 'Arch') === false)
				{
					// On at least some Arch installs (ARM) the file is empty so would have missed above check
					$os = trim('Arch Linux ' . $os);
				}
			}
		}
		else if($version != null && stripos($vendor, $version) === false)
		{
			$os = $vendor . ' ' . $version;
		}
		else
		{
			$os = $vendor;
		}

		if(($break_point = strpos($os, ':')) > 0)
		{
			$os = substr($os, $break_point + 1);
		}
		
		if(phodevi::is_macos())
		{
			$os = phodevi_osx_parser::read_osx_system_profiler('SPSoftwareDataType', 'SystemVersion');
		}
		else if(phodevi::is_windows())
		{
			$os = $info = phodevi_windows_parser::get_wmi_object('win32_operatingsystem', 'caption') . ' Build ' . phodevi::read_property('system', 'os-version');
			if(strpos($os, 'Windows') === false)
			{
				$os = trim(exec('ver'));
			}
		}	
		if(($break_point = strpos($os, '(')) > 0)
		{
			$os = substr($os, 0, $break_point);
		}

		$os = trim($os);

		return $os;
	}
	public static function sw_desktop_environment()
	{
		$desktop = null;
		$desktop_environment = null;
		$desktop_version = null;
		$desktop_session = getenv('DESKTOP_SESSION');

		if(pts_client::is_process_running('gnome-shell'))
		{
			// GNOME 3.0 / GNOME Shell
			$desktop_environment = 'GNOME Shell';

			if(pts_client::executable_in_path('gnome-shell'))
			{
				$desktop_version = pts_strings::last_in_string(trim(shell_exec('gnome-shell --version 2> /dev/null')));
			}
		}
		else if(pts_client::is_process_running('gnome-panel') || $desktop_session == 'gnome')
		{
			// GNOME
			$desktop_environment = 'GNOME';

			if(pts_client::executable_in_path('gnome-about'))
			{
				$desktop_version = pts_strings::last_in_string(trim(shell_exec('gnome-about --version 2> /dev/null')));
			}
			else if(pts_client::executable_in_path('gnome-session'))
			{
				$desktop_version = pts_strings::last_in_string(trim(shell_exec('gnome-session --version 2> /dev/null')));
			}
		}
		else if(pts_client::is_process_running('unity-panel-service') || $desktop_session == 'ubuntu')
		{
			// Canonical / Ubuntu Unity Desktop
			$desktop_environment = 'Unity';

			if(pts_client::executable_in_path('unity'))
			{
				$desktop_version = pts_strings::last_in_string(trim(shell_exec('unity --version 2> /dev/null')));
			}
		}
		else if($desktop_session == 'mate')
		{
			$desktop_environment = 'MATE';
			if(pts_client::executable_in_path('mate-about'))
			{
				$desktop_version = pts_strings::last_in_string(trim(shell_exec('mate-about --version 2> /dev/null')));
			}
		}
		else if(($kde5 = pts_client::is_process_running('plasmashell')))
		{
			// KDE 5.x
			$desktop_environment = 'KDE Plasma';
			$desktop_version = shell_exec('plasmashell --version 2> /dev/null');
			if(!empty($desktop_version))
			{
				$desktop_version = pts_strings::last_in_string(trim($desktop_version));
			}
		}
		else if(($kde5 = pts_client::is_process_running('kded5')))
		{
			// KDE 5.x
			$desktop_environment = 'KDE Frameworks';
			if(pts_client::executable_in_path('kdeinit5'))
			{
				$desktop_version = pts_strings::last_in_string(trim(shell_exec('kdeinit5 --version 2> /dev/null')));
			}
		}
		else if(($dde = pts_client::is_process_running('dde-desktop')))
		{
			// KDE 5.x
			$desktop_environment = 'Deepin Desktop Environment';
			$desktop_version = null; // TODO XXX
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
		else if(pts_client::is_process_running('lxqt-panel') || $desktop_session == 'lxqt')
		{
			//$lx_output = trim(shell_exec('lxqt-panel --version'));
			//$version = substr($lx_output, strpos($lx_output, ' ') + 1);

			$desktop_environment = 'LXQt';
			if(pts_client::executable_in_path('lxqt-about'))
			{
				$desktop_version = pts_strings::last_in_string(trim(shell_exec('lxqt-about --version | grep liblxqt 2> /dev/null')));
			}
		}
		else if(pts_client::is_process_running('lxsession') || $desktop_session == 'lxde')
		{
			$lx_output = trim(shell_exec('lxpanel --version 2>&1'));
			$version = substr($lx_output, strpos($lx_output, ' ') + 1);

			$desktop_environment = 'LXDE';
			$desktop_version = $version;
		}
		else if(pts_client::is_process_running('xfce4-session') || pts_client::is_process_running('xfce-mcs-manager') || $desktop_session == 'xfce')
		{
			// Xfce 4.x
			$desktop_environment = 'Xfce';
			$xfce_output = trim(shell_exec('xfce4-session-settings --version 2>&1'));

			if(($open = strpos($xfce_output, '(Xfce')) > 0)
			{
				$xfce_output = substr($xfce_output, strpos($xfce_output, ' ', $open) + 1);
				$desktop_version = substr($xfce_output, 0, strpos($xfce_output, ')'));
			}
		}
		else if(pts_client::is_process_running('openbox'))
		{
			$desktop_environment = 'Openbox';
			$openbox_output = trim(shell_exec('openbox --version 2>&1'));

			if(($openbox_d = stripos($openbox_output, 'Openbox ')) !== false)
			{
				$openbox_output = substr($openbox_output, ($openbox_d + 8));
				$desktop_version = substr($openbox_output, 0, strpos($openbox_output, PHP_EOL));
			}
		}
		else if(pts_client::is_process_running('cinnamon'))
		{
			$desktop_environment = 'Cinnamon';
			$desktop_version = pts_strings::last_in_string(trim(shell_exec('cinnamon --version 2> /dev/null')));
		}
		else if(pts_client::is_process_running('sway'))
		{
			$desktop_environment = 'Sway';
			$desktop_version = pts_strings::last_in_string(trim(shell_exec('sway --version 2> /dev/null')));
		}
		else if(pts_client::is_process_running('enlightenment'))
		{
			$desktop_environment = 'Enlightenment';
			$desktop_version = null; // No known -v / --version command on any Enlightenment component
		}
		else if(pts_client::is_process_running('icewm'))
		{
			$desktop_environment = 'IceWM';
			$desktop_version = null;
		}
		else if(pts_client::is_process_running('budgie-panel') || $desktop_session == 'budgie-desktop')
		{
			// Budgie
			$desktop_environment = 'Budgie';
			$desktop_version = pts_strings::last_in_string(trim(shell_exec('budgie-desktop --version 2> /dev/null | grep desktop')));
		}
		else if(pts_client::is_process_running('cosmic-comp') && pts_client::is_process_running('cosmic-panel'))
		{
			// Pop OS COSMIC
			$desktop_environment = 'COSMIC';
			if(pts_client::executable_in_path('cosmic-launcher'))
			{
				$desktop_version = pts_strings::last_in_string(trim(shell_exec('cosmic-launcher -V 2> /dev/null')));
			}
		}

		if(!empty($desktop_environment))
		{
			$desktop = $desktop_environment;

			if(!empty($desktop_version) && pts_strings::is_version($desktop_version))
			{
				$desktop .= ' ' . $desktop_version;
			}
		}

		return $desktop;
	}
	public static function sw_display_server()
	{
		$display_servers = array();

		if(phodevi::is_windows())
		{
			// TODO: determine what to do for Windows support
		}
		else
		{
			if(pts_client::is_process_running('weston'))
			{
				$info = 'Wayland Weston';
				$vinfo = trim(shell_exec('weston --version 2>&1'));

				if(pts_strings::last_in_string($vinfo) && pts_strings::is_version(pts_strings::last_in_string($vinfo)))
				{
					$info .= ' ' . pts_strings::last_in_string($vinfo);
				}
					array_push($display_servers, $info);
			}
			if(pts_client::is_process_running('unity-system-compositor'))
			{
				$unity_system_comp = trim(str_replace('unity-system-compositor', '', shell_exec('unity-system-compositor --version 2>&1')));

				if(pts_strings::is_version($unity_system_comp))
				{
					array_push($display_servers, 'Unity-System-Compositor ' . $unity_system_comp);
				}

			}
			$xorg_log = isset(phodevi::$vfs->xorg_log) ? phodevi::$vfs->xorg_log : false;
			if($xorg_log && ($x = strpos($xorg_log, 'X.Org X Server ')) !== false)
			{
				$xorg_log = substr($xorg_log, ($x + strlen('X.Org X Server ')));
				$xorg_log = substr($xorg_log, 0, strpos($xorg_log, PHP_EOL));

				if(pts_strings::is_version($xorg_log))
				{
					array_push($display_servers, 'X Server ' . $xorg_log);
				}
			}
			else if(($x_bin = (is_executable('/usr/libexec/Xorg.bin') ? '/usr/libexec/Xorg.bin' : false)) || ($x_bin = pts_client::executable_in_path('Xorg')) || ($x_bin = pts_client::executable_in_path('X')))
			{
				// Find graphics subsystem version
				$info = shell_exec($x_bin . ' ' . (phodevi::is_solaris() ? ':0' : '') . ' -version 2>&1');
				$pos = (($p = strrpos($info, 'Release Date')) !== false ? $p : strrpos($info, 'Build Date'));
				$info = trim(substr($info, 0, $pos));

				if($pos === false || getenv('DISPLAY') == false)
				{
					$version = null;
				}
				else if(($pos = strrpos($info, '(')) === false && strrpos($info, 'Server') === false)
				{
					$version = trim(substr($info, strrpos($info, ' ')));
				}
				else
				{
					$version = trim(substr($info, strrpos($info, 'Server') + 6));

					if(!pts_strings::is_version($version))
					{
						$version = null;
					}
				}

				array_push($display_servers, trim('X Server ' . $version));
			}
			if(pts_client::is_process_running('surfaceflinger'))
			{
				array_push($display_servers, 'SurfaceFlinger');
			}

			if(pts_client::is_process_running('gnome-shell-wayland'))
			{
				array_push($display_servers, 'GNOME Shell Wayland');
			}

			if(getenv('WAYLAND_DISPLAY') != false)
			{
				array_push($display_servers, 'Wayland');
			}
		}

		return implode(' + ', $display_servers);
	}
	public static function sw_display_driver($with_version = true)
	{
		if(phodevi::is_windows())
		{
			$windows_driver = phodevi_windows_parser::get_wmi_object('Win32_VideoController', 'DriverVersion');

			if(($nvidia_smi = pts_client::executable_in_path('nvidia-smi')))
			{
				$smi_output = shell_exec(escapeshellarg($nvidia_smi) . ' -q -d CLOCK');
				if(($v = stripos($smi_output, 'Driver Version')) !== false)
				{
					$nv_version = substr($smi_output, strpos($smi_output, ':', $v) + 1);
					$nv_version = trim(substr($nv_version, 0, strpos($nv_version, "\n")));
					if(pts_strings::is_version($nv_version))
					{
						$windows_driver = $nv_version . ' (' . $windows_driver . ')';
					}
				}
			}
			return $windows_driver;
		}

		$display_driver = phodevi::read_property('system', 'dri-display-driver');
		$driver_version = null;

		if(empty($display_driver) || $display_driver == 'NVIDIA')
		{
			if(phodevi::is_nvidia_graphics() || is_file('/proc/driver/nvidia/version'))
			{
				$display_driver = 'NVIDIA';
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
			}
			else if((phodevi::is_mesa_graphics() || phodevi::is_bsd()) && stripos(phodevi::read_property('gpu', 'model'), 'NVIDIA') !== false)
			{
				if(is_file('/sys/class/drm/version'))
				{
					// If there's DRM loaded and NVIDIA, it should be Nouveau
					$display_driver = 'nouveau';
				}
			}
		}

		// XXX: As of PTS 10.2.1, commented out due to xorg logs growing too big on recent Ubuntu/bug causing slow perf
		if(false && !empty($display_driver))
		{
			$driver_version = phodevi_parser::read_xorg_module_version($display_driver . '_drv');

			if($driver_version == false || $driver_version == '1.0.0')
			{
				switch($display_driver)
				{
					case 'amd':
						// See if it's radeon driver
						$driver_version = phodevi_parser::read_xorg_module_version('radeon_drv');

						if($driver_version != false)
						{
							$display_driver = 'radeon';
						}
						// See if it's the newer AMDGPU driver
						$driver_version = phodevi_parser::read_xorg_module_version('amdgpu_drv');

						if($driver_version != false)
						{
							$display_driver = 'amdgpu';
						}
						break;
					case 'vmwgfx':
						// See if it's VMware driver
						$driver_version = phodevi_parser::read_xorg_module_version('vmware_drv');

						if($driver_version != false)
						{
							$display_driver = 'vmware';
						}
						break;
					case 'radeon':
						// RadeonHD driver also reports DRI driver as 'radeon', so try reading that instead
						$driver_version = phodevi_parser::read_xorg_module_version('radeonhd_drv');

						if($driver_version != false)
						{
							$display_driver = 'radeonhd';
						}
						$driver_version = phodevi_parser::read_xorg_module_version('amdgpu_drv');

						if($driver_version != false)
						{
							$display_driver = 'amdgpu';
						}
						break;
					case 'nvidia':
					case 'NVIDIA':
					case 'nouveau':
						// NVIDIA's binary driver usually ends up reporting 1.0.0
						if(($nvs_value = phodevi_parser::read_nvidia_extension('NvidiaDriverVersion')))
						{
							$display_driver = 'NVIDIA';
							$driver_version = $nvs_value;
						}
						else
						{
							// NVIDIA's binary driver appends their driver version on the end of the OpenGL version string
							$glxinfo = phodevi_parser::software_glxinfo_version();

							if(($pos = strpos($glxinfo, 'NVIDIA ')) != false)
							{
								$display_driver = 'NVIDIA';
								$driver_version = substr($glxinfo, ($pos + 7));
							}
						}
						break;
					default:
						if(is_readable('/sys/class/graphics/fb0/name'))
						{
							// This path works for at least finding NVIDIA Tegra 2 DDX (via tegra_fb)
							$display_driver = file_get_contents('/sys/class/graphics/fb0/name');
							$display_driver = str_replace(array('drm', '_fb'), '', $display_driver);
							$driver_version = phodevi_parser::read_xorg_module_version($display_driver . '_drv');
						}
						break;
				}
			}

			if($driver_version == false)
			{
				// If the version is empty, chances are the DDX driver string is incorrect
				$display_driver = null;

				// See if the VESA or fbdev driver is in use
				foreach(array('modesetting', 'fbdev', 'vesa') as $drv)
				{
					$drv_version = phodevi_parser::read_xorg_module_version($drv . '_drv');

					if($drv_version)
					{
						$display_driver = $drv;
						$driver_version = $drv_version;
						break;
					}
				}
			}
		}
		if(!empty($driver_version) && $with_version && $driver_version != '0.0.0')
		{
			$display_driver .= ' ' . $driver_version;
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

		if($info == null)
		{
			$info = phodevi_parser::software_glxinfo_version();

			if($info && ($pos = strpos($info, ' ')) != false && strpos($info, 'Mesa') === false)
			{
				$info = substr($info, 0, $pos);
			}

			$renderer = phodevi_parser::read_glx_renderer();

			if($renderer && ($s = strpos($renderer, 'Gallium')) !== false)
			{
				$gallium = substr($renderer, $s);
				$gallium = substr($gallium, 0, strpos($gallium, ' ', strpos($gallium, '.')));
				$info .= ' ' . $gallium . '';
			}

			if($renderer && ($s = strpos($renderer, 'LLVM ')) !== false)
			{
				$llvm = substr($renderer, $s);
				$llvm = substr($llvm, 0, strpos($llvm, ')'));
				if(!empty($llvm) && phodevi::read_property('system', 'kernel'))
				{
					// Mesa Oibaf PPA for example includes kernel version but not important since PTS already reports kernel
					$llvm = str_replace(array(',', phodevi::read_property('system', 'kernel')), '', $llvm);
				}
				$info .= ' (' . trim($llvm) . ')';
			}
		}

		return $info;
	}
	public static function sw_vulkan_driver()
	{
		// Vulkan driver/version
		$info = null;

		if(isset(phodevi::$vfs->vulkaninfo))
		{
			if(($pos = strpos(phodevi::$vfs->vulkaninfo, 'Vulkan API Version:')) !== false)
			{
				$info = substr(phodevi::$vfs->vulkaninfo, $pos + 20);
				$info = trim(substr($info, 0, strpos($info, "\n")));
			}
			else if(($pos = strpos(phodevi::$vfs->vulkaninfo, 'apiVersion')) !== false)
			{
				$apiv = substr(phodevi::$vfs->vulkaninfo, $pos);
				$apiv = trim(substr($apiv, 0, strpos($apiv, ")\n")));
				$apiv = trim(substr($apiv, strpos($apiv, '(') + 1));
				if(pts_strings::is_version($apiv))
				{
					$info = $apiv;
				}
			}
		}
		/*
		if($info == null)
		{
			// A less than ideal fallback for some detection now
			foreach(array_merge(pts_file_io::glob('/etc/vulkan/icd.d/*.json'), pts_file_io::glob('/usr/share/vulkan/icd.d/*.json')) as $icd_json)
			{
				$icd_json = json_decode(file_get_contents($icd_json), true);

				if(isset($icd_json['ICD']['api_version']) && !empty($icd_json['ICD']['api_version']))
				{
					$info = trim($icd_json['ICD']['api_version']);
					break;
				}
			}
		}
		*/

		return $info;
	}
	public static function sw_opencl_driver()
	{
		// OpenCL driver/version
		$info = array();

		if(isset(phodevi::$vfs->clinfo))
		{
			$sea = phodevi::$vfs->clinfo;
			while(($pos = strpos($sea, 'Platform Version')) != false)
			{
				$sea = substr($sea, $pos + 18);
				$info[] = trim(substr($sea, 0, strpos($sea, "\n")));
			}
		}

		return implode(' + ', $info);
	}
	public static function sw_opengl_vendor()
	{
		// OpenGL version
		$info = null;

		if(pts_client::executable_in_path('glxinfo'))
		{
			$info = shell_exec('glxinfo 2>&1 | grep vendor');

			if($info && ($pos = strpos($info, 'OpenGL vendor string:')) !== false)
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
	public static function sw_compiler_build_configuration($compiler)
	{
		$cc = shell_exec($compiler . ' -v 2>&1');

		if(($t = stripos($cc, 'Configured with: ')) !== false)
		{
			$cc = substr($cc, ($t + 18));
			$cc = substr($cc, 0, strpos($cc, PHP_EOL));
			$cc = explode(' ', $cc);
			array_shift($cc); // this should just be the configure call (i.e. ../src/configure)

			$drop_arguments = array(
				'--with-pkgversion=',
				'--with-bugurl=',
				'--prefix=',
				'--program-suffix=',
				'--libexecdir=',
				'--infodir=',
				'--libdir=',
				'--with-cloog=',
				'--with-isl=',
				'--with-java-home=',
				'--manddir=',
				'--with-ecj-jar=',
				'--with-jvm-jar-dir=',
				'--with-jvm-root-dir=',
				'--with-sysroot=',
				'--with-gxx-include-dir=',
				'--with-system-zlib',
				'--enable-linker-build-id',
				'--without-included-gettext'
				);

			foreach($cc as $i => $argument)
			{
				$arg_length = strlen($argument);
				if($argument[0] != '-')
				{
					unset($cc[$i]);
				}
				else
				{
					foreach($drop_arguments as $check_to_drop)
					{
						$len = strlen($check_to_drop);

						if($len <= $arg_length && substr($argument, 0, $len) == $check_to_drop)
						{
							unset($cc[$i]);
						}
					}
				}
			}

			sort($cc);
			$cc = implode(' ', $cc);
		}
		else if(($t = stripos($cc, 'clang')) !== false)
		{
			$cc = null;

			// Clang doesn't report "configured with" but has other useful tid-bits...
			if(($c = pts_client::executable_in_path('llvm-ld')) || ($c = pts_client::executable_in_path('llvm-link')))
			{
				$llvm_ld = shell_exec($c . ' -version 2>&1');
				/*
				EXAMPLE OUTPUT:
					LLVM (http://llvm.org/):
					  LLVM version 3.1svn
					  Optimized build.
					  Built Mar 23 2012 (08:53:34).
					  Default target: x86_64-unknown-linux-gnu
					  Host CPU: corei7-avx
				*/

				if(stripos($llvm_ld, 'build') && (stripos($llvm_ld, 'host') || stripos($llvm_ld, 'target')))
				{
					$llvm_ld = explode(PHP_EOL, $llvm_ld);

					if(stripos($llvm_ld[0], 'http://'))
					{
						array_shift($llvm_ld);
					}
					if(stripos($llvm_ld[0], 'version'))
					{
						array_shift($llvm_ld);
					}

					foreach($llvm_ld as $i => &$line)
					{
						$line = trim($line);
						if(substr($line, -1) == '.')
						{
							$line = substr($line, 0, -1);
						}

						if($line == null)
						{
							unset($llvm_ld[$i]);
						}
					}

					$cc = implode('; ', $llvm_ld);
				}
			}

		}
		else
		{
			$cc = null;
		}

		return $cc;
	}
	public static function sw_dri_display_driver()
	{
		$dri_driver = false;

		if(is_file('/proc/driver/nvidia/version'))
		{
			$dri_driver = 'nvidia';
		}
		else if(is_file('/sys/class/drm/card0/device/vendor'))
		{
			$vendor_id = pts_file_io::file_get_contents('/sys/class/drm/card0/device/vendor');

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

		if(strpos($java_version, 'not found') == false && (stripos($java_version, 'Java') !== false || stripos($java_version, 'jdk') !== false))
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

		if(($p = pts_client::executable_in_path('python')) != false)
		{
			$python_version = trim(shell_exec(escapeshellarg($p) . ' -V 2>&1'));
		}
		if(($p = pts_client::executable_in_path('python3')) != false)
		{
			$python3_version = trim(shell_exec(escapeshellarg($p) . ' -V 2>&1'));
			if($python3_version != $python_version)
			{
				$python_version .= ($python_version != null ? ' + ' : null) . $python3_version;
			}
		}

		return $python_version;
	}
	public static function sw_wine_version()
	{
		$wine_version = null;

		if((($use_wine = getenv('USE_WINE')) !== false || ($use_wine = getenv('WINE_VERSION')) !== false) && (is_executable($use_wine) || ($use_wine = pts_client::executable_in_path($use_wine)) !== false))
		{
			$wine_version = trim(shell_exec($use_wine . ' --version 2>&1'));
		}
		else if(pts_client::executable_in_path('wine') != false)
		{
			$wine_version = trim(shell_exec('wine --version 2>&1'));
		}
		else if(pts_client::executable_in_path('winecfg.exe') != false && getenv('WINE_VERSION'))
		{
			$wine_version = trim(getenv('WINE_VERSION'));

			if(stripos($wine_version, 'wine') === false)
			{
				$wine_version = 'wine-' . $wine_version;
			}
		}

		return $wine_version;
	}
	public static function battery()
	{
		$batteries = array();
		if(phodevi::is_linux())
		{
			foreach(pts_file_io::glob('/sys/class/power_supply/BAT*/model_name') as $bat_path)
			{
				$bat_model = pts_file_io::file_get_contents($bat_path);
				$bat_dir = dirname($bat_path);
				$bat_manufacturer = is_file($bat_dir . '/manufacturer') ? pts_file_io::file_get_contents($bat_dir . '/manufacturer') : null;
				if(!empty($bat_model))
				{
					$batteries[] = trim($bat_manufacturer . ' ' . $bat_model);
				}
			}
		}

		return implode(' + ', $batteries);
	}
}

?>
