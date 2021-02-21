#!/bin/sh

unzip -o asmFish-20180723.zip
cd asmFish-master
chmod +x MacOS_binaries/*
cd ~

echo "#!/bin/sh
cd asmFish-master/MacOS_binaries
echo \"bench 1024 \$NUM_CPU_CORES 26\" |  ./asmFishX_2018-07-23_base > \$LOG_FILE 2>&1" > asmfish
chmod +x asmfish
