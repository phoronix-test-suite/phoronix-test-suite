#!/bin/sh

tar -xf iozone3_465.tar
cd iozone3_465/src/current/

case $OS_ARCH in
	"x86_64" )
	make linux-AMD64
	;;
	* )
	make linux
	;;
esac
echo $? > ~/install-exit-status

echo "#!/bin/sh
iozone3_465/src/current/iozone \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > ~/iozone
chmod +x ~/iozone
