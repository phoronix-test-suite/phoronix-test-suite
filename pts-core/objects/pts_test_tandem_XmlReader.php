<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2004-2008, Michael Larabel
	pts_test_tandem_XmlReader.php: The XML reading object for the Phoronix Test Suite with optimizations for handling test profiles

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

class pts_test_tandem_XmlReader extends tandem_XmlReader
{
	private function handleXmlZeroTagFallback($XML_TAG)
	{
		// Cascading Test Profiles for finding a tag within an XML file being extended by another XML file
		$fallback_value = $this->NO_TAG_FALLBACK_VALUE;

		if(!empty($this->XML_FILE_NAME))
		{
			$test_extends = $this->getXMLValue(P_TEST_CTPEXTENDS);

			if(!empty($test_extends) && is_test($test_extends))
			{
				$test_below_parser = new pts_test_tandem_XmlReader(pts_location_test($test_extends));
				$test_below_tag = $test_below_parserr->getXMLValue($XML_TAG);

				if(!empty($test_below_tag))
					$fallback_value = $test_below_tag;
			}
		}

		return $fallback_value;
	}
}
?>
