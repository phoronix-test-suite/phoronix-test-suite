#!/bin/sh

unzip -o oa081.zip
cd openarena-0.8.1/baseoa
tar -xvf ../../openarena-benchmark-files-4.tar.gz
cd ../..

echo "#!/bin/sh
cd openarena-0.8.1/

case \$OS_TYPE in
	\"MacOSX\" )
		export HOME=\$DEBUG_REAL_HOME # TODO: Otherwise the game will segv
		./OpenArena.app/Contents/MacOS/openarena.ub \$@ > \$LOG_FILE 2>&1
	;;
	\"Linux\" )
		if [ \$OS_ARCH = \"x86_64\" ]
		then
			./openarena.x86_64 \$@ > \$LOG_FILE 2>&1
		else
			./openarena.i386 \$@ > \$LOG_FILE 2>&1
		fi
	;;
esac" > openarena
chmod +x openarena
