#!/bin/sh

unzip -o openarena-0.8.8.zip
unzip -o openarena-088-1.zip
mv pts-openarena-088.cfg openarena-0.8.8/baseoa

echo "#!/bin/sh
cd openarena-0.8.8/

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
