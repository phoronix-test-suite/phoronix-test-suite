#!/bin/sh

tar -xf embree-3.6.1.x86_64.linux.tar.gz
unzip -o asian_dragon.zip
unzip -o asian_dragon_obj.zip
unzip -o crown.zip

echo "#!/bin/sh
./embree-3.6.1.x86_64.linux/bin/\$@ --threads \$NUM_CPU_CORES > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > embree
chmod +x embree
