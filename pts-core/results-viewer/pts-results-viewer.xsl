<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
<xsl:template match="/">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Phoronix Test Suite - <xsl:value-of select="PhoronixTestSuite/Suite/Title" /> - Results Viewer</title>
		<link href="../pts-results-viewer/pts-viewer.css" rel="stylesheet" type="text/css" />
		<script src="../pts-results-viewer/pts.js" type="text/javascript"></script>
	</head>
	<body>
		<div id="pts_header_top">
			<div id="pts_header_top_center">
				<div id="pts_header_top_title">Phoronix Test Suite</div>
				<div id="pts_header_top_link_group"><a href="http://www.phoronix-test-suite.com/">Phoronix Test Suite</a><a href="http://global.phoronix.com/">Phoronix Global</a></div>
			</div>
		</div>
		<div id="pts_container">
			<div class="pts_box">

			<div style="float: left; width: 60%;">
				<h1><xsl:value-of select="PhoronixTestSuite/Suite/Title" /></h1>
				<div class="pts_chart_box">
					<p><strong><xsl:value-of select="PhoronixTestSuite/Suite/Name" /> v<xsl:value-of select="PhoronixTestSuite/Suite/Version" /> (<xsl:value-of select="PhoronixTestSuite/Suite/Type" />)</strong></p>
				</div>
			</div>
			<div style="float: right; width: 40%;">
				<p align="right"><img src="../pts-results-viewer/pts-logo.png" /></p>
			</div>
				<div class="pts_chart_box">
					<p><xsl:value-of select="PhoronixTestSuite/Suite/Description"/></p>
				</div>

				<h1>System Hardware</h1>
				<div class="pts_table_box">
				<xsl:for-each select="PhoronixTestSuite/System"><div class="pts_table_box_col"><div class="pts_column_head"><xsl:value-of select="AssociatedIdentifiers" /></div><div class="pts_column_body"><div class="pts_column_body_text"><xsl:value-of select="Hardware" /></div></div></div></xsl:for-each>
				</div>
				<h1>System Software</h1>
				<div class="pts_table_box">
				<xsl:for-each select="PhoronixTestSuite/System"><div class="pts_table_box_col"><div class="pts_column_head"><xsl:value-of select="AssociatedIdentifiers" /></div><div class="pts_column_body"><div class="pts_column_body_text"><xsl:value-of select="Software" /></div></div></div></xsl:for-each>
				</div>
				<h1>Additional Details</h1>
				<div class="pts_table_box">
				<xsl:for-each select="PhoronixTestSuite/System"><div class="pts_table_box_col"><div class="pts_column_head"><xsl:value-of select="AssociatedIdentifiers" /></div><div class="pts_column_body"><div class="pts_column_body_text"><strong>Administrator:</strong> <xsl:value-of select="Author" /><br /><strong>Date:</strong> <xsl:value-of select="TestDate" /> (UTC)<br /><strong>PTS Version:</strong> <xsl:value-of select="Version" /><br /><strong>Test Notes:</strong><br /><xsl:value-of select="TestNotes" /></div></div></div></xsl:for-each>
				</div>
				<h1>Results Overview</h1>
				<div class="pts_results_table_box">
						<div class="pts_table_col">
							<div class="pts_table_cell_header" style="text-align: left; text-indent: 2px;">Test</div>

							<xsl:for-each select="PhoronixTestSuite/Benchmark">
								<a><xsl:attribute name="href">#test-<xsl:value-of select="position()" /></xsl:attribute><xsl:attribute name="title"><xsl:value-of select="Attributes" /></xsl:attribute><div class="pts_table_cell_property"><xsl:value-of select="Name" /></div></a>
							</xsl:for-each>
						</div>
					<xsl:for-each select="PhoronixTestSuite/Benchmark[position()=1]/Results/Group/Entry">
						<div class="pts_table_col">
							<div class="pts_table_cell_header"><xsl:value-of select="Identifier" /></div>
						<xsl:variable name="this_identify" select="Identifier" />
							<xsl:for-each select="/PhoronixTestSuite/Benchmark/Results/Group/Entry[Identifier=$this_identify]">
								<div class="pts_table_cell"><xsl:value-of select="Value" /></div>
							</xsl:for-each>
						</div>
					</xsl:for-each>
				</div>
			</div>

			<div class="pts_box">
				<h1>Test Results</h1>

				<div id="pts_benchmark_area">
					<xsl:for-each select="PhoronixTestSuite/Benchmark">
						<div class="pts_benchmark_bar"><a><xsl:attribute name="name">test-<xsl:value-of select="position()" /></xsl:attribute></a><span class="pts_benchmark_bar_header"><xsl:value-of select="Name"/></span> <span class="pts_benchmark_bar_version"><xsl:value-of select="Version"/></span><br /><strong><xsl:value-of select="Attributes"/></strong></div>
						<div class="pts_benchmark_text">
								<xsl:if test="not(contains(ResultFormat,'MULTI_'))">
									<xsl:for-each select="Results/Group">
										<div style="padding: 5px 0;">
											<xsl:for-each select="Entry">
												<strong><xsl:value-of select="Identifier" />:</strong><span style="padding-left: 5px;"><xsl:value-of select="Value"/></span><br />
											</xsl:for-each>
										</div>
									</xsl:for-each>
								</xsl:if>
						</div>
						<div class="pts_benchmark_img_area"><!-- GRAPH TAGS --></div>
					</xsl:for-each>
				</div>


			</div>
		</div>
		<div id="pts_header_bottom">
			<div id="pts_header_bottom_center">The <a href="http://www.phoronix-test-suite.com/">Phoronix Test Suite</a> is developed by <a href="http://www.phoronix.com/">Phoronix</a>, an Internet resource devoted to Linux hardware reviews, video driver articles, and much more.<br />Copyright &#xA9; 2008 - 2009 by <a href="http://www.phoronix-media.com/">Phoronix Media</a>.</div>
		</div>

	</body>
</html>
</xsl:template>
</xsl:stylesheet>
