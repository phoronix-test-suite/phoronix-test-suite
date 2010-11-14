<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
	pts_results_tandem_XmlReader.php: The XML reading object for the Phoronix Test Suite for test results

	Additional Notes: A very simple XML parser with a few extras... Does not currently support attributes on tags, etc.
	A work in progress. This was originally designed for just some select needs in the past. No XML validation is done with this parser, etc.

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

pts_load_xml_definitions("result-file.xml");

class pts_results_tandem_XmlReader extends tandem_XmlReader
{
	public function __construct($read_xml)
	{
		$is_file = !isset($read_xml[1024]) && substr($read_xml, 0, 1) != "<" && is_file($read_xml);
		if($is_file == false && defined("PTS_SAVE_RESULTS_PATH") && is_file(PTS_SAVE_RESULTS_PATH . $read_xml . "/composite.xml"))
		{
			$read_xml = PTS_SAVE_RESULTS_PATH . $read_xml . "/composite.xml";
		}

		parent::__construct($read_xml);
	}

	function handleXmlZeroTagFallback($xml_tag)
	{
		$legacy_spec = array(
			/* New Tag => Old Tag */
			// The below tags were changed during Phoronix Test Suite 3.0 Iveland
			"PhoronixTestSuite/Generated/Title" => "PhoronixTestSuite/Suite/Title",
			"PhoronixTestSuite/Generated/Description" => "PhoronixTestSuite/Suite/Description"
			);

		if(isset($legacy_spec[$xml_tag]))
		{
			$value = $this->getXMLValue($legacy_spec[$xml_tag]);
		}
		else
		{
			$value = $this->tag_fallback_value;
		}

		return $value;
	}	
	function handleXmlZeroTagArrayFallback($xml_tag, $fallback)
	{
		$legacy_spec = array(
			/* New Tag => Old Tag */
			// The below tags were changed during Phoronix Test Suite 3.0 Iveland
			"PhoronixTestSuite/System/User" => "PhoronixTestSuite/System/Author",
			"PhoronixTestSuite/System/Identifier" => "PhoronixTestSuite/System/AssociatedIdentifiers",
			"PhoronixTestSuite/System/TimeStamp" => "PhoronixTestSuite/System/TestDate",
			"PhoronixTestSuite/System/Notes" => "PhoronixTestSuite/System/TestNotes",
			"PhoronixTestSuite/System/TestClientVersion" => "PhoronixTestSuite/System/Version",
			"PhoronixTestSuite/Result/Identifier" => "PhoronixTestSuite/Benchmark/TestName",
			"PhoronixTestSuite/Result/Title" => "PhoronixTestSuite/Benchmark/Name",
			"PhoronixTestSuite/Result/Scale" => "PhoronixTestSuite/Benchmark/Scale",
			"PhoronixTestSuite/Result/AppVersion" => "PhoronixTestSuite/Benchmark/Version",
			"PhoronixTestSuite/Result/ProfileVersion" => "PhoronixTestSuite/Benchmark/ProfileVersion",
			"PhoronixTestSuite/Result/DisplayFormat" => "PhoronixTestSuite/Benchmark/ResultFormat",
			"PhoronixTestSuite/Result/Proportion" => "PhoronixTestSuite/Benchmark/Proportion",
			"PhoronixTestSuite/Result/Arguments" => "PhoronixTestSuite/Benchmark/TestArguments",
			"PhoronixTestSuite/Result/ArgumentsDescription" => "PhoronixTestSuite/Benchmark/Attributes",
			"PhoronixTestSuite/Result/Data" => "PhoronixTestSuite/Benchmark/Results",
			"PhoronixTestSuite/Result/Data/Entry/Identifier" => "PhoronixTestSuite/Benchmark/Results/Group/Entry/Identifier",
			"PhoronixTestSuite/Result/Data/Entry/Value" =>  "PhoronixTestSuite/Benchmark/Results/Group/Entry/Value",
			"PhoronixTestSuite/Result/Data/Entry/RawString" =>  "PhoronixTestSuite/Benchmark/Results/Group/Entry/RawString"
			);

		if(isset($legacy_spec[$xml_tag]))
		{
			$value = $this->getArrayValues($legacy_spec[$xml_tag], $this->xml_data, false);

			if(empty($value))
			{
				$value = $fallback;
			}
		}
		else
		{
			$value = $fallback;
		}

		return $value;
	}
}
?>
