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


class phoromatic_systems implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Main';
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
			echo phoromatic_webui_header_logged_in();

			$main = '<h1>Test Systems</h1>
				<h2>Add A System</h2>
				<p>To connect a <a href="http://www.phoronix-test-suite.com/">Phoronix Test Suite</a> test system to this account for remotely managing and/or carrying out routine automated benchmarking, follow these simple and quick steps:</p>
				<ol><li>From a system with <em>Phoronix Test Suite 5.2 or newer</em> run <strong>phoronix-test-suite phoromatic.connect ' . phoromatic_web_socket_server_addr() . '</strong>. (The test system must be able to access this server\'s correct IP address / domain name.)</li><li>When you have run the command from the test system, you will need to log into this page on Phoromatic server again where you can approve the system and configure the system settings so you can begin using it as part of this Phoromatic account.</li><li>Repeat the two steps for as many systems as you would like! When you are all done -- if you haven\'t done so already, you can start creating test schedules, groups, and other Phoromatic events.</li></ol>


				<hr />

			<h1>Systems</h1>
			<div class="pts_phoromatic_info_box_area">

				<div style="float: left; width: 50%;">
					<ul>
						<li><h1>Recent System Activity</h1></li>
						<a href=""><li>Core i7 4770K<br /><em>sfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bb.</em></li></a>
						<a href=""><li>Radeon R9 270X<br /><em>sfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bb.</em></li></a>
					</ul>
				</div>
				<div style="float: left; width: 50%;">
					<ul>
						<li><h1>Recent System Warnings &amp; Errors</h1></li>
						<a href=""><li>Core i7 4770K<br /><em>sfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bb.</em></li></a>
						<a href=""><li>Radeon R9 270X<br /><em>sfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bb.</em></li></a>
					</ul>
				</div>
			</div>';

			$right = '<ul>
					<li>Active Systems</li>
					<li><a href="#">System A</a></li>
					<li><a href="#">System B</a></li>
					<li><a href="#">System C</a></li>
					<li><a href="#">System D</a></li>
				</ul>
				<hr />
				<ul>
					<li>Upcoming Tests</li>
					<li><a href="#">Test A</a></li>
					<li><a href="#">Test B</a></li>
					<li><a href="#">Test C</a></li>
					<li><a href="#">Test D</a></li>
				</ul>
				<hr />
				<p><strong>' . date('H:i - j F Y') . '</strong><br />10 Systems Connected<br />2 Test Schedules<br /><a href="?logout">Log-Out</a></p>';

			echo phoromatic_webui_main($main, $right);
			echo phoromatic_webui_footer();
	}
}

?>
