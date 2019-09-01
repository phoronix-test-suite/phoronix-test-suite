#!/bin/sh

rm -rf minion-1.8/
tar -zxvf minion-1.8-linux.tar_.gz
cd minion-1.8
cd bin
cmake -DQUICK=1 ..
make minion -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
cd ~/
rm -rf minion-1.8/bin/CMakeFiles/

echo "#!/bin/sh
cd minion-1.8/
./bin/minion-quick \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > minion
chmod +x minion
