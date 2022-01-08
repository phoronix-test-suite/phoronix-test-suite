<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2015, Phoronix Media
	Copyright (C) 2015, Michael Larabel

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

class phoromatic_password implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Password Management';
	}
	public static function page_header()
	{
		return null;
	}
	public static function preload($PAGE)
	{
		return true;
	}
	public static function render_page_process($PATH)
	{
		if(isset($_POST['register_password']) && isset($_POST['register_password_confirm']) && isset($_POST['old_password']))
		{
			$matching_user = phoromatic_server::$db->querySingle('SELECT Password FROM phoromatic_users WHERE UserName = \'' . $_SESSION['UserName'] . '\' AND AccountID = \'' . $_SESSION['AccountID'] . '\'', true);

			if(!empty($matching_user))
			{
				$hashed_password = $matching_user['Password'];
				$account_salt = phoromatic_server::$db->querySingle('SELECT Salt FROM phoromatic_accounts WHERE AccountID = \'' . $_SESSION['AccountID'] . '\'');

				if($account_salt != null && hash('sha256', $account_salt . $_POST['old_password']) == $hashed_password)
				{
					if(strlen($_POST['register_password']) < 6)
					{
						phoromatic_error_page('Oops!', 'Please go back and ensure the supplied password is at least six characters long.');
						return false;
					}
					if($_POST['register_password'] != $_POST['register_password_confirm'])
					{
						phoromatic_error_page('Oops!', 'Please go back and ensure the supplied password matches the password confirmation.');
						return false;
					}

					$new_salted_password = hash('sha256', $account_salt . $_POST['register_password']);
					phoromatic_server::$db->exec('UPDATE phoromatic_users SET Password = \'' . $new_salted_password . '\' WHERE UserName = "' . $_SESSION['UserName'] . '"');
					echo '<h1>Password Updated!</h1>';
				}
				else
				{
					phoromatic_error_page('Oops!', 'The original password does not match the records for this account.');
					return false;
				}
			}
			else
			{
				phoromatic_error_page('Oops!', 'Problem fetching user information. Try again.');
				return false;
			}
		}

		echo phoromatic_webui_header_logged_in();
		$main = '<h1>Change Password</h1>
		<form name="reset_password" id="reset_password" action="?password" method="post" onsubmit="return phoromatic_password_reset(this);">
		<div style="clear: both;">
			<div style="float: left; font-weight: bold; padding-right: 10px;">
			<p style="height: 50px;">Password</p>
			<p style="height: 50px;">New Password</p>
			<p style="height: 50px;">Confirm New Password</p>
			</div>

			<div style="float: left;">
			<p style="height: 50px;"><input type="password" name="old_password" /></p>
			<p style="height: 50px;"><input type="password" name="register_password" /> <sup>1</sup></p>
			<p style="height: 50px;"><input type="password" name="register_password_confirm" /></p>
			<p style="height: 50px;"><input type="submit" value="Change Password" /></p>
			</div>
		</div>
		<p style="clear: both;"><sup>1</sup> Passwords shall be at least six characters long.</p>';

		echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in());
		echo phoromatic_webui_footer();
	}
}

?>
