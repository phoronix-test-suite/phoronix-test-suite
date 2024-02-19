#!/bin/sh
tar -zxf NAMD_3.0b6_MacOS-universal-multicore.tar.gz
unzip -o f1atpase.zip
unzip -o stmv.zip
sed -i 's/\/usr\/tmp/\/tmp/g' f1atpase/f1atpase.namd
cd ~
echo "#!/bin/sh
cd NAMD_3.0b6_MacOS-universal-multicore/
./namd3 +p\$NUM_CPU_CORES +setcpuaffinity \$@ > \$LOG_FILE 2>&1" > namd
chmod +x namd
