#!/bin/sh

tar -xvf iozone3_315.tar
cd iozone3_315/src/current/

case $OS_ARCH in
	"x86_64" )
	make linux-AMD64
	;;
	* )
	make linux
	;;
esac

echo "#!/bin/sh
iozone3_315/src/current/iozone \$@ > \$LOG_FILE 2>&1" > ../../../iozone
chmod +x ../../../iozone
