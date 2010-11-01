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

// Load compatibility definitions
pts_load_xml_definitions("compatibility.xml");

class pts_compatibility
{
	public static function pts_convert_pre_pts_26_module_settings()
	{
		/*
			Prior to Phoronix Test Suite 2.6 all user module configuration options were stored in ~/.phoronix-test-suite/modules-config.xml
			In PTS 2.6+ the settings for each module are stored in ~/.phoronix-test-suite/modules-data/<module-name>/module-settings.xml
			This function writes out any existing data in the old file to the new modules-data location
		*/

		if(!is_file(PTS_USER_DIR . "modules-config.xml"))
		{
			return false;
		}

		$module_config_parser = new tandem_XmlReader(PTS_USER_DIR . "modules-config.xml");
		$option_module = $module_config_parser->getXMLArrayValues(P_COMPAT_MODULE_OPTION_NAME);
		$option_identifier = $module_config_parser->getXMLArrayValues(P_COMPAT_MODULE_OPTION_IDENTIFIER);
		$option_value = $module_config_parser->getXMLArrayValues(P_COMPAT_MODULE_OPTION_VALUE);
		$module_settings = array();

		for($i = 0; $i < count($option_module); $i++)
		{
			$module_settings[$option_module[$i]][$option_identifier[$i]] = $option_value[$i];
		}

		foreach($module_settings as $module_name => &$module_option_group)
		{
			pts_module::module_config_save($module_name, $module_option_group);
		}

		pts_file_io::unlink(PTS_USER_DIR . "modules-config.xml");
	}
}

?>
