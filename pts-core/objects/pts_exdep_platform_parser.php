<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2012 - 2013, Phoronix Media
	Copyright (C) 2012 - 2013, Michael Larabel

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

class pts_exdep_platform_parser
{
	public $struct;

	public function __construct($identifier = null)
	{
		$this->struct = array('external-dependencies' => array('name' => null, 'package_manager' => null, 'aliases' => array(), 'packages' => array()));

		if(PTS_IS_CLIENT)
		{
			$xml = PTS_EXDEP_PATH . 'xml/' . $identifier . '-packages.xml';
			$xml_parser = new nye_XmlReader($xml);

			$this->struct['external-dependencies']['name'] = $xml_parser->getXMLValue('PhoronixTestSuite/ExternalDependencies/Information/Name');
			$this->struct['external-dependencies']['package_manager'] = $xml_parser->getXMLValue('PhoronixTestSuite/ExternalDependencies/Information/PackageManager');
			$generic_package = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ExternalDependencies/Package/GenericName');
			$distro_package = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ExternalDependencies/Package/PackageName');
			$file_check = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ExternalDependencies/Package/FileCheck');
			$arch_specific = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ExternalDependencies/Package/ArchitectureSpecific');
			$os_version_specific = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ExternalDependencies/Package/VersionSpecific');
			$os_version = phodevi::read_property('system', 'os-version');

			foreach(array_keys($generic_package) as $i)
			{
				if(empty($generic_package[$i]))
				{
					continue;
				}
				$os_version_compliant = empty($os_version_specific) || in_array($os_version, pts_strings::comma_explode($os_version_specific));
				if($os_version_compliant == false)
				{
					continue;
				}

				$this->struct['external-dependencies']['packages'][$generic_package[$i]] = $this->get_package_format($distro_package[$i], $file_check[$i], $arch_specific[$i]);
			}

			$aliases = $xml_parser->getXMLValue('PhoronixTestSuite/ExternalDependencies/Information/Aliases');

			if($aliases != null)
			{
				$aliases = pts_strings::trim_explode(',', $aliases);

				foreach($aliases as $alias)
				{
					if($alias != null)
					{
						array_push($this->struct['external-dependencies']['aliases'], $alias);
					}
				}
			}
		}
	}
	public function get_package_format($distro_package = null, $file_check = null, $arch_specific = null)
	{
		if(!is_array($arch_specific))
		{
			$arch_specific = pts_strings::comma_explode($arch_specific);
		}

		return array(
			'os_package' => $distro_package,
			'file_check' => $file_check,
			'arch_specific' => $arch_specific
			);
	}
	public function get_name()
	{
		return $this->struct['external-dependencies']['name'];
	}
	public function get_package_manager()
	{
		return $this->struct['external-dependencies']['package_manager'];
	}
	public function get_aliases()
	{
		$aliases = $this->struct['external-dependencies']['aliases'];

		foreach($aliases as &$alias)
		{
			$alias = strtolower(str_replace(' ', null, $alias));
		}

		return $aliases;
	}
	public function get_aliases_formatted()
	{
		return $this->struct['external-dependencies']['aliases'];
	}
	public function is_package($package)
	{
		return isset($this->struct['external-dependencies']['packages'][$package]);
	}
	public function get_available_packages()
	{
		return array_keys($this->struct['external-dependencies']['packages']);
	}
	public function get_package_data($package)
	{
		return $this->is_package($package) ? $this->struct['external-dependencies']['packages'][$package] : $this->get_package_format();
	}
}


?>
