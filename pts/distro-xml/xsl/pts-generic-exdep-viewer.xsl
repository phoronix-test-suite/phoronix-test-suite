<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
<xsl:template match="/">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Phoronix Test Suite - External Dependencies</title>
	</head>
	<body>
		<div style="font-size: 27px; font-weight: bold; text-align: center; margin: 30px 0 0;">Phoronix Test Suite - External Dependencies</div>
		<div style="width: 90%; margin: 20px auto 10px; text-align: center; border: 2px solid #000; border-width: 1px 2px; min-width: 700px;">
			<div style="overflow: hidden; background-color: #000; color: #FFF; font-weight: bold;">
				<div style="width: 30%; float: left;">External Dependency Name</div>
				<div style="width: 20%; float: left;">Possible Package Names</div>
				<div style="width: 50%; float: left;">File / Directory Check</div>
			</div>
			<xsl:for-each select="PhoronixTestSuite/ExternalDependencies/Package">
				<div style="overflow: hidden; border: 1px solid #000; border-width: 1px 0;">
					<a><xsl:attribute name="name"><xsl:value-of select="GenericName" /></xsl:attribute></a>
					<div style="width: 30%; float: left;"><strong><xsl:value-of select="Title" /></strong><br />(<xsl:value-of select="GenericName" />)</div>
					<div style="width: 20%; float: left; background-color: #EFEFEF; min-height: 40px;"><xsl:value-of select="PossibleNames" /></div>
					<div style="width: 50%; float: left; font-style: italic;"><xsl:value-of select="FileCheck" /></div>
				</div>
			</xsl:for-each>
		</div>
		<div style="text-align: center; font-size: 12px;">Copyright &#xA9; 2008 - 2009 by <a href="http://www.phoronix-media.com/" style="text-decoration: none; color: #000;">Phoronix Media</a>.</div>
	</body>
</html>
</xsl:template>
</xsl:stylesheet>
