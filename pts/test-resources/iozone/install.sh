#!/bin/sh

tar -xvf iozone3_308.tar
cd iozone3_308/src/current/

case $OS_ARCH in
	"x86_64" )
	make linux-AMD64
	;;
	* )
	make linux
	;;
esac

echo "#!/bin/sh
iozone_ram=\$((\$SYS_MEMORY * 2))
iozone3_308/src/current/iozone -s \${iozone_ram}M \$@ > \$LOG_FILE 2>&1" > ../../../iozone
chmod +x ../../../iozone
