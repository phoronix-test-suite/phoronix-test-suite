#!/bin/sh

cp -f linux-2625-config-x86 $1
cp -f linux-2625-config-x86_64 $1

cd $1

echo "#!/bin/sh

if [ ! -f linux-2.6.25.tar.bz2 ]
  then
	echo \"Linux Kernel Not Downloaded... Build Fails.\"
	exit
fi

rm -rf linux-2.6.25/
tar -xjf linux-2.6.25.tar.bz2

case \`uname -m\` in
	\"x86_64\" )
	cp -f linux-2625-config-x86_64 linux-2.6.25/.config
	;;
	* )
	cp -f linux-2625-config-x86 linux-2.6.25/.config
	;;
esac

cd linux-2.6.25/
sleep 3
time -f \"Kernel Build Time: %e Seconds\" make -s -j \$NUM_CPU_JOBS 2>&1 | grep Seconds" > time-compile-kernel

chmod +x time-compile-kernel
