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

class pts_exdep_generic_parser
{
	public $struct;

	public function __construct()
	{
		$this->struct = array('external-dependencies' => array('generic-packages' => array()));

		if(PTS_IS_CLIENT)
		{
			$xml_options = LIBXML_COMPACT | LIBXML_PARSEHUGE;
			$xml = simplexml_load_file(pts_exdep_generic_parser::get_external_dependency_path() . 'xml/generic-packages.xml', 'SimpleXMLElement', $xml_options);

			if(isset($xml->ExternalDependencies) && isset($xml->ExternalDependencies->Package))
			{
				foreach($xml->ExternalDependencies->Package as $pkg)
				{
					$generic_name = isset($pkg->GenericName) ? $pkg->GenericName->__toString() : null;
					$title = isset($pkg->Title) ? $pkg->Title->__toString() : null;
					$file_check = isset($pkg->FileCheck) ? $pkg->FileCheck->__toString() : null;
					$possible_packages = isset($pkg->PossibleNames) ? $pkg->PossibleNames->__toString() : null;
					$this->struct['external-dependencies']['generic-packages'][$generic_name] = $this->get_package_format($title, $file_check, $possible_packages);
				}
			}
		}
	}
	public static function get_external_dependency_path()
	{
		return PTS_CORE_PATH . 'external-test-dependencies/';
	}
	/**
	 * @param string      $title
	 * @param string|null $file_check
	 * @param string|null $possible_packages
	 *
	 * @return (null|string)[]
	 */
	public function get_package_format($title = null, $file_check = null, $possible_packages = null)
	{
		return array(
			'title' => $title,
			'file_check' => $file_check,
			'possible_packages' => $possible_packages
			);
	}
	public function get_available_packages()
	{
		return array_keys($this->struct['external-dependencies']['generic-packages']);
	}
	/**
	 * @param string $package
	 *
	 * @return bool
	 */
	public function is_package($package)
	{
		return isset($this->struct['external-dependencies']['generic-packages'][$package]);
	}
	/**
	 * @param string $package
	 *
	 * @return string[]
	 */
	public function get_package_data($package)
	{
		return $this->is_package($package) ? $this->struct['external-dependencies']['generic-packages'][$package] : $this->get_package_format();
	}
	public function get_vendors_list()
	{
		$package_files = pts_file_io::glob(pts_exdep_generic_parser::get_external_dependency_path() . 'xml/*-packages.xml');

		foreach($package_files as &$file)
		{
			$file = basename(substr($file, 0, strpos($file, '-packages.xml')));
		}

		return $package_files;
	}
	public function get_vendors_list_formatted()
	{
		$vendors = array();

		foreach($this->get_vendors_list() as $vendor)
		{
			$exdep_platform_parser = new pts_exdep_platform_parser($vendor);
			$name = $exdep_platform_parser->get_name();

			if($name)
			{
				$vendors[] = $name;
			}
		}

		return $vendors;
	}
	public function get_vendor_aliases()
	{
		$alias_list = array();

		foreach($this->get_vendors_list() as $vendor)
		{
			$exdep_platform_parser = new pts_exdep_platform_parser($vendor);
			$aliases = $exdep_platform_parser->get_aliases();

			foreach($aliases as $alias)
			{
				$alias_list[] = $alias;
			}
		}

		return $alias_list;
	}
	public function get_vendor_aliases_formatted()
	{
		$alias_list = array();

		foreach($this->get_vendors_list() as $vendor)
		{
			$exdep_platform_parser = new pts_exdep_platform_parser($vendor);
			$aliases = $exdep_platform_parser->get_aliases_formatted();

			foreach($aliases as $alias)
			{
				$alias_list[] = $alias;
			}
		}

		return $alias_list;
	}
}


?>
