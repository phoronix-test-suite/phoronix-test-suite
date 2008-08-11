#!/bin/sh

unzip -o oa080.zip
cd openarena-0.8.0/baseoa
tar -xvf ../../openarena-benchmark-files-3.tar.gz
cd ../..

echo "#!/bin/sh
cd openarena-0.8.0/

case \$OS_ARCH in
	\"x86_64\" )
	./openarena.x86_64 \$@ 2>&1 | grep fps
	;;
	* )
	./openarena.i386 \$@ 2>&1 | grep fps
	;;
esac" > openarena
chmod +x openarena
