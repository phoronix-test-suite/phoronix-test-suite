#!/bin/sh

# Nexuiz 2.4.2

unzip -o nexuiz-242.zip
mv Nexuiz/ Nexuiz_/

echo "#!/bin/sh
cd Nexuiz_/
if [ \$OS_TYPE = \"MacOSX\" ]
then
	./Nexuiz.app/Contents/MacOS/nexuiz-osx-agl +exec normal.cfg \$@ | grep fps
else
	./nexuiz-linux-glx.sh +exec normal.cfg \$@ | grep fps
fi" > nexuiz
chmod +x nexuiz

cd Nexuiz_/
chmod -w data
