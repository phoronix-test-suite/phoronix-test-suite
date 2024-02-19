#!/bin/sh
tar -zxf NAMD_3.0b6_Linux-x86_64-multicore.tar.gz
tar -zxf NAMD_3.0b6_Linux-x86_64-multicore-AVX512.tar.gz
unzip -o f1atpase.zip
unzip -o stmv.zip
sed -i 's/\/usr\/tmp/\/tmp/g' f1atpase/f1atpase.namd
cd ~
echo "#!/bin/bash
if grep avx512 /proc/cpuinfo > /dev/null
then
	cd NAMD_3.0b6_Linux-x86_64-multicore-AVX512
else
	cd NAMD_3.0b6_Linux-x86_64-multicore
fi
./namd3 +p\$NUM_CPU_CORES +setcpuaffinity \$@ > \$LOG_FILE 2>&1" > namd
chmod +x namd
