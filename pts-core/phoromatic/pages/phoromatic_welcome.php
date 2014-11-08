<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2014, Phoronix Media
	Copyright (C) 2008 - 2014, Michael Larabel

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


class phoromatic_welcome implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Welcome';
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
		if(isset($_POST['register_username']) && isset($_POST['register_password']) && isset($_POST['register_password_confirm']) && isset($_POST['register_email']))
		{
			// REGISTER NEW USER
			if(strlen($_POST['register_username']) < 4 || strpos($_POST['register_username'], ' ') !== false)
			{
				phoromatic_error_page('Oops!', 'Please go back and ensure the supplied username is at least four characters long and contains no spaces.');
				return false;
			}
			if(in_array(strtolower($_POST['register_username']), array('admin', 'administrator')))
			{
				phoromatic_error_page('Oops!', $_POST['register_username'] . ' is a reserved and common username that may be used for other purposes, please make a different selection.');
				return false;
			}
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
			if($_POST['register_email'] == null || filter_var($_POST['register_email'], FILTER_VALIDATE_EMAIL) == false)
			{
				phoromatic_error_page('Oops!', 'Please enter a valid email address.');
				return false;
			}

			$valid_user_name_chars = '1234567890-_.abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			for($i = 0; $i < count($_POST['register_username']); $i++)
			{
				if(strpos($valid_user_name_chars, substr($_POST['register_username'], $i, 1)) === false)
				{
					phoromatic_error_page('Oops!', 'Please go back and ensure a valid user-name. The character <em>' . substr($_POST['register_username'], $i, 1) . '</em> is not allowed.');
					return false;
				}
			}

			$matching_users = phoromatic_server::$db->querySingle('SELECT UserName FROM phoromatic_users WHERE UserName = \'' . SQLite3::escapeString($_POST['register_username']) . '\'');
			if(!empty($matching_users))
			{
				phoromatic_error_page('Oops!', 'The user-name is already taken.');
				return false;
			}

			do
			{
				$account_id = pts_strings::random_characters(6, true);
				$matching_accounts = phoromatic_server::$db->querySingle('SELECT AccountID FROM phoromatic_accounts WHERE AccountID = \'' . $account_id . '\'');
			}
			while(!empty($matching_accounts));

			$account_salt = pts_strings::random_characters(12, true);
			$user_id = pts_strings::random_characters(4, true);
			$salted_password = hash('sha256', $account_salt . $_POST['register_password']);

			pts_logger::add_to_log($_SERVER['REMOTE_ADDR'] . ' created a new account: ' . $user_id . ' - ' . $account_id);

			$stmt = phoromatic_server::$db->prepare('INSERT INTO phoromatic_accounts (AccountID, ValidateID, CreatedOn, Salt) VALUES (:account_id, :validate_id, :current_time, :salt)');
			$stmt->bindValue(':account_id', $account_id);
			$stmt->bindValue(':validate_id', pts_strings::random_characters(4, true));
			$stmt->bindValue(':salt', $account_salt);
			$stmt->bindValue(':current_time', phoromatic_server::current_time());
			$result = $stmt->execute();

			$stmt = phoromatic_server::$db->prepare('INSERT INTO phoromatic_users (UserID, AccountID, UserName, Email, Password, CreatedOn, LastIP, AdminLevel) VALUES (:user_id, :account_id, :user_name, :email, :password, :current_time, :last_ip, :admin_level)');
			$stmt->bindValue(':user_id', $user_id);
			$stmt->bindValue(':account_id', $account_id);
			$stmt->bindValue(':user_name', $_POST['register_username']);
			$stmt->bindValue(':email', $_POST['register_email']);
			$stmt->bindValue(':password', $salted_password);
			$stmt->bindValue(':last_ip', $_SERVER['REMOTE_ADDR']);
			$stmt->bindValue(':current_time', phoromatic_server::current_time());
			$stmt->bindValue(':admin_level', 1);
			$result = $stmt->execute();

			$stmt = phoromatic_server::$db->prepare('INSERT INTO phoromatic_user_settings (UserID, AccountID) VALUES (:user_id, :account_id)');
			$stmt->bindValue(':user_id', $user_id);
			$stmt->bindValue(':account_id', $account_id);
			$result = $stmt->execute();

			$stmt = phoromatic_server::$db->prepare('INSERT INTO phoromatic_account_settings (AccountID) VALUES (:account_id)');
			$stmt->bindValue(':account_id', $account_id);
			$result = $stmt->execute();

			mkdir(phoromatic_server::phoromatic_account_path($account_id));

			phoromatic_server::send_email($_POST['register_email'], 'Phoromatic Account Registration', 'no-reply@phoromatic.com', '<p><strong>' . $_POST['register_username'] . '</strong>:</p><p>Your Phoromatic account has been created and is now active.</p>');


			echo phoromatic_webui_header(array('Account Created'), '');
			$box = '<h1>Account Created</h1>
			<p>Your account has been created. You may now log-in to begin utilizing the Phoronix Test Suite\'s Phoromatic.</p>
			<form name="login_form" id="login_form" action="?login" method="post" onsubmit="return phoromatic_login(this);">
			<p><div style="width: 200px; font-weight: bold; float: left;">User:</div> <input type="text" name="username" /></p>
			<p><div style="width: 200px; font-weight: bold; float: left;">Password:</div> <input type="password" name="password" /></p>
			<p><div style="width: 200px; font-weight: bold; float: left;">&nbsp;</div> <input type="submit" value="Submit" /></p>
			</form>';
			echo phoromatic_webui_box($box);
			echo phoromatic_webui_footer();
		}
		else if(isset($_POST['username']) && isset($_POST['password']))
		{
			$matching_user = phoromatic_server::$db->querySingle('SELECT UserName, Password, AccountID, UserID, AdminLevel FROM phoromatic_users WHERE UserName = \'' . SQLite3::escapeString($_POST['username']) . '\'', true);
			if(!empty($matching_user))
			{
				$user_id = $matching_user['UserID'];
				$user = $matching_user['UserName'];
				$hashed_password = $matching_user['Password'];
				$account_id = $matching_user['AccountID'];
				$admin_level = $matching_user['AdminLevel'];

				if($user == $_POST['username'])
				{
					$account_salt = phoromatic_server::$db->querySingle('SELECT Salt FROM phoromatic_accounts WHERE AccountID = \'' . $account_id . '\'');
				}
				else
				{
					$account_salt = null;
				}

				if($account_salt != null && hash('sha256', $account_salt . $_POST['password']) == $hashed_password)
				{
					session_regenerate_id();
					$_SESSION['UserID'] = $user_id;
					$_SESSION['UserName'] = $user;
					$_SESSION['AccountID'] = $account_id;
					$_SESSION['AdminLevel'] = $admin_level;
					$account_salt = phoromatic_server::$db->exec('UPDATE phoromatic_users SET LastIP = \'' . $_SERVER['REMOTE_ADDR'] . '\', LastLogin = \'' . phoromatic_server::current_time() . '\' WHERE UserName = "' . $matching_user['UserName'] . '"');
					session_write_close();

					pts_file_io::mkdir(phoromatic_server::phoromatic_account_path($account_id));
					pts_file_io::mkdir(phoromatic_server::phoromatic_account_result_path($account_id));

					echo phoromatic_webui_header(array('Welcome, ' . $user), '');
					$box = '<h1>Log-In Successful</h1>
					<p><strong>' . $user . '</strong>, we are now redirecting you to your account portal. If you are not redirected within a few seconds, please <a href="?main">click here</a>.<script type="text/javascript">window.location.href = "?main";</script></p>';
					echo phoromatic_webui_box($box);
					echo phoromatic_webui_footer();
					pts_logger::add_to_log($_SERVER['REMOTE_ADDR'] . ' successfully logged in as user: ' . $user);
				}
				else
				{
					pts_logger::add_to_log($_SERVER['REMOTE_ADDR'] . ' failed a log-in attempt as: ' . $_POST['username']);
					phoromatic_error_page('Invalid Information', 'The user-name or password did not match our records.');
					return false;
				}
			}
			else
			{
				pts_logger::add_to_log($_SERVER['REMOTE_ADDR'] . ' failed a log-in attempt as: ' . $_POST['username']);
				phoromatic_error_page('Invalid Information', 'The user-name was not found within our system.');
				return false;
			}
		}
		else
		{
			echo phoromatic_webui_header(array('Sign-In'), '');

			$box = '<h1>Welcome</h1>
			<p>You must log-in to your Phoromatic account or create an account to access this service. Phoromatic is a remote management system for the Phoronix Test Suite. Phoromatic allows the automatic scheduling of tests, remote installation of new tests, and the management of multiple test systems all through an intuitive, easy-to-use web interface. Tests can be scheduled to automatically run on a routine basis across multiple test systems. The test results are then available from this central, secure location.</p>
			<p>Phoromatic makes it very easy to provide for automated scheduling of tests on multiple systems, is extremely extensible, allows various remote testing possibilities, makes it very trivial to manage multiple systems, and centralizes result management within an organization.</p>
			<hr />
			<h1>Log-In</h1>
			<form name="login_form" id="login_form" action="?login" method="post" onsubmit="return phoromatic_login(this);">
			<p><div style="width: 200px; font-weight: 500; float: left;">User:</div> <input type="text" name="username" /></p>
			<p><div style="width: 200px; font-weight: 500; float: left;">Password:</div> <input type="password" name="password" /></p>
			<p><div style="width: 200px; font-weight: 500; float: left;">&nbsp;</div> <input type="submit" value="Submit" /></p>
			</form>
			<hr />
			<h1>Register</h1>
			<p>Creating a new Phoromatic account is free and easy. The public, open-source version of the Phoronix Test Suite client is limited in its Phoromatic server abilities when it comes to result management and local storage outside of the OpenBenchmarking.org cloud. For organizations looking for behind-the-firewall support and other enterprise features, <a href="http://www.phoronix-test-suite.com/?k=commercial">contact us</a>. To create a new account for this Phoromatic server, simply fill out the form below.</p>';

			$box .= '<form name="register_form" id="register_form" action="?register" method="post" onsubmit="return phoromatic_initial_registration(this);">

			<div style="clear: both; font-weight: 500;">
			<div style="float: left; width: 25%;">Username</div>
			<div style="float: left; width: 25%;">Password</div>
			<div style="float: left; width: 25%;">Confirm Password</div>
			<div style="float: left; width: 25%;">Email Address</div>
			</div>

			<div style="clear: both;">
			<div style="float: left; width: 25%;"><input type="text" name="register_username" /> <sup>1</sup></div>
			<div style="float: left; width: 25%;"><input type="password" name="register_password" /> <sup>2</sup></div>
			<div style="float: left; width: 25%;"><input type="password" name="register_password_confirm" /></div>
			<div style="float: left; width: 25%;"><input type="text" name="register_email" /> <sup>3</sup><br /><br /><input type="submit" value="Create Account" /></div>
			</div>

			</form>';

			$box .= '<p style="font-size: 11px;"><sup>1</sup> Usernames shall be at least four characters long, not contain any spaces, and only be composed of normal ASCII characters.<br />
				<sup>2</sup> Passwords shall be at least six characters long.<br />
				<sup>3</sup> A valid email address is required for notifications, password reset, and other verification purposes.<br />
				</p>';

			$box .= '<hr /><hr />';

			echo phoromatic_webui_box($box);
			echo phoromatic_webui_footer();
		}
	}
}

?>
