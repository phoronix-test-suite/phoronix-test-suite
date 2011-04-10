<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2011, Phoronix Media
	Copyright (C) 2010 - 2011, Michael Larabel

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

class pts_results_nye_XmlReader extends nye_XmlReader
{
	public function __construct($read_xml)
	{
		if(!isset($read_xml[1024]) && defined('PTS_SAVE_RESULTS_PATH') && is_file(PTS_SAVE_RESULTS_PATH . $read_xml . '/composite.xml'))
		{
			$read_xml = PTS_SAVE_RESULTS_PATH . $read_xml . '/composite.xml';
		}

		if(defined('PHOROMATIC_BUILD') && !isset($read_xml[1024]) && is_file($read_xml))
		{
			// Work around a nye_XmlReader parsing bug with early Phoromatic versions where \' was done
			$read_xml = file_get_contents($read_xml);
			$read_xml = substr($read_xml, strpos($read_xml, '<PhoronixTestSuite>'));
		}

		parent::__construct($read_xml);
	}
	public function validate()
	{
		// on failure get errors from libxml_get_errors();
		return $this->dom->schemaValidate(PTS_OPENBENCHMARKING_PATH . 'schemas/result-file.xsd');
	}
	protected function handleXmlZeroTagFallback($xml_tag, $value)
	{
		$legacy_spec = array(
			/* New Tag => Old Tag */
			// The below tags were changed during Phoronix Test Suite 3.0 Iveland
			'PhoronixTestSuite/Generated/Title' => 'PhoronixTestSuite/Suite/Title',
			'PhoronixTestSuite/Generated/Description' => 'PhoronixTestSuite/Suite/Description'
			);

		return isset($legacy_spec[$xml_tag]) ? $this->getXMLValue($legacy_spec[$xml_tag], $value) : $value;
	}	
	protected function handleXmlZeroTagArrayFallback($xml_tag, $value, $break_depth = -1)
	{
		$legacy_spec = array(
			/* New Tag => Old Tag */
			// The below tags were changed during Phoronix Test Suite 3.0 Iveland
			'PhoronixTestSuite/System/User' => 'PhoronixTestSuite/System/Author',
			'PhoronixTestSuite/System/Identifier' => 'PhoronixTestSuite/System/AssociatedIdentifiers',
			'PhoronixTestSuite/System/TimeStamp' => 'PhoronixTestSuite/System/TestDate',
			'PhoronixTestSuite/System/Notes' => 'PhoronixTestSuite/System/TestNotes',
			'PhoronixTestSuite/System/TestClientVersion' => 'PhoronixTestSuite/System/Version',
			'PhoronixTestSuite/Result/Identifier' => 'PhoronixTestSuite/Benchmark/TestName',
			'PhoronixTestSuite/Result/Title' => 'PhoronixTestSuite/Benchmark/Name',
			'PhoronixTestSuite/Result/Scale' => 'PhoronixTestSuite/Benchmark/Scale',
			'PhoronixTestSuite/Result/AppVersion' => 'PhoronixTestSuite/Benchmark/Version',
			'PhoronixTestSuite/Result/DisplayFormat' => 'PhoronixTestSuite/Benchmark/ResultFormat',
			'PhoronixTestSuite/Result/Proportion' => 'PhoronixTestSuite/Benchmark/Proportion',
			'PhoronixTestSuite/Result/Arguments' => 'PhoronixTestSuite/Benchmark/TestArguments',
			'PhoronixTestSuite/Result/Description' => 'PhoronixTestSuite/Benchmark/Attributes',
			'PhoronixTestSuite/Result/Data' => 'PhoronixTestSuite/Benchmark/Results',
			'PhoronixTestSuite/Result/Data/Entry/Identifier' => 'PhoronixTestSuite/Benchmark/Results/Group/Entry/Identifier',
			'PhoronixTestSuite/Result/Data/Entry/Value' =>  'PhoronixTestSuite/Benchmark/Results/Group/Entry/Value',
			'PhoronixTestSuite/Result/Data/Entry/RawString' =>  'PhoronixTestSuite/Benchmark/Results/Group/Entry/RawString'
			);

		return isset($legacy_spec[$xml_tag]) ? $this->getXMLArrayValues($legacy_spec[$xml_tag], $break_depth) : $value;
	}
}
?>
