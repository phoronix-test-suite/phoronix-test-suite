<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2021, Phoronix Media
	Copyright (C) 2021, Michael Larabel

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

class enable_repo implements pts_option_interface
{
	const doc_section = 'OpenBenchmarking.org';
	const doc_description = 'This option is used if wanting to add a new OpenBenchmarking.org account/repository to your system for enabling third-party/unofficial test profiles and test suites.';

	public static function run($r)
	{
		if(count($r) == 0)
		{
			echo pts_client::cli_just_bold('You must specify the OpenBenchmarking.org account/repo name when running this command.') . PHP_EOL;
			echo 'Example: phoronix-test-suite enable-repo pts' . PHP_EOL . PHP_EOL;
			return false;
		}
		
		foreach($r as $repo_to_enable)
		{
			if(pts_openbenchmarking::is_local_repo($repo_to_enable))
			{
				echo pts_client::cli_just_bold($repo_to_enable) . ' is already enabled on this system.' . PHP_EOL;
			}
			else
			{
				$ob_info = pts_openbenchmarking::ob_repo_exists($repo_to_enable);
				if($ob_info == false)
				{
					echo pts_client::cli_just_bold('This repository does not seem to exist on OpenBenchmarking.org.') . PHP_EOL;
				}
				else
				{
					if(!in_array($ob_info[0], pts_openbenchmarking::official_repositories()))
					{
						echo '    ' . pts_client::cli_just_bold(pts_client::cli_just_italic($ob_info[0]) . ' is not an official OpenBenchmarking.org repository. This is a third-party account created by an independent user. Proceed with caution and use at your own risk.') . PHP_EOL;
						$proceed = pts_user_io::prompt_bool_input('Proceed with enabling this repository?', -1);
						if(!$proceed)
						{
							return false;
						}
					}
					
					pts_openbenchmarking::refresh_repository_lists(array($ob_info[0]), true);
				}
			
			}
		}
	}
}

?>
