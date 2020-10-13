#!/bin/sh

unzip -o mixbench-20200623.zip
cd mixbench-master

# Fix building for CUDA 11
sed -i 's/-gencode=arch=compute_30,code=\\"compute_30\\"/-gencode=arch=compute_70,code=\\"compute_70\\" -gencode=arch=compute_75,code=\\"compute_75\\"/g' Makefile

make
echo $? > ~/install-exit-status

cd ~/
echo "#!/bin/sh
cd mixbench-master
./\$1 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > mixbench
chmod +x mixbench
