#!/bin/sh

tar -jxvf Lightsmark2008.1.9.tar.bz2

echo "#!/bin/sh
case \$OS_ARCH in
	\"x86_64\" )
	cd Lightsmark2008.1.9/bin/pc-linux64/
	;;
	* )
	cd Lightsmark2008.1.9/bin/pc-linux32/
	;;
esac
./backend \$@ 2>&1" > lightsmark
chmod +x lightsmark
