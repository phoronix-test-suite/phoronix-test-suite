<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
<xsl:template match="/">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Phoronix Test Suite - <xsl:value-of select="PhoronixTestSuite/SuiteInformation/Title" /></title>
	</head>
	<body>
		<div style="font-size: 27px; font-weight: bold; text-align: center; margin: 30px 0 0;"><xsl:value-of select="PhoronixTestSuite/SuiteInformation/Title" /></div>
		<div style="width: 90%; margin: 20px auto 10px; text-align: center; border: 2px solid #000; border-width: 1px 2px; min-width: 700px;">
			<div style="overflow: hidden; background-color: #000; color: #FFF; font-weight: bold;">
				<div style="width: 15%; float: left;">Version</div>
				<div style="width: 15%; float: left;">Testing Type</div>
				<div style="width: 15%; float: left;">Maintainer</div>
				<div style="width: 55%; float: left;">Contained Tests</div>
			</div>
			<div style="overflow: hidden; border: 1px solid #000; border-width: 1px 0; padding-top: 2px;">
				<div style="width: 15%; float: left;"><xsl:value-of select="PhoronixTestSuite/SuiteInformation/Version" /></div>
				<div style="width: 15%; float: left;"><xsl:value-of select="PhoronixTestSuite/SuiteInformation/TestType" /></div>
				<div style="width: 15%; float: left;"><xsl:value-of select="PhoronixTestSuite/SuiteInformation/Maintainer" /></div>
				<div style="width: 55%; float: right;">

					<xsl:for-each select="PhoronixTestSuite/RunTest">
						<p><xsl:value-of select="Test" /><br /><em><xsl:value-of select="Description" /></em></p>
					</xsl:for-each>

				</div>
				<div style="width: 45%; float: left; clear: left; margin-top: 2px; background-color: #000; color: #FFF; font-weight: bold;">Description</div>
				<div style="width: 45%; float: left; clear: left; font-style: italic;"><xsl:value-of select="PhoronixTestSuite/SuiteInformation/Description" /></div>
			</div>
		</div>
		<div style="text-align: center; font-size: 12px;">Copyright &#xA9; 2008 - 2010 by <a href="http://www.phoronix-media.com/" style="text-decoration: none; color: #000;">Phoronix Media</a>.</div>
	</body>
</html>
</xsl:template>
</xsl:stylesheet>
