#!/bin/sh

# Nexuiz 2.4.2

unzip -o nexuiz-242.zip
mv Nexuiz/ Nexuiz_/

echo "#!/bin/sh
cd Nexuiz_/
if [ \$OS_TYPE = \"MacOSX\" ]
then
	./Nexuiz.app/Contents/MacOS/nexuiz-osx-agl +exec normal.cfg \$@ > \$LOG_FILE 2>&1
else
	./nexuiz-linux-glx.sh +exec normal.cfg \$@ > \$LOG_FILE 2>&1
fi
cat \$LOG_FILE | grep fps" > nexuiz
chmod +x nexuiz

cd Nexuiz_/
chmod -w data
