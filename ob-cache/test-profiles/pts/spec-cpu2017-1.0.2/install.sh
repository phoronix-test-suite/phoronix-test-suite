#!/bin/sh

mkdir mnt
mkdir cpu2017
fuseiso cpu2017-1_0_5.iso mnt 
cd mnt
./install.sh -f -d $HOME/cpu2017
cd ~
fusermount -u mnt

echo "#!/bin/bash
cd cpu2017
source shrc
cp -f config/Example-gcc-linux-x86.cfg config/pts.cfg

rm -f result/CPU2017.*.txt

runcpu --config=pts --define build_ncpus=\$NUM_CPU_PHYSICAL_CORES --define gcc_dir=/usr --action=build \$@

runcpu --config=pts --define build_ncpus=\$NUM_CPU_PHYSICAL_CORES --define gcc_dir=/usr \$@
echo \$? > ~/test-exit-status

cat result/CPU2017.*.txt > \$LOG_FILE
" > spec-cpu2017
chmod +x spec-cpu2017
