#!/bin/sh

unzip -o oa080.zip
cd openarena-0.8.0/baseoa
tar -xvf ../../openarena-benchmark-files-3.tar.gz
cd ../..

echo "#!/bin/sh
cd openarena-0.8.0/

case \$OS_ARCH in
	\"x86_64\" )
	./openarena.x86_64 \$@ > \$LOG_FILE 2>&1
	;;
	* )
	./openarena.i386 \$@ > \$LOG_FILE 2>&1
	;;
esac
cat \$LOG_FILE | grep fps" > openarena
chmod +x openarena
