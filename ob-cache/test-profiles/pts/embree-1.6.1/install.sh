#!/bin/sh
tar -xf embree-4.3.0.sycl.x86_64.linux.tar.gz
unzip -o asian_dragon.zip
unzip -o asian_dragon_obj.zip
unzip -o crown.zip
echo "#!/bin/sh
LD_LIBRARY_PATH=lib:\$LD_LIBRARY_PATH ./bin/embree_\$@ --threads \$NUM_CPU_CORES > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > embree
chmod +x embree
