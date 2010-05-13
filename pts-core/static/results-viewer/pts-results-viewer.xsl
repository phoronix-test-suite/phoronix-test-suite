<?xml version="1.0" encoding="UTF-8"?>
<!--

Phoronix Test Suite
URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
Copyright (C) 2008 - 2010, Phoronix Media
Copyright (C) 2008 - 2010, Michael Larabel

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

-->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
<xsl:template match="/">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Phoronix Test Suite - <xsl:value-of select="PhoronixTestSuite/Suite/Title" /> - Results</title>
		<link href="../pts-results-viewer/phoronix-test-suite.css" rel="stylesheet" type="text/css" />
		<link rel="shortcut icon" href="../pts-results-viewer/favicon.ico" />
		<script src="../pts-results-viewer/pts.js" type="text/javascript"></script>
	</head>
	<body>
		<div id="pts_header_top">
			<div class="pts_header_center">
				<div id="pts_header_top_logo"></div>
				<div class="pts_header_links"><em><strong><xsl:value-of select="PhoronixTestSuite/Generated/Version" /></strong><br /><xsl:if test="PhoronixTestSuite/Generated/TimeStamp != ''"><strong>Generated:</strong>&#160;<xsl:value-of select="PhoronixTestSuite/Generated/TimeStamp" /><br /></xsl:if></em></div>
			</div>
		</div>
		<div id="pts_container">
		<div id="pts_banner_nav"><a href="#result-overview">Results Table</a> <a href="#test-results">Test Results</a> <a href="installation-logs/">Installation Logs</a> <a href="system-logs/">System Logs</a> <a href="test-logs/">Test Logs</a></div>

		<h1><xsl:value-of select="PhoronixTestSuite/Suite/Title" /></h1>
		<p><em><strong>Test:</strong></em>&#160;<xsl:value-of select="PhoronixTestSuite/Suite/Name" /><br /><em><strong>Test Version:</strong></em>&#160;<xsl:value-of select="PhoronixTestSuite/Suite/Version" /><br /><em><strong>Test Type:</strong></em>&#160;<xsl:value-of select="PhoronixTestSuite/Suite/Type" /></p>
		<p><xsl:value-of select="PhoronixTestSuite/Suite/Description"/></p>

		<h1>System Information</h1>
		<div class="pts_table_box_out"><table border="0">
		<tr class="pts_column_head"> 
		<xsl:for-each select="PhoronixTestSuite/System"><td><xsl:value-of select="AssociatedIdentifiers" /></td></xsl:for-each>
		</tr>
		<tr> 
		<xsl:for-each select="PhoronixTestSuite/System"><td><xsl:value-of select="Hardware" /></td></xsl:for-each>
		</tr>
		<tr class="pts_column_head"> 
		<xsl:for-each select="PhoronixTestSuite/System"><td><xsl:value-of select="AssociatedIdentifiers" /></td></xsl:for-each>
		</tr>
		<tr> 
		<xsl:for-each select="PhoronixTestSuite/System"><td><xsl:value-of select="Software" /></td></xsl:for-each>
		</tr>
		<tr class="pts_column_head"> 
		<xsl:for-each select="PhoronixTestSuite/System"><td><xsl:value-of select="AssociatedIdentifiers" /></td></xsl:for-each>
		</tr>
		<tr>
		<xsl:for-each select="PhoronixTestSuite/System"><td><strong>Administrator:</strong>&#160;<xsl:value-of select="Author" /><br /><strong>Date:</strong>&#160;<xsl:value-of select="TestDate" /><br /><strong>PTS Version:</strong>&#160;<xsl:value-of select="Version" /><br /><strong>Test Notes:</strong>&#160;<xsl:value-of select="TestNotes" /></td></xsl:for-each>
		</tr>
		</table></div>

		<a name="result-overview"></a><h1>Results Overview</h1>
		<div align="center" style="width: 100%; overflow: auto;"><!-- OVERVIEW TAG --><object type="image/svg+xml" data="result-graphs/overview.svg"></object></div>

		<a name="test-results"></a><h1>Test Results</h1>
		<div id="pts_benchmark_area">
			<xsl:for-each select="PhoronixTestSuite/Benchmark">
				<xsl:variable name="this_test_pos" select="position()" />
				<div class="pts_benchmark_bar"><div style="float: left;"><a><xsl:attribute name="name">test-<xsl:value-of select="$this_test_pos" /></xsl:attribute></a><a><xsl:attribute name="name">b-<xsl:value-of select="$this_test_pos" /></xsl:attribute></a><span class="pts_benchmark_bar_header"><xsl:value-of select="Name"/></span> <span class="pts_benchmark_bar_version"><xsl:value-of select="Version"/></span><br /><strong><xsl:value-of select="Attributes"/></strong></div><div style="float: right;"><a style="text-decoration: none;"><xsl:attribute name="href">test-logs/<xsl:value-of select="$this_test_pos" />/</xsl:attribute>View Test Logs</a></div></div>
				<div class="pts_benchmark_text">
					<xsl:if test="not(contains(ResultFormat,'MULTI_'))">
						<xsl:for-each select="Results/Group">
							<div style="padding: 5px 0;">
								<xsl:for-each select="Entry">
									<strong><xsl:value-of select="Identifier" />:</strong><span style="padding-left: 5px;"><xsl:choose><xsl:when test="string-length(Value)&lt;20"><xsl:value-of select="Value"/></xsl:when></xsl:choose></span><br />
									</xsl:for-each>
							</div>
						</xsl:for-each>
					</xsl:if>
				</div>

				<div class="pts_benchmark_img_area"><!-- GRAPH TAG --></div>
				<div class="pts_benchmark_img_table_area"><!-- GRAPH TABLE TAG --></div>
			</xsl:for-each>
		</div>

		<div id="pts_copyright_area">Copyright &#xA9; 2008 - 2010 by <a href="http://www.phoronix-media.com/">Phoronix Media</a>.</div>
		</div>
		<div id="pts_header_bottom">
			<div class="pts_header_center">
				<div class="pts_header_links_left"><a href="http://www.phoronix.com/">Phoronix</a><br /><a href="http://www.phoronix.com/forums/">Phoronix Forums</a><br /><a href="http://commercial.phoronix-test-suite.com/">PTS Commercial</a></div>
				<div class="pts_header_links"><a href="http://www.phoronix-test-suite.com/">Phoronix Test Suite</a><br /><a href="http://global.phoronix.com/">Phoronix Global</a><br /><a href="http://www.phoromatic.com/">Phoromatic</a></div>
			</div>
		</div>

	</body>
</html>
</xsl:template>
</xsl:stylesheet>
