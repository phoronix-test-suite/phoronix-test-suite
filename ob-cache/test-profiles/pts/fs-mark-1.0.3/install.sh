#!/bin/sh

tar -zxvf fs_mark-3.3.tar.gz
cd fs_mark-3.3/
mkdir scratch

sed -i 's/-static //g' Makefile

make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

echo "#!/bin/sh
cd fs_mark-3.3/
mkdir scratch
./fs_mark \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
rm -rf scratch" > ~/fs-mark
chmod +x ~/fs-mark
