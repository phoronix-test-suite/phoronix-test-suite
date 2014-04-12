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


class phoromatic_main implements pts_webui_interface
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
			echo phoromatic_webui_header(array('<a href="#">Main</a>', '<a href="#">Systems</a>', '<a href="#">Schedules</a>', '<a href="#">Results</a>'), '<form action="#" id="search"><input type="search" name="q" size="14" /><input type="submit" name="sa" value="Search" /></form>');

			$main = '<h1>Phoromatic</h1>
				<h2>Test</h2>
				<p>Test 1111111 <a href="">GO</a></p>


				<hr />

			<h1>Results</h1>
			<div class="pts_phoromatic_info_box_area">
				<div style="float: left; width: 100%;">
					<ul>
						<li><h1>Today\'s Test Results</h1></li>
						<a href=""><li>Title 1<br /><em>The daily mainline kernel performance tracking.</em></li></a>
						<a href=""><li>Title 2<br /><em>Monitoring the performance of various Mesa/Gallium3D drivers.</em></li></a>
					</ul>
				</div>
				<div style="float: left; width: 50%;">
					<ul>
						<li><h1>Yesterday\'s Test Results</h1></li>
						<a href=""><li>Test<br /><em>sdf sdfsdf dsfds fds fds fdsf dsf dsf dsfdsf ds</em></li></a>
						<a href=""><li>Test<br /><em>sdf dsfdsf dsfdsfds fdsfds fds fds fdsfdsfds fdsfdsfsdfdsfdsfds fsdfsdfsdfsdf.</em></li></a>
					</ul>
				</div>
				<div style="float: left; width: 50%;">
					<ul>
						<li><h1>Other Test Results This Week</h1></li>
						<a href=""><li>Core i7 4770K<br /><em>sfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bb.</em></li></a>
						<a href=""><li>Radeon R9 270X<br /><em>sfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bbsfdg dsfg dfsg fdgdfsav fgrthtehr hfbfg bb.</em></li></a>
					</ul>
				</div>

			</div>
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
