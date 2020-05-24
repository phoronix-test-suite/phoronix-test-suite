#!/bin/sh

tar -xf iozone3_465.tar
cd iozone3_465/src/current/

if [ "$OS_TYPE" = "BSD" ]
then
	make freebsd
else
	case $OS_ARCH in
		"x86_64" )
		make CFLAGS=-fcommon linux-AMD64
		;;
		* )
		make CFLAGS=-fcommon linux
		;;
	esac
fi
echo $? > ~/install-exit-status

echo "#!/bin/sh
iozone3_465/src/current/iozone \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > ~/iozone
chmod +x ~/iozone
