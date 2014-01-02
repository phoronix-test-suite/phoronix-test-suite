<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
<!--

This XSL file doesn't fully implement the PTS test profile specification as found in pts-core/library/pts-interfaces.php. 
Some tags can easily be added, and patches are welcome. Though not all of the specification can be implemented due to the Extends 
tag with Cascading Test Profiles (CTP) and other tags that require processing by pts-core.

-->
<xsl:template match="/">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Phoronix Test Suite - <xsl:value-of select="PhoronixTestSuite/TestInformation/Title" /></title>
	</head>
	<body>
		<div style="font-size: 27px; font-weight: bold; text-align: center; margin: 30px 0 0;"><xsl:value-of select="PhoronixTestSuite/TestInformation/Title" /></div>
		<div style="width: 90%; margin: 20px auto 10px; text-align: center; border: 2px solid #000; border-width: 1px 2px; min-width: 700px;">
			<div style="overflow: hidden; background-color: #000; color: #FFF; font-weight: bold;">
				<div style="width: 15%; float: left;">Test Version</div>
				<div style="width: 15%; float: left;">Testing Type</div>
				<div style="width: 15%; float: left;">Software Type</div>
				<div style="width: 55%; float: left;">Test Options</div>
			</div>
			<div style="overflow: hidden; border: 1px solid #000; border-width: 1px 0; padding-top: 2px;">
				<div style="width: 15%; float: left;"><xsl:value-of select="PhoronixTestSuite/TestInformation/Version" /></div>
				<div style="width: 15%; float: left;"><xsl:value-of select="PhoronixTestSuite/TestProfile/TestType" /></div>
				<div style="width: 15%; float: left;"><xsl:value-of select="PhoronixTestSuite/TestProfile/SoftwareType" /></div>
				<div style="width: 55%; float: right;">
					<xsl:for-each select="PhoronixTestSuite/TestSettings/Option">
						<p><strong><xsl:value-of select="DisplayName" /></strong><ul>
							<xsl:for-each select="Menu/Entry">
								<li><xsl:value-of select="Name" /></li>
							</xsl:for-each>
						</ul></p>
					</xsl:for-each>
				</div>
				<div style="width: 15%; float: left; clear: left; margin-top: 2px; background-color: #000; color: #FFF; font-weight: bold;">License</div>
				<div style="width: 15%; float: left; margin-top: 2px; background-color: #000; color: #FFF; font-weight: bold;">Status</div>
				<div style="width: 15%; float: left; margin-top: 2px; background-color: #000; color: #FFF; font-weight: bold;">Maintainer</div>
				<div style="width: 15%; float: left; clear: left;"><xsl:value-of select="PhoronixTestSuite/TestProfile/License" /></div>
				<div style="width: 15%; float: left;"><xsl:value-of select="PhoronixTestSuite/TestProfile/Status" /></div>
				<div style="width: 15%; float: left;"><xsl:value-of select="PhoronixTestSuite/TestProfile/Maintainer" /></div>
				<div style="width: 15%; float: left; clear: left; margin-top: 2px; background-color: #000; color: #FFF; font-weight: bold;">Profile Version</div>
				<div style="width: 15%; float: left; margin-top: 2px; background-color: #000; color: #FFF; font-weight: bold;">Scale</div>
				<div style="width: 15%; float: left; margin-top: 2px; background-color: #000; color: #FFF; font-weight: bold;">Proportion</div>
				<div style="width: 15%; float: left; clear: left;"><xsl:value-of select="PhoronixTestSuite/TestProfile/Version" /></div>
				<div style="width: 15%; float: left;"><xsl:value-of select="PhoronixTestSuite/TestInformation/ResultScale" /></div>
				<div style="width: 15%; float: left;"><xsl:value-of select="PhoronixTestSuite/TestInformation/Proportion" /></div>
				<div style="width: 45%; float: left; clear: left; margin-top: 2px; background-color: #000; color: #FFF; font-weight: bold;">Test Description</div>
				<div style="width: 45%; float: left; clear: left;"><xsl:value-of select="PhoronixTestSuite/TestInformation/Description" /></div>
			</div>
		</div>
		<div style="text-align: center; font-size: 12px;">Copyright &#xA9; 2008 - 2014 by <a href="http://www.phoronix-media.com/" style="text-decoration: none; color: #000;">Phoronix Media</a>.</div>
	</body>
</html>
</xsl:template>
</xsl:stylesheet>
