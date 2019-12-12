#!/bin/sh

[ -d Nexuiz_/data ] && chmod +w Nexuiz_/data
rm -rf Nexuiz Nexuiz_
unzip -o nexuiz-252.zip
mv Nexuiz Nexuiz_

echo "#!/bin/sh
cd Nexuiz_/
if [ \$OS_TYPE = \"MacOSX\" ]
then
	./Nexuiz.app/Contents/MacOS/nexuiz-osx-agl +exec effects-high.cfg \$@ > \$LOG_FILE 2>&1
else
	./nexuiz-linux-glx.sh +exec effects-high.cfg \$@ > \$LOG_FILE 2>&1
fi" > nexuiz
chmod +x nexuiz

cd Nexuiz_/
chmod -w data
