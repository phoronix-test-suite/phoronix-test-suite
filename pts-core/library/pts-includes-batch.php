<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
	pts-includes-batch.php: Functions Needed When Running In Batch Mode

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

function pts_batch_mode_configured()
{
	return pts_strings::string_bool(pts_config::read_user_config(P_OPTION_BATCH_CONFIGURED, "FALSE"));
}
function pts_batch_prompt_test_identifier()
{
	return pts_strings::string_bool(pts_config::read_user_config(P_OPTION_BATCH_PROMPTIDENTIFIER, "TRUE"));
}
function pts_batch_prompt_test_description()
{
	return pts_strings::string_bool(pts_config::read_user_config(P_OPTION_BATCH_PROMPTDESCRIPTION, "FALSE"));
}
function pts_batch_prompt_save_name()
{
	return pts_strings::string_bool(pts_config::read_user_config(P_OPTION_BATCH_PROMPTSAVENAME, "FALSE"));
}
function pts_batch_run_all_test_options()
{
	return pts_strings::string_bool(pts_config::read_user_config(P_OPTION_BATCH_TESTALLOPTIONS, "TRUE"));
}

?>
