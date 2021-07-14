<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2012 - 2018, Phoronix Media
	Copyright (C) 2012 - 2018, Michael Larabel

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
			$xml = pts_exdep_generic_parser::get_external_dependency_path() . 'xml/' . $identifier . '-packages.xml';
			$xml_options = LIBXML_COMPACT | LIBXML_PARSEHUGE;
			$xml = simplexml_load_file($xml, 'SimpleXMLElement', $xml_options);

			$this->struct['external-dependencies']['name'] = isset($xml->ExternalDependencies->Information->Name) ? $xml->ExternalDependencies->Information->Name->__toString() : null;
			$this->struct['external-dependencies']['package_manager'] = isset($xml->ExternalDependencies->Information->PackageManager) ? $xml->ExternalDependencies->Information->PackageManager->__toString() : null;
			$this->struct['external-dependencies']['warn_on_unmet_dependencies'] = isset($xml->ExternalDependencies->Information->WarnOnUnmetDependencies) ? $xml->ExternalDependencies->Information->WarnOnUnmetDependencies->__toString() : '';

			if(isset($xml->ExternalDependencies) && isset($xml->ExternalDependencies->Package))
			{
				foreach($xml->ExternalDependencies->Package as $pkg)
				{
					$generic_package = isset($pkg->GenericName) ? $pkg->GenericName->__toString() : null;
					if(empty($generic_package))
					{
						continue;
					}

					$os_version_specific = isset($pkg->VersionSpecific) ? $pkg->VersionSpecific->__toString() : null;
					$os_version_compliant = empty($os_version_specific) || in_array(phodevi::read_property('system', 'os-version'), pts_strings::comma_explode($os_version_specific));
					if($os_version_compliant == false)
					{
						continue;
					}

					$distro_package = isset($pkg->PackageName) ? $pkg->PackageName->__toString() : null;
					$file_check = isset($pkg->FileCheck) ? $pkg->FileCheck->__toString() : null;
					$arch_specific = isset($pkg->ArchitectureSpecific) ? $pkg->ArchitectureSpecific->__toString() : null;
					$this->struct['external-dependencies']['packages'][$generic_package] = $this->get_package_format($distro_package, $file_check, $arch_specific);
				}
			}

			$aliases = isset($xml->ExternalDependencies->Information->Aliases) ? $xml->ExternalDependencies->Information->Aliases->__toString() : null;
			if($aliases != null)
			{
				$aliases = pts_strings::trim_explode(',', $aliases);

				foreach($aliases as $alias)
				{
					if($alias != null)
					{
						$this->struct['external-dependencies']['aliases'][] = $alias;
					}
				}
			}
		}
	}
	public function skip_warning_on_unmet_dependencies()
	{
		return $this->struct['external-dependencies']['warn_on_unmet_dependencies'] && strtolower($this->struct['external-dependencies']['warn_on_unmet_dependencies']) == 'false';
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
			$alias = strtolower(str_replace(' ', '', $alias));
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
