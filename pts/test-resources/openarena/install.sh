#!/bin/sh

unzip -o oa076.zip
cd openarena-0.7.6/baseoa
tar -xvf ../../openarena-benchmark-files-2.tar.gz
cd ../..

echo "#!/bin/sh
cd openarena-0.7.6/

case \$OS_ARCH in
	\"x86_64\" )
	./openarena.x86_64 \$@ 2>&1 | grep fps
	;;
	* )
	./openarena.i386 \$@ 2>&1 | grep fps
	;;
esac" > openarena
chmod +x openarena
