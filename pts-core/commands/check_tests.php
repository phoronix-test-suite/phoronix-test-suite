<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2020, Julie Tippett

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

/**
 * check_tests
 * 
 * Performs a check against the vendor on the XML elements in the test-profiles downloads.xml file. 
 * Checks performed include filename, filesize, SHA256 and MD5 hash.
 * 
 * Status is output to a PTS_OPENBENCHMARKING_SCRATCH_PATH/checkTestResults.json file.
 * 
 */
class check_tests implements pts_option_interface
{
	const doc_section = 'Information';
	const doc_description = 'This option will perform a check on one or more test profiles to determine if there have been any vendor changes to the filename, filesize, url location, md5 and sha256 checksums.';

	// Delimiter in the TESTED_FILES
	const DELIMITER = ":::";

	// Number of child process to spawn
	const CHILD_PROCS = 8;

	// Keys used in Assocative array to store vendor data
	const V_IDENTIFIER = 'identifier';			// name and version of the test profile
	const V_URL = 'url';						// url from which test profile is downloaded
	const V_DOWNLOAD_FILE = 'downloadFile';		// name of file downloaded. Usuall basename of url
	const V_STATUS = 'status';					// http status code for the download
	const V_DOWNLOAD_TIME = "downloadTime";		// complete time to connect to url and download
	const V_DOWNLOAD_SIZE = "downloadSize";		// size of downloaded file
	const V_DUPLICATE = "duplicate";			// indicates if we have already downloaded the file as part of an earlier test profile version
	const V_REDIRECT = "redirectTo";			// If a url is redirected, this is the original url location. V_URL will contain the redirection 
	
	// Don't use const for these as it breaks PHP 5.6
	// See https://stackoverflow.com/questions/10969342/parse-error-syntax-error-unexpected-expecting-or for details
	protected static $TESTED_FILES;
	protected static $JSON_FILE;
	protected static $DOWNLOADED_VENDOR_FILES;

	/**
	 * Determines if the test profile is valid. If invalid 'Invalid Arguement' Problem reported.
	 * */
	public static function argument_checks()
	{
		return array(
			new pts_argument_check('VARIABLE_LENGTH_MAYBE', array('pts_test_profile', 'is_test_profile'), null)
		);
	}

	/**
	 * Extracts all available test profiles that will be checked. The download.xml file is extracted for
	 * each test and test are performed on the filename, filesize, sha256 and md5 values to ensure it is
	 * concurrent with the Service Provider (ie john-the-ripper).
	 * 
	 * @param $r 	Checks named test(s) only 
	 */
	public static function run($r)
	{

		// File contains a list of all the tests that have already been tested
		self::$TESTED_FILES = PTS_OPENBENCHMARKING_SCRATCH_PATH . 'check-tests-tested.txt';

		// The file holding the final json results
		self::$JSON_FILE = PTS_OPENBENCHMARKING_SCRATCH_PATH . 'check-tests-results.json';

		// Downloaded file from vendors
		self::$DOWNLOADED_VENDOR_FILES = PTS_OPENBENCHMARKING_SCRATCH_PATH . "checkTestsDownloads" . "/";
	
		if (!function_exists('curl_init')) {
			echo pts_client::cli_colored_text("Test Failed. cURL must be installed to proceed." . PHP_EOL . PHP_EOL, 'red', true);
			return false;
		}

		$startTime = microtime(true);

		$procs = self::CHILD_PROCS;
		echo pts_client::cli_colored_text('Running check-tests with ' . $procs . ' processes.' . PHP_EOL . PHP_EOL, 'green', true);

		// Set up the file in which the results will be stored
		if (file_exists(self::$JSON_FILE)) {
			if (!unlink(self::$JSON_FILE))
				echo pts_client::cli_colored_text("File " . self::$JSON_FILE . " could not be deleted." . PHP_EOL . PHP_EOL, 'red', true);
		}

		if ($r == null) {
			// extract the test-profiles that will be checked. Omit minor release versions.
			$available_tests = pts_openbenchmarking::available_tests(false, 2);
		} else {
			// extract the test file(s) passed via the CLI
			$available_tests = $r;
		}

		$noOfForks = 0;
		$processed = 0;
		while ($processed < count($available_tests)) {

			// If we have more then $procs running, wait for one to free up before proceeding.
			if ($noOfForks >= $procs) {
				$pid = pcntl_waitpid(0, $status);
				self::mergeResults($pid);
				$noOfForks--;
			}

			// TODO: try to getting a test profile that is currently being worked on. This will prevent potentially
			// downloading the file twice as each process takes a different version of the test profile with the same
			// download. ie create 3 groups, namely done, doing, toDo 
			$currentTest = $available_tests[$processed];

			$pid = pcntl_fork();

			if ($pid === -1) {
				die('Unable to fork.');
			} else if ($pid) {
				// Parent Code
				echo pts_client::cli_colored_text("Testing " . $currentTest . PHP_EOL, 'grey', true);
				$noOfForks++;
			} else {
				// Child Code
				$results = self::processTestFile($currentTest);
				if ($results)
					self::logStatus($results, getmypid());

				exit(0);
			}
			$processed++;
		}

		// Wait for the children to catch up (also prevent command prompt from appearing early)
		while (($pid = pcntl_waitpid(0, $status)) != -1) {
			$status = pcntl_wexitstatus($status);
			self::mergeResults($pid);
		}

		// Clean any temp json files that might remain
		$deleteFiles = self::$JSON_FILE . '.*';
		array_map('unlink', glob($deleteFiles));

		// Count and report on number of downloads
		$noOfDownloads = count(scandir(self::$DOWNLOADED_VENDOR_FILES)) - 2; // remove ./ and ../ from the array count

		// PROD: delete all files... In DEV: comment out to prevent files from downloading with each test run.
		if (file_exists(self::$TESTED_FILES))
			unlink(self::$TESTED_FILES);
		if (is_dir(self::$DOWNLOADED_VENDOR_FILES)) {
			$deleteFiles = self::$DOWNLOADED_VENDOR_FILES . '*';
			array_map('unlink', glob($deleteFiles));
			rmdir(self::$DOWNLOADED_VENDOR_FILES);
		}

		echo PHP_EOL . pts_client::cli_colored_text("Total Tests Performed: " . $processed . PHP_EOL, 'white', true);
		echo pts_client::cli_colored_text("Total Downloads in Cache: " . $noOfDownloads . PHP_EOL, 'white', true);
		echo PHP_EOL . pts_client::cli_colored_text("Test Completed in " . self::timeToString(microtime(true) - $startTime) . "." . PHP_EOL . "Results reported in " . self::$JSON_FILE .  PHP_EOL . PHP_EOL, 'green', true);
	}

	/**
	 * Given a time in secs, converts time into hours mins secs.
	 * 
	 * @return string	"hours mins secs"
	 */
	public static function timeToString($time)
	{
		$hours = floor($time / 3600);
		$time -= $hours * 3600;
		$mins = floor($time / 60);
		$secs = $time - $mins * 60;

		if ($secs < 0)
			$secs = round($secs, 2);
		else
			$secs = floor($secs);

		if ($hours == 0 && $mins == 0)
			return "${secs}s";
		else if ($hours == 0)
			return "${mins}m ${secs}s";
		else
			return "${hours}h ${mins}m ${secs}s";
	}

	/**
	 * Extracts the download.xml file for a given Test Profile and returns the results of the
	 * checks.
	 * In the case that a download.xml file does not exist, the test-profile will not be tested 
	 * and the status will be 'Not Tested'
	 * 
	 * @param 	$identifier		array	List of test profiles as string. ie pts/sunflow-1.1.3
	 * @return	array					Test results. See @return in performChecksOnTestProfile
	 */
	public static function processTestFile($identifier)
	{
		// Determine if the identifer has is valid before proceeding.
		$testProfileObjects = pts_types::identifiers_to_test_profile_objects($identifier, true, true);
		if (count($testProfileObjects) == 0) {
			echo PHP_EOL . pts_client::cli_colored_text($identifier . " could not be found." .  PHP_EOL, 'cyan', true);
			return;
		}

		$packages = array();
		foreach ($testProfileObjects as $testProfile) {

			// need to massage into a format for frontend use.
			$xmlFile = $testProfile->get_downloads();
			if ($xmlFile == null)
				$packages[0] =
					array(
						"identifier" => $identifier,	// repeated for front end
						"mirror" =>	array(
							"status" => 'Not Tested',
							"failures" => "downloads.xml file not found"
						)
					);

			foreach ($xmlFile as $checks) {
				$packages[count($packages)] = self::performChecksOnTestProfile($checks, $identifier);
			}
		}

		return $packages;
	}

	/**
	 * 
	 * Performs a check on the filename, filesize, sha256 and md5 to ensure the parameters
	 * align with the Service Provider.
	 *
	 * @param $data array Test Results
	 * 
	 * @return array [
	 * 		'identifer' 	=> test-profile'	// ie pts/aobench-1.0.0
	 *  	'status'		=> Passed|Failed	// If all tests passed returns Passed, otherwise Failed
	 * 		'pts-filename'	=> Name of file		// Indicative of which mirror was tested ie aircrack-1.5.2.tar.gz or aircrack-1.5.2-win.zip
	 * 		'pts-filesize	=> Size of downloaded file
	 * 		'pts-sha256 	=> sha256 checksum
	 * 		'pts-md5 	 	=> md5 checksum
	 * 		'download-time'	=> Time to download file (microsecs)
	 * 		'duplicate'		=> indicate that the test-profile reused a download from another version that had already been downloaded
	 * 		'redirect'		=> The original url in that case of a HTTP Status 302 
	 * 		
	 * 		// The following will only be populated if the test failed:
	 * 		'failures'		=> array [			
	 * 			<test that failed> 	=>	<details on the test(s) that failed>			
	 * 			]
	 * 		]
	 */
	public static function performChecksOnTestProfile($data, $identifier)
	{
		$filename = $data->get_filename();

		// Extract the following data from the local environment.
		$url = $data->get_download_url_array();
		$filesize = $data->get_filesize();
		$sha256 = $data->get_sha256();
		$md5 = $data->get_md5();

		// Record the local pts values extracted from the download.xml file
		$profileResults = array(
			"identifier" => $identifier,
			"pts-filename" => $filename,
			"pts-filesize" => $filesize,
			"pts-sha256" => $sha256,
			"pts-md5" => $md5,
		);

		$results = array(
			"status" => null
		);

		// Set up a structure that will handle any number of mirrors
		$mirrorCount = 0;
		$mirrorResults = array();

		foreach ($url as $mirror) {

			// Filename is derived from the $URL so no comparison is necessary
			$remote_filename = basename($mirror);

			// Check to see if the URL has already been tested and grab the download.
			$vendorData = self::downloadVendorData($mirror, $remote_filename, $identifier);

			if ($vendorData[self::V_DUPLICATE])
				$results[self::V_DUPLICATE] = $vendorData[self::V_IDENTIFIER] . " copied from " . $vendorData[self::V_URL];

			$results["url"] = $mirror;
			if (array_key_exists(self::V_REDIRECT, $vendorData) && $vendorData[self::V_REDIRECT] != "0")
				$results[self::V_REDIRECT] = $vendorData[self::V_REDIRECT];


			// Initiate where all the Discrepancies reside
			$results['failures'] = array();

			// Determine validity of url. If url d.n.e then all other tests are irrelevant
			if ($vendorData['status'] != '200') {
				$results["failures"]['vendor'] = "HTTP Code: " . $vendorData['status'];
				$results['status'] = "Failed";
			} else {
				// Record the results downloaded from the vendor
				$results["download-time"] = $vendorData[self::V_DOWNLOAD_TIME];

				// Local filesize should be the same as the downloaded bytes. In older versions
				// of test-profiles, the filesize may not be set. This does not constitute a failure.

				$filesize_status = ($filesize > 0) && ($filesize == $vendorData[self::V_DOWNLOAD_SIZE]);
				if (!$filesize_status) {
					$results["failures"]["filesize"] = $vendorData[self::V_DOWNLOAD_SIZE];
				}


				// In some cases the checksum d.n.e and does not constitue a failure, test it only
				// if it exists.
				$sha256_status = true;
				if (!is_null($sha256)) {
					$remote_sha256 = hash_file('sha256', $vendorData[self::V_DOWNLOAD_FILE]);
					$sha256_status = ($sha256 == $remote_sha256);
					if (!$sha256_status) {
						$results["failures"]["sha256"] =  $remote_sha256;
					}
				}

				$md5_status = true;
				if (!is_null($md5)) {
					$remote_md5 = md5_file($vendorData[self::V_DOWNLOAD_FILE]);
					$md5_status = ($md5 == $remote_md5);
					if (!$md5_status) {
						$results["failures"]["md5"] = $remote_md5;
					}
				}

				// Return status of all tests
				if ($filesize_status && $sha256_status && $md5_status) {
					$results['status'] =  "Passed";
					unset($results["failures"]);
				} else {
					$results['status'] =  "Failed";
				}
			}

			// if there are mirrors then save the result for each mirror
			$mirrorResults[$mirrorCount] = $results;
			$mirrorCount++;
		}

		$profileResults["mirror"] = $mirrorResults;
		return $profileResults;
	}


	/**
	 * Logs the status of the test to a temp file. 
	 * A file is created for each child process. The results are stored as JSON.
	 * 
	 * @param $results	Array	Results for each test profile.
	 * @param $id		integer	Suffix $id to file containing the results. ie  resultFile.json.id
	 * 
	 */
	public static function logStatus($results, $id)
	{
		$writeFile = self::$JSON_FILE . '.' . $id;

		if ($results)
			if (!file_put_contents($writeFile, json_encode($results), FILE_APPEND)) {
				echo PHP_EOL . pts_client::cli_colored_text("Failed to write " . $results['test-profile'] . " to file" . $writeFile . PHP_EOL . PHP_EOL, 'red', true);
			}
	}

	/**
	 * Merges the results in each temp file into one json file.
	 * The temp files are deleted.
	 * 
	 * @param $id	integer	The suffix for the temp files created in the method 'logStatus'
	 */
	public static function mergeResults($id)
	{
		if (!file_exists(self::$JSON_FILE . '.' . $id)) {
			//echo PHP_EOL . "File " . self::$JSON_FILE . '.' . $id . " could not be merged. Does not exist." . PHP_EOL . PHP_EOL;
			return;
		}

		$mergedArray = json_decode(file_get_contents(self::$JSON_FILE), true);
		if (!$mergedArray)
			$mergedArray = array();

		$dataArray = json_decode(file_get_contents(self::$JSON_FILE . '.' . $id), true);

		$mergedArray[count($mergedArray)] =  array(

			"profile-name" => $dataArray[0]['identifier'],
			"packages" => $dataArray
		);

		if (!file_put_contents(self::$JSON_FILE, json_encode($mergedArray))) {
			echo PHP_EOL . pts_client::cli_colored_text("Failed to merge results to  " . self::$JSON_FILE . PHP_EOL . PHP_EOL, 'red', true);
		}

		// Delete the temp files
		if (!unlink(self::$JSON_FILE . '.' . $id))
			echo "Unable to delete file " . self::$JSON_FILE . '.' . $id;
	}

	/**
	 * Download the required vendor data to test against.
	 * 
	 * @param $url			The vendor $url from where the test is downloaded
	 * @param $filename		The file to which the test will be downloaded locally.
	 * 
	 * @return $download	Associative array holding the downloaded data
	 * 						'status' 		=> Return HTTP Code
	 * 						'downloadTime' 	=> Time to connect and download test
	 * 						'downloadSize' 	=> Test size
	 * 						self::D_DOWNLOAD_FILE 	=> Location where the file was downloaded. 	
	 */
	public static function downloadVendorData($url, $filename, $identifier)
	{
		if (!is_dir(self::$DOWNLOADED_VENDOR_FILES))
			mkdir(self::$DOWNLOADED_VENDOR_FILES);

		$download = array();

		// Check to see if the file has already been downloaded
		$downloaded = self::alreadyTested($url);
		if ($downloaded) {
			$download[self::V_DUPLICATE] = true;
			$download[self::V_IDENTIFIER] = $downloaded[0];
			$download[self::V_URL] = $downloaded[1];
			$download[self::V_DOWNLOAD_FILE] = $downloaded[2];
			$download[self::V_STATUS] = $downloaded[3];
			$download[self::V_DOWNLOAD_TIME] = $downloaded[4];
			$download[self::V_DOWNLOAD_SIZE] = $downloaded[5];
			$download[self::V_REDIRECT] = $downloaded[6];

			echo pts_client::cli_colored_text($downloaded[0] . " extracted " . basename($download[self::V_DOWNLOAD_FILE])  . " for reuse by " . $identifier . PHP_EOL, 'gray', false);
		} else {
			$temp_filename = self::$DOWNLOADED_VENDOR_FILES . $filename . getmypid();

			$ch = curl_init($url);
			$fh = fopen($temp_filename, 'w');

			// Check for a 302 redirect without downloading any info. If a redirect is returned then get the redirected URL and note it.
			// http_code is technically not a failure if the redirect returns a status of 200 however we may want to watch it.
			curl_setopt($ch, CURLOPT_NOBODY, TRUE);
			curl_exec($ch);
			$info = curl_getinfo($ch);

			if ($info['http_code'] == 302) {
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
				$download[self::V_REDIRECT] = $info['url'];
			}

			// Perform the standard curl
			curl_setopt($ch, CURLOPT_NOBODY, FALSE);
			curl_setopt($ch, CURLOPT_FILE, $fh);
			curl_exec($ch);
			$info = curl_getinfo($ch);

			curl_close($ch);
			fclose($fh);

			$download[self::V_IDENTIFIER] = $identifier;
			$download[self::V_DUPLICATE] = false;
			$download[self::V_URL] = $info['url'];
			$download[self::V_STATUS] = $info['http_code'];
			$download[self::V_DOWNLOAD_TIME] = $info['total_time'];
			$download[self::V_DOWNLOAD_SIZE] = $info['size_download'];
			$download[self::V_DOWNLOAD_FILE] = $temp_filename;

			//echo "Downloaded " . $temp_filename . " and saving to tested.txt" . PHP_EOL;
			self::preventDuplicates($download);
		}

		return $download;
	}

	/**
	 * Write the details that were downloaded from the vendor to a file.
	 * 
	 * @param $data	array	Details that were downloaded from the vendor
	 */
	public static function preventDuplicates($data)
	{
		// If a redirection exists, record the original URL as per the downloads.xml file
		$url = $data[self::V_URL];
		$redirect = 0;
		if (array_key_exists(self::V_REDIRECT, $data)) {
			$url = $data[self::V_REDIRECT];
			$redirect = $data[self::V_URL];
		}

		$recordedData = $data[self::V_IDENTIFIER] . self::DELIMITER .
			$url . self::DELIMITER .
			$data[self::V_DOWNLOAD_FILE] . self::DELIMITER .
			$data[self::V_STATUS] . self::DELIMITER .
			$data[self::V_DOWNLOAD_TIME] . self::DELIMITER .
			$data[self::V_DOWNLOAD_SIZE] . self::DELIMITER .
			$redirect . self::DELIMITER;


		if (!file_put_contents(self::$TESTED_FILES, $recordedData . PHP_EOL, FILE_APPEND | LOCK_EX))
			echo PHP_EOL . pts_client::cli_colored_text("Failed to write " . $data[self::V_URL] . " test status to file " . self::$TESTED_FILES . PHP_EOL . PHP_EOL, 'cyan', true);
	}

	/**
	 * Returns true if the search string exists in the file. 
	 * As the tests are run in batches of like, the downloaded file will likely
	 * be at the end of the file so start the search from the end.
	 * 
	 * @param $search	The url from which the file was downloaded.
	 */
	public static function alreadyTested($search)
	{
		$handle = @fopen(self::$TESTED_FILES, "r");
		if ($handle) {
			while (!feof($handle)) {
				$buffer = fgets($handle);
				$pos = strpos($buffer, $search);
				if ($pos !== FALSE) {
					$downloadDetails = explode(self::DELIMITER, $buffer);
					return $downloadDetails;
				}
			}
			fclose($handle);

			return false;
		}
	}
}
