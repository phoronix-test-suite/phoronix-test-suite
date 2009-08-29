#!/bin/sh

tar -xvf pts-warsow-2.tar.gz

case $OS_TYPE in
	"MacOSX" )
		unzip -o warsow_0.5_mac_intel.zip
		# TODO: fixup Mac support for Warsow 0.5
		cp -f pts-bardu.wd11 Warsow.app/Contents/Resources/Warsow/basewsw/demos
		cp -f pts-warsow.cfg Warsow.app/Contents/Resources/Warsow/basewsw
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
		./Warsow.app/Contents/MacOS/Warsow \$@ > \$LOG_FILE 2>&1 # TODO: the Mac OS X binary doesn't seem to listen to command line arguments that are passed
	;;
	\"Linux\" )
		if [ \$OS_ARCH = \"x86_64\" ]
		then
			./warsow.x86_64 \$@ > \$LOG_FILE 2>&1
		else
			./warsow.i386 \$@ > \$LOG_FILE 2>&1
		fi
	;;
esac" > warsow
chmod +x warsow
