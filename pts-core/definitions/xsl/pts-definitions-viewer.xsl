<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
<xsl:template match="/">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Phoronix Test Suite - Definition Viewer</title>
	</head>
	<body>
		<div style="width: 90%; margin: 20px auto 10px; text-align: left;">
			<h1>Specifications</h1>
			<xsl:for-each select="PhoronixTestSuite/Definitions/Define">
				<h3><xsl:value-of select="Value" /></h3>
				<p><strong>Tag:</strong> <xsl:value-of select="Value" /><br />
				<strong>Internal Reference:</strong> <xsl:value-of select="Name" /><br />
				<strong>Required:</strong> <xsl:value-of select="Required" /><br />
				<strong>Description:</strong> <xsl:value-of select="Description" /></p>
			</xsl:for-each>
		</div>
		<div style="text-align: center; font-size: 12px;">Copyright &#xA9; 2008 - 2010 by <a href="http://www.phoronix-media.com/" style="text-decoration: none; color: #000;">Phoronix Media</a>.</div>
	</body>
</html>
</xsl:template>
</xsl:stylesheet>
