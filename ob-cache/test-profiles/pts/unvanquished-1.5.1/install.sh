#!/bin/sh

unzip -o unvanquished-0.26.0-universal.zip
mv unvanquished unvanquished-game

unzip -o unvanquished-26-1.zip
mkdir ~/.unvanquished
mkdir ~/.unvanquished/demos
mv pts26.dm_86 ~/.unvanquished/demos

cd ~

echo "#!/bin/sh
cd unvanquished-game/
if [ \$OS_TYPE = \"MacOSX\" ]
then
	./Unvanquished.app/Contents/Resources/Unvanquished/daemon +set fs_libpath \$HOME/unvanquished-game/ \$@ > \$LOG_FILE 2>&1
else
	./daemon \$@ > \$LOG_FILE 2>&1
fi" > unvanquished
chmod +x unvanquished
