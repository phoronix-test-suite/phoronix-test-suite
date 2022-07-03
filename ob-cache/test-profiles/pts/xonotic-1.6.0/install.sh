#!/bin/sh
rm -rf Xonotic_
unzip -o xonotic-0.8.5.zip
mv Xonotic Xonotic_

echo "#!/bin/sh
cd Xonotic_/
if [ \$OS_TYPE = \"MacOSX\" ]
then
	./Xonotic.app/Contents/MacOS/xonotic-osx-sdl \$@ > \$LOG_FILE 2>&1
else
	./xonotic-linux64-sdl \$@ > \$LOG_FILE 2>&1
fi" > xonotic
chmod +x xonotic
