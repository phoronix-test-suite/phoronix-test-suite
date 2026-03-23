#!/bin/sh
tar -xf 7z2500-src.tar.xz
cd CPP/7zip/Bundles/Alone2
CFLAGS="-O3 -march=native -Wno-error $CFLAGS" make -j $NUM_CPU_CORES -f makefile.gcc
EXIT_STATUS=$?
if [ $EXIT_STATUS -ne 0 ];
then
	CFLAGS="-O3 -Wno-error $CFLAGS" make -j $NUM_CPU_CORES -f makefile.gcc
	EXIT_STATUS=$?
fi
echo $EXIT_STATUS > ~/install-exit-status
cd ~
echo "#!/bin/sh
./CPP/7zip/Bundles/Alone2/_o/7zz b > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > compress-7zip
chmod +x compress-7zip
