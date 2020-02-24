#!/bin/sh

tar -xjvf asmFish-20170919.tar.bz2
rm -rf asmFish-Bin
mv asmFish asmFish-Bin
cd asmFish-Bin
chmod +x asmFish/fasm
make
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd asmFish-Bin
echo \"bench 1024 \$NUM_CPU_CORES 26\" | ./Linux/asmFishL_*_base > \$LOG_FILE 2>&1" > asmfish
chmod +x asmfish
