#!/bin/sh

tar -zxvf NAMD_2.13b1_Linux-x86_64-multicore.tar.gz
unzip -o f1atpase.zip
sed -i 's/\/usr\/tmp/\/tmp/g' f1atpase/f1atpase.namd

cd ~
echo "#!/bin/sh
cd NAMD_2.13b1_Linux-x86_64-multicore
./namd2 +p\$NUM_CPU_CORES +setcpuaffinity ../f1atpase/f1atpase.namd > \$LOG_FILE 2>&1" > namd
chmod +x namd
