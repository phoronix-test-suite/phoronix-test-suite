#!/bin/sh

cp -f linux-2624-config $1

cd $1

if [ ! -f linux-2.6.24.tar.bz2 ]
  then
     wget http://www.kernel.org/pub/linux/kernel/v2.6/linux-2.6.24.tar.bz2 -O linux-2.6.24.tar.bz2
fi

echo "#!/bin/sh

if [ ! -f linux-2.6.24.tar.bz2 ]
  then
	echo \"Linux Kernel Not Downloaded... Build Fails.\"
	exit
fi

rm -rf linux-2.6.24/
tar -xjf linux-2.6.24.tar.bz2
cp -f linux-2624-config linux-2.6.24/.config
cd linux-2.6.24/
sleep 3
/usr/bin/time -f \"Kernel Build Time: %e Seconds\" make -s -j \$NUM_CPU_JOBS 2>&1 | grep Seconds" > time-compile-kernel

chmod +x time-compile-kernel
