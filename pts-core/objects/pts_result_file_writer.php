<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2015, Phoronix Media
	Copyright (C) 2010 - 2015, Michael Larabel

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

class pts_result_file_writer
{
	public static function result_file_to_xml(&$result_file, $to = null)
	{
		$xml_writer = new nye_XmlWriter((PTS_IS_CLIENT ? 'pts-results-viewer.xsl' : null));
		$xml_writer->addXmlNode('PhoronixTestSuite/Generated/Title', $result_file->get_title());
		$xml_writer->addXmlNode('PhoronixTestSuite/Generated/LastModified', date('Y-m-d H:i:s'));
		$xml_writer->addXmlNode('PhoronixTestSuite/Generated/TestClient', pts_title(true));
		$xml_writer->addXmlNode('PhoronixTestSuite/Generated/Description', $result_file->get_description());
		$xml_writer->addXmlNodeWNE('PhoronixTestSuite/Generated/Notes', $result_file->get_notes());
		$xml_writer->addXmlNodeWNE('PhoronixTestSuite/Generated/InternalTags', $result_file->get_internal_tags());
		$xml_writer->addXmlNodeWNE('PhoronixTestSuite/Generated/ReferenceID', $result_file->get_reference_id());
		$xml_writer->addXmlNodeWNE('PhoronixTestSuite/Generated/PreSetEnvironmentVariables', $result_file->get_preset_environment_variables());

		// Write the system hardware/software information
		foreach($result_file->get_systems() as $s)
		{
			$xml_writer->addXmlNode('PhoronixTestSuite/System/Identifier', $s->get_identifier());
			$xml_writer->addXmlNode('PhoronixTestSuite/System/Hardware', $s->get_hardware());
			$xml_writer->addXmlNode('PhoronixTestSuite/System/Software', $s->get_software());
			$xml_writer->addXmlNode('PhoronixTestSuite/System/User', $s->get_username());
			$xml_writer->addXmlNode('PhoronixTestSuite/System/TimeStamp', $s->get_timestamp());
			$xml_writer->addXmlNode('PhoronixTestSuite/System/TestClientVersion', $s->get_client_version());
			$xml_writer->addXmlNode('PhoronixTestSuite/System/Notes', $s->get_notes());

			if(!defined('USER_PTS_CORE_VERSION') || USER_PTS_CORE_VERSION > 3722)
			{
				// Ensure that a supported result file schema is being written...
				// USER_PTS_CORE_VERSION is set by OpenBenchmarking.org so if the requested client is old, don't write this data to send back to their version
				$xml_writer->addXmlNodeWNE('PhoronixTestSuite/System/JSON', ($s->get_json() ? json_encode($s->get_json()) : null));
			}
		}

		foreach($result_file->get_result_objects() as $result_object)
		{
			$buffer_items = $result_object->test_result_buffer->get_buffer_items();

			if(count($buffer_items) == 0)
			{
				continue;
			}

			$xml_writer->addXmlNode('PhoronixTestSuite/Result/Identifier', $result_object->test_profile->get_identifier());
			$xml_writer->addXmlNode('PhoronixTestSuite/Result/Title', $result_object->test_profile->get_title());
			$xml_writer->addXmlNode('PhoronixTestSuite/Result/AppVersion', $result_object->test_profile->get_app_version());
			$xml_writer->addXmlNode('PhoronixTestSuite/Result/Arguments', $result_object->get_arguments());
			$xml_writer->addXmlNode('PhoronixTestSuite/Result/Description', $result_object->get_arguments_description());
			$xml_writer->addXmlNode('PhoronixTestSuite/Result/Scale', $result_object->test_profile->get_result_scale());
			$xml_writer->addXmlNode('PhoronixTestSuite/Result/Proportion', $result_object->test_profile->get_result_proportion());
			$xml_writer->addXmlNode('PhoronixTestSuite/Result/DisplayFormat', $result_object->test_profile->get_display_format());

			foreach($buffer_items as $i => &$buffer_item)
			{
				$xml_writer->addXmlNode('PhoronixTestSuite/Result/Data/Entry/Identifier', $buffer_item->get_result_identifier());
				$xml_writer->addXmlNode('PhoronixTestSuite/Result/Data/Entry/Value', $buffer_item->get_result_value());
				$xml_writer->addXmlNode('PhoronixTestSuite/Result/Data/Entry/RawString', $buffer_item->get_result_raw());

				if(!defined('USER_PTS_CORE_VERSION') || USER_PTS_CORE_VERSION > 3722)
				{
					// Ensure that a supported result file schema is being written...
					// USER_PTS_CORE_VERSION is set by OpenBenchmarking.org so if the requested client is old, don't write this data to send back to their version
					$xml_writer->addXmlNodeWNE('PhoronixTestSuite/Result/Data/Entry/JSON', ($buffer_item->get_result_json() ? json_encode($buffer_item->get_result_json()) : null));
				}
			}
		}

		return $to == null ? $xml_writer->getXML() : $xml_writer->saveXMLFile($to);
	}
}

?>
