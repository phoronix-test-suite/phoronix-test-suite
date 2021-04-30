#!/bin/sh

tar -xf png-samples-1.tar.xz
tar -xf KTX-Software-4.0.0-Linux.tar.bz2
echo \$? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd KTX-Software-4.0.0-Linux/bin
LD_LIBRARY_PATH=../lib/ ./toktx --t2 --threads \$NUM_CPU_CORES \$@ out.ktx ~/sample-4.png > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > toktx
chmod +x toktx
