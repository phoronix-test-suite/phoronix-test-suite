#!/bin/sh
tar -xf helsing-1.0-beta2.tar.gz

cd helsing-1.0-beta2/helsing/
sed "s|^#define THREADS .*|#define THREADS $NUM_CPU_CORES|g" configuration.h > tmp
mv tmp configuration.h
case $OS_TYPE in
	"Solaris")
		sed "s|^LFLAGS := -Wl,--gc-sections .*|LFLAGS := |g" Makefile > tmp
		mv tmp Makefile
		gmake
	;;
	"BSD")
		gmake
	;;
	*)
		make
	;;
esac
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
./helsing-1.0-beta/helsing/helsing \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > ~/helsing
chmod +x ~/helsing
