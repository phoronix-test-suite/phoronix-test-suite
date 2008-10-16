<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-functions_tests.php: Functions needed for some test parameters

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


function pts_test_needs_updated_install($identifier)
{
	// Checks if test needs updating
	return !is_file(TEST_ENV_DIR . $identifier . "/pts-install.xml")  || !pts_version_comparable(pts_test_profile_version($identifier), pts_test_installed_profile_version($identifier)) || pts_test_checksum_installer($identifier) != pts_test_installed_checksum_installer($identifier) || pts_test_installed_system_identifier($identifier) != pts_system_identifier_string();
}
function pts_test_checksum_installer($identifier)
{
	// Calculate installed checksum
	$md5_checksum = "";

	if(is_file(pts_location_test_resources($identifier) . "install.php"))
	{
		$md5_checksum = md5_file(pts_location_test_resources($identifier) . "install.php");
	}
	else if(is_file(pts_location_test_resources($identifier) . "install.sh"))
	{
		$md5_checksum = md5_file(pts_location_test_resources($identifier) . "install.sh");
	}

	return $md5_checksum;
}
function pts_test_installed_checksum_installer($identifier)
{
	// Read installer checksum of installed tests
	$version = "";

	if(is_file(TEST_ENV_DIR . $identifier . "/pts-install.xml"))
	{
	 	$xml_parser = new tandem_XmlReader(TEST_ENV_DIR . $identifier . "/pts-install.xml", false);
		$version = $xml_parser->getXMLValue(P_INSTALL_TEST_CHECKSUM);
	}

	return $version;
}
function pts_test_installed_system_identifier($identifier)
{
	// Read installer checksum of installed tests
	$value = "";

	if(is_file(TEST_ENV_DIR . $identifier . "/pts-install.xml"))
	{
	 	$xml_parser = new tandem_XmlReader(TEST_ENV_DIR . $identifier . "/pts-install.xml", false);
		$value = $xml_parser->getXMLValue(P_INSTALL_TEST_SYSIDENTIFY);
	}

	return $value;
}
function pts_test_profile_version($identifier)
{
	// Checks PTS profile version
	$version = "";

	if(is_test($identifier))
	{
	 	$xml_parser = new pts_test_tandem_XmlReader(pts_location_test($identifier));
		$version = $xml_parser->getXMLValue(P_TEST_PTSVERSION);
	}

	return $version;
}
function pts_test_installed_profile_version($identifier)
{
	// Checks installed version
	$version = "";

	if(is_file(TEST_ENV_DIR . $identifier . "/pts-install.xml"))
	{
	 	$xml_parser = new tandem_XmlReader(TEST_ENV_DIR . $identifier . "/pts-install.xml", false);
		$version = $xml_parser->getXMLValue(P_INSTALL_TEST_VERSION);
	}

	return $version;
}
function pts_test_generate_install_xml($identifier)
{
	// Generate an install XML for pts-install.xml
	/*$xml_writer = new tandem_XmlWriter();

	$xml_writer->addXmlObject(P_INSTALL_TEST_NAME, 1, $identifier);
	$xml_writer->addXmlObject(P_INSTALL_TEST_VERSION, 1, pts_test_profile_version($identifier));
	$xml_writer->addXmlObject(P_INSTALL_TEST_CHECKSUM, 1, pts_test_checksum_installer($identifier));
	$xml_writer->addXmlObject(P_INSTALL_TEST_SYSIDENTIFY, 1, pts_system_identifier_string());
	$xml_writer->addXmlObject(P_INSTALL_TEST_INSTALLTIME, 2, date("Y-m-d H:i:s"));
	$xml_writer->addXmlObject(P_INSTALL_TEST_LASTRUNTIME, 2, "0000-00-00 00:00:00");
	$xml_writer->addXmlObject(P_INSTALL_TEST_TIMESRUN, 2, "0");

	file_put_contents(TEST_ENV_DIR . $identifier . "/pts-install.xml", $xml_writer->getXML());*/
	return pts_test_refresh_install_xml($identifier, 0, true);
}
function pts_test_refresh_install_xml($identifier, $this_test_duration = 0, $new_install = false)
{
	// Generate/refresh an install XML for pts-install.xml
 	$xml_parser = new tandem_XmlReader(TEST_ENV_DIR . $identifier . "/pts-install.xml", false);
	$xml_writer = new tandem_XmlWriter();

	$test_duration = $xml_parser->getXMLValue(P_INSTALL_TEST_AVG_RUNTIME);
	if(!is_numeric($test_duration))
	{
		$test_duration = $this_test_duration;
	}
	if(is_numeric($this_test_duration) && $this_test_duration > 0)
	{
		$test_duration = ceil((($test_duration * $xml_parser->getXMLValue(P_INSTALL_TEST_TIMESRUN)) + $this_test_duration) / ($xml_parser->getXMLValue(P_INSTALL_TEST_TIMESRUN) + 1));
	}

	$test_version = $xml_parser->getXMLValue(P_INSTALL_TEST_VERSION);
	if(empty($test_version) || $new_install)
	{
		$test_version = pts_test_profile_version($identifier);
	}

	$test_checksum = $xml_parser->getXMLValue(P_INSTALL_TEST_CHECKSUM);
	if(empty($test_checksum) || $new_install)
	{
		$test_checksum = pts_test_checksum_installer($identifier);
	}

	$sys_identifier = $xml_parser->getXMLValue(P_INSTALL_TEST_SYSIDENTIFY);
	if(empty($sys_identifier) || $new_install)
	{
		$sys_identifier = pts_system_identifier_string();
	}

	$install_time = $xml_parser->getXMLValue(P_INSTALL_TEST_INSTALLTIME);
	if(empty($install_time))
	{
		$install_time = date("Y-m-d H:i:s");
	}

	$times_run = $xml_parser->getXMLValue(P_INSTALL_TEST_TIMESRUN);
	if($new_install && empty($times_run))
	{
		$times_run = 0;
	}
	if(!$new_install)
		$times_run++;

	$xml_writer->addXmlObject(P_INSTALL_TEST_NAME, 1, $identifier);
	$xml_writer->addXmlObject(P_INSTALL_TEST_VERSION, 1, $test_version);
	$xml_writer->addXmlObject(P_INSTALL_TEST_CHECKSUM, 1, $test_checksum);
	$xml_writer->addXmlObject(P_INSTALL_TEST_SYSIDENTIFY, 1, $sys_identifier);
	$xml_writer->addXmlObject(P_INSTALL_TEST_INSTALLTIME, 2, $install_time);
	$xml_writer->addXmlObject(P_INSTALL_TEST_LASTRUNTIME, 2, date("Y-m-d H:i:s"));
	$xml_writer->addXmlObject(P_INSTALL_TEST_TIMESRUN, 2, $times_run);
	$xml_writer->addXmlObject(P_INSTALL_TEST_AVG_RUNTIME, 2, $test_duration, 2);

	file_put_contents(TEST_ENV_DIR . $identifier . "/pts-install.xml", $xml_writer->getXML());
}
function pts_test_name_to_identifier($name)
{
	// Convert test name to identifier
	$identifier = false;

	if(!empty($name))
	{
		foreach(glob(XML_PROFILE_DIR . "*.xml") as $test_profile_file)
		{
		 	$xml_parser = new tandem_XmlReader($test_profile_file);

			if($xml_parser->getXMLValue(P_TEST_TITLE) == $name)
			{
				$identifier = basename($test_profile_file, ".xml");
			}
		}
	}

	return $identifier;
}
function pts_test_identifier_to_name($identifier)
{
	// Convert identifier to test name
	$name = false;

	if(!empty($identifier) && is_test($identifier))
	{
	 	$xml_parser = new pts_test_tandem_XmlReader(pts_location_test($identifier));
		$name = $xml_parser->getXMLValue(P_TEST_TITLE);
	}

	return $name;
}
function pts_estimated_download_size($identifier)
{
	// Estimate the size of files to be downloaded
	$estimated_size = 0;

	foreach(pts_contained_tests($identifier, true) as $test)
	{
	 	$xml_parser = new pts_test_tandem_XmlReader(pts_location_test($test));
		$this_size = $xml_parser->getXMLValue(P_TEST_DOWNLOADSIZE); // TODO: The DownloadSize tag has been deprecates as of Phoronix Test Suite 1.4.0

		if(!empty($this_size) && is_numeric($this_size))
		{
			$estimated_size += $this_size;
		}
		else
		{
			// The work for calculating the download size post 1.4.0.
			if(is_file(pts_location_test_resources($test) . "downloads.xml"))
			{
				$xml_parser = new tandem_XmlReader(pts_location_test_resources($test) . "downloads.xml");
				$package_filesize_bytes = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_FILESIZE);
				$package_platform = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_PLATFORMSPECIFIC);
				$package_architecture = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_ARCHSPECIFIC);

				for($i = 0; $i < count($package_filesize_bytes); $i++)
				{

					$file_exempt = false;

					if(!empty($package_platform[$i]))
					{
						$platforms = explode(",", $package_platform[$i]);

						foreach($platforms as $key => $value)
						{
							$platforms[$key] = trim($value);
						}

						if(!in_array(OPERATING_SYSTEM, $platforms))
						{
							$file_exempt = true;
						}
					}
					if(!empty($package_architecture[$i]))
					{
						$architectures = explode(",", $package_architecture[$i]);

						foreach($architectures as $key => $value)
						{
							$architectures[$key] = trim($value);
						}

						$this_arch = kernel_arch();

						if(strlen($this_arch) > 3 && substr($this_arch, -2) == "86")
						{
							$this_arch = "x86";
						}

						if(!in_array($this_arch, $architectures))
						{
							$file_exempt = true;
						}
					}

					if(is_numeric($package_filesize_bytes[$i]) && !$file_exempt)
					{
						$estimated_size += pts_trim_double($package_filesize_bytes[$i] / 1048576);
					}
				}
			}
		}
	}

	return $estimated_size;
}
function pts_test_estimated_environment_size($identifier)
{
	// Estimate the environment size of a test or suite
	$estimated_size = 0;

	foreach(pts_contained_tests($identifier, true) as $test)
	{
	 	$xml_parser = new pts_test_tandem_XmlReader(pts_location_test($identifier));
		$this_size = $xml_parser->getXMLValue(P_TEST_ENVIRONMENTSIZE);

		if(!empty($this_size) && is_numeric($this_size))
		{
			$estimated_size += $this_size;
		}
	}

	return $estimated_size;
}
function pts_test_architecture_supported($identifier)
{
	// Check if the system's architecture is supported by a test
	$supported = true;

	if(is_test($identifier))
	{
	 	$xml_parser = new pts_test_tandem_XmlReader(pts_location_test($identifier));
		$archs = $xml_parser->getXMLValue(P_TEST_SUPPORTEDARCHS);

		if(!empty($archs))
		{
			$archs = explode(",", $archs);

			foreach($archs as $key => $value)
			{
				$archs[$key] = trim($value);
			}

			$this_arch = kernel_arch();

			if(strlen($this_arch) > 3 && substr($this_arch, -2) == "86")
			{
				$this_arch = "x86";
			}

			if(!in_array($this_arch, $archs))
			{
				$supported = false;
			}
		}
	}

	return $supported;
}
function pts_test_platform_supported($identifier)
{
	// Check if the system's OS is supported by a test
	$supported = true;

	if(is_test($identifier))
	{
	 	$xml_parser = new pts_test_tandem_XmlReader(pts_location_test($identifier));
		$platforms = $xml_parser->getXMLValue(P_TEST_SUPPORTEDPLATFORMS);
		$un_platforms = $xml_parser->getXMLValue(P_TEST_UNSUPPORTEDPLATFORMS);

		if(OPERATING_SYSTEM != "Unknown")
		{
			if(!empty($un_platforms))
			{
				$un_platforms = explode(",", $un_platforms);

				foreach($un_platforms as $key => $value)
				{
					$un_platforms[$key] = trim($value);
				}

				if(in_array(OPERATING_SYSTEM, $un_platforms))
				{
					$supported = false;
				}
			}
			if(!empty($platforms))
			{
				$platforms = explode(",", $platforms);

				foreach($platforms as $key => $value)
				{
					$platforms[$key] = trim($value);
				}

				if(!in_array(OPERATING_SYSTEM, $platforms))
				{
					$supported = false;
				}
			}
		}
	}

	return $supported;
}
function pts_test_version_supported($identifier)
{
	// Check if the test profile's version is compatible with pts-core
	$supported = true;

	if(is_test($identifier))
	{
	 	$xml_parser = new pts_test_tandem_XmlReader(pts_location_test($identifier));
		$requires_core_version = $xml_parser->getXMLValue(P_TEST_SUPPORTS_COREVERSION);

		$supported = pts_test_version_compatible($requires_core_version);
	}

	return $supported;
}
function pts_suite_supported($identifier)
{
	$tests = pts_contained_tests($identifier, true);
	$supported_size = $original_size = count($tests);

	for($i = 0; $i < $original_size; $i++)
	{
		if(!pts_test_supported(@$tests[$i]))
		{
			$supported_size--;
		}
	}

	if($supported_size == 0)
	{
		$return_code = 0;
	}
	else if($supported_size != $original_size)
	{
		$return_code = 1;
	}
	else
	{
		$return_code = 2;
	}

	return $return_code;
}
function pts_test_supported($identifier)
{
	return pts_test_architecture_supported($identifier) && pts_test_platform_supported($identifier) && pts_test_version_supported($identifier);
}
function pts_available_tests_array()
{
	$tests = glob(XML_PROFILE_DIR . "*.xml");
	$local_tests = glob(XML_PROFILE_LOCAL_DIR . "*.xml");
	$tests = array_unique(array_merge($tests, $local_tests));
	asort($tests);

	for($i = 0; $i < count($tests); $i++)
	{
		$tests[$i] = basename($tests[$i], ".xml");
	}

	return $tests;
}
function pts_installed_tests_array()
{
	$tests = glob(TEST_ENV_DIR . "*/pts-install.xml");

	for($i = 0; $i < count($tests); $i++)
	{
		$install_file_arr = explode("/", $tests[$i]);
		$tests[$i] = $install_file_arr[count($install_file_arr) - 2];
	}

	return $tests;
}
function pts_available_suites_array()
{
	$suites = glob(XML_SUITE_DIR . "*.xml");
	$local_suites = glob(XML_SUITE_LOCAL_DIR . "*.xml");
	$suites = array_unique(array_merge($suites, $local_suites));
	asort($suites);

	for($i = 0; $i < count($suites); $i++)
	{
		$suites[$i] = basename($suites[$i], ".xml");
	}

	return $suites;
}
function pts_test_version_compatible($version_compare = "")
{
	$compatible = true;

	if(!empty($version_compare))
	{
		$current = preg_replace("/[^0-9]/", "", PTS_VERSION);

		$version_compare = explode("-", $version_compare);	
		$support_begins = preg_replace("/[^0-9]/", "", trim($version_compare[0]));

		if(count($version_compare) == 2)
		{
			$support_ends = trim($version_compare[1]);
		}
		else
		{
			$support_ends = PTS_VERSION;
		}

		$support_ends = preg_replace("/[^0-9]/", "", $support_ends);

		if($current >= $support_begins && $current <= $support_ends)
		{
			$compatible = true;
		}
		else
		{
			$compatible = false;
		}
	}

	return $compatible;	
}

?>
