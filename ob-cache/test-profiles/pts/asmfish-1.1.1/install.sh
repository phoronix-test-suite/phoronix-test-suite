#!/bin/sh

unzip -o asmFish-20180723.zip
cd asmFish-master
chmod +x LinuxOS_binaries/*
cd ~

echo "#!/bin/sh
cd asmFish-master
if [ \$OS_ARCH = \"aarch64\" ]
then
	echo \"bench 1024 \$NUM_CPU_CORES 26\" | ./LinuxOS_binaries/armFishL_2018-07-23_v8 > \$LOG_FILE 2>&1
else
	echo \"bench 1024 \$NUM_CPU_CORES 26\" |  ./LinuxOS_binaries/asmFishL_2018-07-23_base > \$LOG_FILE 2>&1
fi" > asmfish
chmod +x asmfish
