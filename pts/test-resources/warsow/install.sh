#!/bin/sh

tar -xvf pts-warsow-2.tar.gz

case $OS_TYPE in
	"MacOSX" )
		unzip -o warsow_0.5_mac_intel.zip
		mkdir -p Library/Application\ Support/Warsow-0.5/basewsw/demos
		cp -f pts-bardu.wd11 Library/Application\ Support/Warsow-0.5/basewsw/demos/
		cp -f pts-warsow.cfg Library/Application\ Support/Warsow-0.5/basewsw/
	;;
	"Linux" )
		unzip -o warsow_0.5_unified.zip
		mkdir -p basewsw/demos
		cp -f pts-bardu.wd11 basewsw/demos
		cp -f pts-warsow.cfg basewsw/
	;;
esac

echo "#!/bin/sh
rm -f .warsow/basewsw/1.log

case \$OS_TYPE in
	\"MacOSX\" )
		/Volumes/Warsow\ 0.5/Warsow\ SDL.app/Contents/MacOS/Warsow\ SDL \$@
		cat Library/Application\ Support/Warsow-0.5/basewsw/pts-log.log > \$LOG_FILE
	;;
	\"Linux\" )
		if [ \$OS_ARCH = \"x86_64\" ]
		then
			./warsow.x86_64 \$@ > \$LOG_FILE 2>&1
		else
			./warsow.i386 \$@ > \$LOG_FILE 2>&1
		fi
		cat ~/.warsow/basewsw/pts-log.log > \$LOG_FILE
	;;
esac" > warsow
chmod +x warsow
