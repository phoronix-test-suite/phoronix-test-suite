#!/bin/sh

tar -xvf linux-2625-config.tar.gz

echo "#!/bin/sh

rm -rf linux-2.6.25/
tar -xjf linux-2.6.25.tar.bz2

case \$OS_ARCH in
	\"x86_64\" )
	cp -f linux-2625-config-x86_64 linux-2.6.25/.config
	;;
	* )
	cp -f linux-2625-config-x86 linux-2.6.25/.config
	;;
esac

cd linux-2.6.25/
sleep 3
\$TIMER_START
make -s -j \$NUM_CPU_JOBS 2>&1
echo \$? > ~/test-exit-status
\$TIMER_STOP" > time-compile-kernel

chmod +x time-compile-kernel
