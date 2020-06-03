#!/bin/sh

tar -jxvf Lightsmark2008.2.0.tar.bz2

echo "#!/bin/sh
case \$OS_ARCH in
	\"x86_64\" )
	cd Lightsmark2008.2.0/bin/pc-linux64/
	;;
	* )
	cd Lightsmark2008.2.0/bin/pc-linux32/
	;;
esac
./backend \$@ > \$LOG_FILE 2>&1" > lightsmark
chmod +x lightsmark
