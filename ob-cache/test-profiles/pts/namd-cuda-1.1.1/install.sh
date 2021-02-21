#!/bin/sh

tar -zxvf NAMD_2.14_Linux-x86_64-multicore-CUDA.tar.gz
unzip -o f1atpase.zip
sed -i 's/\/usr\/tmp/\/tmp/g' f1atpase/f1atpase.namd

cd ~
echo "#!/bin/sh
cd NAMD_2.14_Linux-x86_64-multicore-CUDA
./namd2 +idlepoll +p\$NUM_CPU_CORES +setcpuaffinity ../f1atpase/f1atpase.namd > \$LOG_FILE 2>&1" > namd-cuda
chmod +x namd-cuda
