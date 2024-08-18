<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2014 - 2019, Phoronix Media
	Copyright (C) 2014 - 2019, Michael Larabel

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

class enterprise_setup implements pts_option_interface
{
	const doc_section = 'User Configuration';
	const doc_description = 'This option can be run by enterprise users immediately after package installation or as part of an in-house setup script. Running this command will ensure the phoronix-test-suite program is never interrupted on new runs to accept user agreement changes. It also defaults the anonymous usage reporting to being disabled, along with other conservative settings.';

	public static function run($r)
	{
		$force_options = array(
			'PhoronixTestSuite/Options/OpenBenchmarking/AnonymousUsageReporting' => 'FALSE',
			'PhoronixTestSuite/Options/OpenBenchmarking/AllowResultUploadsToOpenBenchmarking' => 'FALSE'
			);

		if(pts_network::internet_support_available() == false)
		{
			$force_options['PhoronixTestSuite/Options/Networking/NoInternetCommunication'] = 'TRUE';
		}

		pts_config::user_config_generate($force_options);

		$pso = pts_storage_object::recover_from_file(PTS_CORE_STORAGE);
		$pso->add_object('user_agreement_cs', 'enterprise-agree');
		$pso->save_to_file(PTS_CORE_STORAGE);

		echo PHP_EOL . 'Enterprise setup tasks executed.' . PHP_EOL . PHP_EOL;
	}
}

?>
