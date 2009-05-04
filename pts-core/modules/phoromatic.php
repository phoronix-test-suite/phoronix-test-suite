<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel

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

define("M_PHOROMATIC_GEN_RESPONSE", "PhoronixTestSuite/Phoromatic/General/Response");
define("M_PHOROMATIC_SYS_NAME", "PhoronixTestSuite/Phoromatic/General/SystemName");

class phoromatic extends pts_module_interface
{
	const module_name = "Phoromatic Client";
	const module_version = "0.0.2";
	const module_description = "The Phoromatic client is used for connecting to a Phoromatic server (Phoromatic.com or a locally run server) to facilitate the automatic running of tests, generally across multiple test nodes in a routine manner. For more details visit http://www.phoromatic.com/";
	const module_author = "Phoronix Media";

	static $phoromatic_host = null;
	static $phoromatic_account = null;
	static $phoromatic_verifier = null;
	static $phoromatic_system = null;

	public static function module_info()
	{

	}
	public static function module_setup()
	{
		return array(
		new pts_module_option("remote_host", "Enter the URL to host", "HTTP_URL", "http://www.phoromatic.com/"),
		new pts_module_option("remote_account", "Enter the account code", "ALPHA_NUMERIC"),
		new pts_module_option("remote_verifier", "Enter the verification code", "ALPHA_NUMERIC")
		);
	}
	public static function module_setup_validate($options)
	{
		if(substr($options["remote_host"], -14) != "phoromatic.php")
		{
			if(substr($options["remote_host"], -1) != "/")
			{
				$options["remote_host"] .= "/";
			}

			$options["remote_host"] .= "phoromatic.php";
		}

		$server_response = phoromatic::upload_to_remote_server(array("r" => "setup", "h" => pts_hw_string(),  "s" => pts_sw_string()),
		$options["remote_host"], $options["remote_account"], $options["remote_verifier"]);

		$xml_parser = new tandem_XmlReader($server_response);
		$returned_id = $xml_parser->getXMLValue(M_PHOROMATIC_GEN_RESPONSE);

		if(!empty($returned_id))
		{
			$options["remote_system"] = $returned_id;
		}
		else
		{
			echo "\nConfiguration Failed!\n\n";
			$options = array();
		}

		return $options;
	}
	public static function user_commands()
	{
		return array("start" => "user_start", "user_system_return" => "user_system_return");
	}

	//
	// User Run Commands
	//

	public static function user_start()
	{
		if(!pts_module::is_module_setup())
		{
			echo "\nYou first must run:\n\nphoronix-test-suite module-setup phoromatic\n\n";
			return false;
		}

		self::$phoromatic_host = pts_module::read_option("remote_host");
		self::$phoromatic_account = pts_module::read_option("remote_account");
		self::$phoromatic_verifier = pts_module::read_option("remote_verifier");
		self::$phoromatic_system = pts_module::read_option("remote_system");

		$update_sd = phoromatic::update_system_details();

		if(!$update_sd)
		{
			echo "\nConnection to server failed.\n\n";
			return false;
		}

		pts_attach_module("phoromatic");
		phoromatic::user_system_process();
	}

	//
	// Core Functions
	//

	public static function user_system_return($tests)
	{
		// Upload result here
		foreach($tests as $test)
		{
			if(is_file(XML_SUITE_LOCAL_DIR . $test . ".xml"))
			{
				unlink(XML_SUITE_LOCAL_DIR . $test . ".xml");
			}
		}

		exit; // DEBUG
		phoromatic::user_system_process();
	}
	public static function user_system_process()
	{
		do
		{
			$server_response = phoromatic::upload_to_remote_server(array("r" => "status_check"));

			$xml_parser = new tandem_XmlReader($server_response);
			$response = $xml_parser->getXMLValue(M_PHOROMATIC_GEN_RESPONSE);

			switch($response)
			{
				case "benchmark":
					$args_to_pass = array("AUTOMATED_MODE" => true);

					do
					{
						$suite_identifier = "phoromatic-" . rand(1000, 9999);
					}
					while(is_file(XML_SUITE_LOCAL_DIR . $suite_identifier . ".xml"));

					$args_to_pass["AUTO_SAVE_NAME"] = date("Y-m-d H:i:s");
					$args_to_pass["PHOROMATIC_TITLE"] = $xml_parser->getXMLValue(P_SUITE_TITLE);
					$args_to_pass["AUTO_TEST_RESULTS_IDENTIFIER"] = $xml_parser->getXMLValue(M_PHOROMATIC_SYS_NAME);

					file_put_contents(XML_SUITE_LOCAL_DIR . $suite_identifier . ".xml", $server_response);

					pts_run_option_next("install_test", $suite_identifier, array("AUTOMATED_MODE" => true));
					pts_run_option_next("run_test", $suite_identifier, $args_to_pass);
					pts_run_option_next("phoromatic.user_system_return", $suite_identifier, $args_to_pass);
					break;
				case "exit":
					break;
				default:
					exit;  // DEBUG
					sleep((5 - (date("i") % 5)) * 60); // Check with server every 5 minutes
					break;
			}
		}
		while(!in_array($response, array("exit", "benchmark")));
	}

	//
	// Process Functions
	//


	public static function __pre_test_install($test_identifier)
	{
		static $last_update_time = null;

		if($last_update_time == null || time() > ($last_update_time + 600))
		{
			phoromatic::update_system_status("Installing Tests");
			$last_update_time = time();
		}
	}
	public static function __pre_test_run( $pts_test_result)
	{
		phoromatic::update_system_status("Running " . $pts_test_result->get_attribute("TEST_IDENTIFIER") . " For " . pts_read_assignment("PHOROMATIC_TITLE"));
	}

	//
	// Other Functions
	//

	protected static function update_system_details()
	{
		$server_response = phoromatic::upload_to_remote_server(array("r" => "update_system_details", "h" => pts_hw_string(),  "s" => pts_sw_string()));
		$xml_parser = new tandem_XmlReader($server_response);

		return $xml_parser->getXMLValue(M_PHOROMATIC_GEN_RESPONSE) == "TRUE";
	}
	protected static function update_system_status($current_task)
	{
		phoromatic::upload_to_remote_server(array("r" => "update_system_status", "a" => $current_task));
	}

	//
	// Connection
	// 

	protected static function upload_to_remote_server($to_post, $host = null, $account = null, $verifier = null, $system = null)
	{
		if($host != null)
		{
			//$host
			$to_post["aid"] = $account;
			$to_post["vid"] = $verifier;
			$to_post["sid"] = $system;
		}
		else if(self::$phoromatic_host != null)
		{
			$host = self::$phoromatic_host;
			$to_post["aid"] = self::$phoromatic_account;
			$to_post["vid"] = self::$phoromatic_verifier;
			$to_post["sid"] = self::$phoromatic_system;
		}
		else
		{
			echo "\nPhoromatic isn't configured. Run: phoronix-test-suite module-setup phoromatic\n\n";
			return;
		}

		$to_post["pts"] = PTS_VERSION;

		return pts_http_upload_via_post($host, $to_post);
	}
}

?>
