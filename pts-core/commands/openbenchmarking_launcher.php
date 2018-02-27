<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2011, Phoronix Media
	Copyright (C) 2011, Michael Larabel

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

class openbenchmarking_launcher implements pts_option_interface
{
	const doc_section = 'OpenBenchmarking.org';
	const doc_skip = true;
	const doc_description = 'This option is called automatically with the .openbenchmarking MIME file extension support for launching OpenBenchmarking.org operations.';

	public static function run($r)
	{
		if(isset($r[0]) && strpos($r[0], '.openbenchmarking') !== false && is_readable($r[0]))
		{
			// OpenBenchmarking.org launcher
			$dom = new DOMDocument();
			$dom->loadHTMLFile($r[0]);
			$requires_core_version = self::read_openbenchmarking_dom($dom, 'requires_core_version', PTS_CORE_VERSION);

			if(PTS_CORE_VERSION < $requires_core_version)
			{
				echo PHP_EOL . 'An incompatible OpenBenchmarking.org file was provided. You must upgrade the Phoronix Test Suite installation.' . PHP_EOL;
				return false;
			}

			$payload_type = self::read_openbenchmarking_dom($dom, 'payload_type');
			$payload = self::read_openbenchmarking_dom($dom, 'payload');

			switch($payload_type)
			{
				case 'benchmark':
					$to_benchmark = explode(' ', $payload);
					pts_test_installer::standard_install($to_benchmark);
					$run_manager = new pts_test_run_manager();
					$run_manager->standard_run($to_benchmark);
					break;

			}
		}
	}
	protected static function read_openbenchmarking_dom(&$dom, $id, $fallback = false)
	{
		$tag = $dom->getElementById($id);

		if($tag instanceof DOMElement && $tag->tagName == 'input')
		{
			$value = $tag->getAttribute('value');
		}
		else
		{
			$value = $fallback;
		}

		return $value;
	}
}

?>
