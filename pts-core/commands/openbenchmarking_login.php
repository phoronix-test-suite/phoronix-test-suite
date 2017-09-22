<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2017, Phoronix Media
	Copyright (C) 2008 - 2017, Michael Larabel

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

class openbenchmarking_login implements pts_option_interface
{
	const doc_section = 'OpenBenchmarking.org';
	const doc_description = 'This option is used for controlling your Phoronix Test Suite client options for OpenBechmarking.org and syncing the client to your account.';

	public static function command_aliases()
	{
		return array('openbenchmarking_setup');
	}
	public static function run($r)
	{
		echo PHP_EOL . 'If you have not already registered for your free OpenBenchmarking.org account, you can do so at https://openbenchmarking.org/' . PHP_EOL . PHP_EOL . 'Once you have registered your account and clicked the link within the verification email, enter your log-in information below.' . PHP_EOL . PHP_EOL;
		$username = pts_user_io::prompt_user_input('OpenBenchmarking.org User-Name');
		$password = pts_user_io::prompt_user_input('OpenBenchmarking.org Password', false, true);

		$login_payload = array(
			's_u' => $username,
			's_p' => sha1($password),
			's_s' => base64_encode(phodevi::system_software(true)),
			's_h' => base64_encode(phodevi::system_hardware(true))
			);
		$login_state = pts_openbenchmarking::make_openbenchmarking_request('account_login', $login_payload);
		$json = json_decode($login_state, true);

		if(!isset($json['openbenchmarking']) || isset($json['openbenchmarking']['response']['error']))
		{
			trigger_error($json['openbenchmarking']['response']['error'], E_USER_ERROR);
			pts_storage_object::remove_in_file(PTS_CORE_STORAGE, 'openbenchmarking');
		}
		else
		{
			$openbenchmarking_payload = array(
				'user_name' => $json['openbenchmarking']['account']['user_name'],
				'communication_id' => $json['openbenchmarking']['account']['communication_id'],
				'sav' => $json['openbenchmarking']['account']['sav'],
				);
			pts_storage_object::set_in_file(PTS_CORE_STORAGE, 'openbenchmarking', $openbenchmarking_payload);
			echo PHP_EOL . PHP_EOL . 'The Account Has Been Setup.' . PHP_EOL . PHP_EOL;
		}
	}
}

?>
