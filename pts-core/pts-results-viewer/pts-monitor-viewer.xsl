<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
<xsl:template match="/">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Phoronix Test Suite - Results Viewer</title>
		<link href="pts-monitor-viewer/pts-viewer.css" rel="stylesheet" type="text/css" />
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
					<xsl:for-each select="PhoronixTestSuite/SystemMonitor/Entry">
						<p><img width="580" height="300"><xsl:attribute name="src"><xsl:value-of select="Identifier" />.png</xsl:attribute></img></p>
					</xsl:for-each>
			</div>
		</div>
		<div id="pts_header_bottom">
			<div id="pts_header_bottom_center">The <a href="http://www.phoronix-test-suite.com/">Phoronix Test Suite</a> is developed by <a href="http://www.phoronix.com/">Phoronix</a>, an Internet resource devoted to Linux hardware reviews, video driver articles, and much more.</div>
		</div>
	</body>
</html>
</xsl:template>
</xsl:stylesheet>
