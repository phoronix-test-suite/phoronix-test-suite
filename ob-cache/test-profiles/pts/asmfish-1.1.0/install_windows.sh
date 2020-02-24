#!/bin/sh

unzip -o asmFish-20180723.zip

echo "#!/bin/sh
cd asmFish-master
echo \"bench 1024 \$NUM_CPU_CORES 26\" |  ./asmFishW_2018-07-23_base.exe > \$LOG_FILE 2>&1" > asmfish
chmod +x asmfish
