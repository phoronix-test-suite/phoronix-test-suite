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

class pts_triggered_system_events
{
	protected static $needs_queued_reboot = false;

	public static function pre_run_reboot_triggered_check(&$test_profile, &$env_var_array = null)
	{
		$reboot_needed_file = $test_profile->get_install_dir() . 'reboot-needed';
		if(is_file($reboot_needed_file))
		{
			// Test profile previously wrote to ~/reboot-needed
			$reboot_needed = pts_file_io::file_get_contents($reboot_needed_file);

			if($reboot_needed == PTS_CORE_VERSION . ':' . pts_client::get_time_pts_last_started())
			{
				// This test last run and issued the reboot-needed the last time the PTS process was started
				// i.e. ignore any potentially stale reboot-needed issuance so it will then be restarted as who knows what changed or went on
				// or if the PTS version changed, it may have rebooted due to PTS upgrade and/or other change

				// Set $TEST_RECOVERING_FROM_REBOOT to indicate to test script that it is doing so...
				// Potentially useful for test is relaying that last run-time if it wants to apply any extra logic/comparison of its own...
				// Test profiles can already have $THIS_RUN_TIME environment variable that should be the same value prior to the reboot (during the first run)
				$env_var_array['TEST_RECOVERING_FROM_REBOOT'] = pts_client::get_time_pts_last_started();
			}
			unlink($reboot_needed_file);
		}
	}
	public static function post_run_reboot_triggered_check(&$test_profile)
	{
		$reboot_needed_file = $test_profile->get_install_dir() . 'reboot-needed';
		if(is_file($reboot_needed_file))
		{
			// Test profile wrote to ~/reboot-needed to indicate need to reboot the system
			$reboot_needed = pts_file_io::file_get_contents($reboot_needed_file);

			$reboot_now = false;
			switch($reboot_needed)
			{
				case 'queued':
					// reboot at end of running all tests (or until hitting an immediate) to cut down on unnecessary/multiple reboots but prior to saving results
					self::$needs_queued_reboot = true;
					break;
				case 'immediate':
				default:
					// If just touch'ing the file / default, reboot right now
					$reboot_now = true;
					break;
			}

			// Replace the reboot-needed contents with an indicator for on succeeding run that can be used for determining if it's "recovering" from reboot
			file_put_contents($reboot_needed_file, PTS_CORE_VERSION . ':' . TIME_PTS_LAUNCHED);
			if($reboot_now)
			{
				self::do_reboot($test_profile);
			}
		}
	}
	public static function test_requested_queued_reboot_check()
	{
		if(self::$needs_queued_reboot)
		{
			self::do_reboot();
		}
	}
	protected static function do_reboot(&$test_profile = null)
	{
		// XXX: could add logic to warn or abort reboot if it looks like PTS won't come back up on reboot automatically...
		// i.e. if PTS was auto-launched or started by systemd service, likely should be fine, etc.

		pts_client::$display->test_run_instance_error('Rebooting system, requested by test profile.');
		pts_module_manager::module_process('__event_reboot', $test_profile);
		phodevi::reboot();
		exit;
	}
}

?>
