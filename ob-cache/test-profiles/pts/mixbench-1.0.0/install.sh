#!/bin/sh

rm -f mixbench
rm -rf mixbench-source
tar -xjvf mixbench-20160606.tar.bz2
mv mixbench mixbench-source
cd mixbench-source

make
echo $? > ~/install-exit-status

cd ~/
echo "#!/bin/sh
cd mixbench-source
./mixbench-ocl-ro > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > mixbench
chmod +x mixbench
