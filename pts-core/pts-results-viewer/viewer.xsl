<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
<xsl:template match="/">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Phoronix Test Suite - Results Viewer</title>
		<link href="pts-results-viewer/pts-viewer.css" rel="stylesheet" type="text/css" />
		<script src="pts-results-viewer/pts.js" type="text/javascript"></script>
	</head>
	<body>
		<div id="pts_header_top">
			<div id="pts_header_top_center">
				<div id="pts_header_top_title">Phoronix Test Suite</div>
				<div id="pts_header_top_link_group"><a href="http://www.phoronix-test-suite.com/">Phoronix Test Suite</a><a href="http://global.phoronix-test-suite.com/index.php?k=results">PTS Global</a></div>
			</div>
		</div>
		<div id="pts_container">

			<div class="pts_box">

				<h1><xsl:value-of select="PhoronixTestSuite/Suite/Title" /></h1>
				<p><xsl:value-of select="PhoronixTestSuite/Suite/Name" /> v<xsl:value-of select="PhoronixTestSuite/Suite/Version" /> (<xsl:value-of select="PhoronixTestSuite/Suite/Type" /> Benchmarks)</p>
				<p id="tsd_show" style="display: none;"><div class="pts_area_box"><p><xsl:value-of select="PhoronixTestSuite/Suite/Description"/></p><p style="font-size: 11px;"><em>This test is maintained by <xsl:value-of select="PhoronixTestSuite/Suite/Maintainer" />.</em></p><p><a onclick="switchShow('tsd_hidden');switchShow('tsd_show');">&lt;&lt; Hide Test Details</a></p></div></p>
				<p class="pts_benchmark_descriptor" id="tsd_hidden"><a onclick="switchShow('tsd_hidden');switchShow('tsd_show');hideObject('tips_show');showObject('tips_hidden');">Show Test Details &gt;&gt;</a></p>
				<xsl:variable name="idcount"><xsl:value-of select="count(PhoronixTestSuite/System)" /></xsl:variable>
				<xsl:variable name="idwidth"><xsl:value-of select="floor(820 div $idcount) - 1" /></xsl:variable> 
				<h1>System Hardware</h1>
				<div class="pts_chart_box">
				<xsl:for-each select="PhoronixTestSuite/System"><div class="pts_column_head"><xsl:attribute name="style">width: <xsl:value-of select="$idwidth" />px;</xsl:attribute><xsl:value-of select="AssociatedIdentifiers" /></div></xsl:for-each>
				<xsl:for-each select="PhoronixTestSuite/System"><div class="pts_column_body"><xsl:attribute name="style">width: <xsl:value-of select="$idwidth" />px;</xsl:attribute><xsl:value-of select="Hardware" /></div></xsl:for-each>
				</div>
				<h1>System Software</h1>
				<div class="pts_chart_box">
				<xsl:for-each select="PhoronixTestSuite/System"><div class="pts_column_head"><xsl:attribute name="style">width: <xsl:value-of select="$idwidth" />px;</xsl:attribute><xsl:value-of select="AssociatedIdentifiers" /></div></xsl:for-each>
				<xsl:for-each select="PhoronixTestSuite/System"><div class="pts_column_body"><xsl:attribute name="style">width: <xsl:value-of select="$idwidth" />px;</xsl:attribute><xsl:value-of select="Software" /></div></xsl:for-each>
				</div>
				<h1>Additional Details</h1>
				<div class="pts_chart_box">
				<xsl:for-each select="PhoronixTestSuite/System"><div class="pts_column_head"><xsl:attribute name="style">width: <xsl:value-of select="$idwidth" />px;</xsl:attribute><xsl:value-of select="AssociatedIdentifiers" /></div></xsl:for-each>
				<xsl:for-each select="PhoronixTestSuite/System"><div class="pts_column_body"><xsl:attribute name="style">width: <xsl:value-of select="$idwidth" />px;</xsl:attribute><strong>Author:</strong> <xsl:value-of select="AssociatedIdentifiers" /><br /><strong>Test Date/Time:</strong> <xsl:value-of select="TestDate" /><br /><strong>PTS Version:</strong> <xsl:value-of select="Version" /></div></xsl:for-each>
				</div>

			</div>

			<div class="pts_box">
				<h1>Test Results</h1>

				<div id="pts_benchmark_area">
					<xsl:for-each select="PhoronixTestSuite/Benchmark">
						<div class="pts_benchmark_bar"><span class="pts_benchmark_bar_header"><xsl:value-of select="Name"/></span> <span class="pts_benchmark_bar_version">v<xsl:value-of select="Version"/></span><br /><strong><xsl:value-of select="Attributes"/></strong></div>
						<div class="pts_benchmark_text">
							<xsl:for-each select="Results/Group">
								<div style="padding: 5px 0;">
									<xsl:for-each select="Entry">
										<strong><xsl:value-of select="Identifier"/>:</strong><span style="padding-left: 5px;"><xsl:value-of select="Value"/></span><br />
									</xsl:for-each>
								</div>
							</xsl:for-each>
						</div>
						<div class="pts_benchmark_img_area"><img width="580" height="300"><xsl:attribute name="src">http://www.phoronix-test-suite.com/pts-grapher/r/graph-remote.php?t=<xsl:value-of select="Name" />&amp;s=<xsl:value-of select="Attributes"/>&amp;n=<xsl:value-of select="Version"/>&amp;u=<xsl:value-of select="Scale"/>&amp;p=<xsl:value-of select="Proportion"/>&amp;i=<xsl:for-each select="Results/Group"><xsl:for-each select="Entry"><xsl:value-of select="Identifier"/>;</xsl:for-each></xsl:for-each>&amp;v=<xsl:for-each select="Results/Group"><xsl:for-each select="Entry"><xsl:value-of select="Value"/>;</xsl:for-each></xsl:for-each>
	</xsl:attribute></img></div>
					</xsl:for-each>
				</div>


			</div>
		</div>
		<div id="pts_header_bottom">
			<div id="pts_header_bottom_center">The <a href="http://www.phoronix-test-suite.com/">Phoronix Test Suite</a> is developed by <a href="http://www.phoronix.com/">Phoronix</a>, an Internet resource devoted to Linux hardware reviews, video driver articles, and much more.</div>
		</div>

	</body>
</html>
</xsl:template>
</xsl:stylesheet>
