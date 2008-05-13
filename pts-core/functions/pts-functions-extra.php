<?php

/*
	Phoronix Test Suite "Trondheim"
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-functions-extra.php: "Extra" functions needed for some operations.
*/

function pts_remove_saved_result($identifier)
{
	$return_value = false;

	if(is_file(SAVE_RESULTS_DIR . $identifier . "/composite.xml"))
	{
		@unlink(SAVE_RESULTS_DIR . $identifier . "/composite.xml");

		foreach(glob(SAVE_RESULTS_DIR . $identifier . "/result-graphs/*.png") as $remove_file)
			@unlink($remove_file);

		foreach(glob(SAVE_RESULTS_DIR . $identifier . "/test-*.xml") as $remove_file)
			@unlink($remove_file);

		@unlink(SAVE_RESULTS_DIR . $identifier . "/pts-results-viewer.xsl");
		@rmdir(SAVE_RESULTS_DIR . $identifier . "/result-graphs/");
		@rmdir(SAVE_RESULTS_DIR . $identifier);
		echo "Removed: $identifier\n";
		$return_value = true;
	}
	return $return_value;
}
function pts_tests_in_suite($object)
{
	$type = pts_test_type($object);
	$tests = array();

	if($type == "TEST_SUITE")
	{
		$xml_parser = new tandem_XmlReader(file_get_contents(XML_SUITE_DIR . $object . ".xml"));
		$suite_benchmarks = array_unique($xml_parser->getXMLArrayValues(P_SUITE_TEST_NAME));

		foreach($suite_benchmarks as $benchmark)
			foreach(pts_tests_in_suite($benchmark) as $sub_test)
				array_push($tests, $sub_test);
	}
	else if($type == "BENCHMARK")
		return array($object);

	return array_unique($tests);
}

?>
