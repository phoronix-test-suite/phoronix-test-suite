#!/bin/sh

tar -xvf pts-warsow-1.tar.gz

case $OS_TYPE in
	"MacOSX" )
		unzip -o warsow_0.42_mac.zip
		cp -f pts-warsow-04.wd10 Warsow.app/Contents/Resources/Warsow/basewsw/demos
		cp -f pts-warsow.cfg Warsow.app/Contents/Resources/Warsow/basewsw
	;;
	"Linux" )
		unzip -o warsow_0.42_unified.zip
		cp -f pts-warsow-04.wd10 warsow_0.42_unified/basewsw/demos
		cp -f pts-warsow.cfg warsow_0.42_unified/basewsw/
		cd warsow_0.42_unified/
		chmod +x warsow.x86_64
		chmod +x warsow.i386
		cd ..
	;;
esac

echo "#!/bin/sh
rm -f .warsow/basewsw/1.log

case \$OS_TYPE in
	\"MacOSX\" )
		./Warsow.app/Contents/MacOS/Warsow \$@ > \$LOG_FILE 2>&1 # TODO: the Mac OS X binary doesn't seem to listen to command line arguments that are passed
	;;
	\"Linux\" )
		cd warsow_0.42_unified/
		if [ \$OS_ARCH = \"x86_64\" ]
		then
			./warsow.x86_64 \$@ > \$LOG_FILE 2>&1
		else
			./warsow.i386 \$@ > \$LOG_FILE 2>&1
		fi
	;;
esac" > warsow
chmod +x warsow
