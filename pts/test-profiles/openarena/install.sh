#!/bin/sh

unzip -o oa081.zip
unzip -o oa085p.zip

echo "#!/bin/sh
cd openarena-0.8.1/

case \$OS_TYPE in
	\"MacOSX\")
		export HOME=\$DEBUG_REAL_HOME # TODO: Otherwise the game will segv
		./OpenArena.app/Contents/MacOS/openarena.ub \$@ > \$LOG_FILE 2>&1
	;;
	*)
		if [ \$OS_ARCH = \"x86_64\" ]
		then
			./openarena.x86_64 \$@ > \$LOG_FILE 2>&1
		else
			./openarena.i386 \$@ > \$LOG_FILE 2>&1
		fi
	;;
esac" > openarena
chmod +x openarena

cp openarena-benchmark-files-6.zip openarena-0.8.1/baseoa
cd openarena-0.8.1/baseoa
unzip -o openarena-benchmark-files-6.zip
