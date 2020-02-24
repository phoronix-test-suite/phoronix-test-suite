#!/bin/sh
unzip -o jpeg-test-1.zip
tar -xzvf libjpeg-turbo-1.5.3.tar.gz
cd libjpeg-turbo-1.5.3
./configure
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status

cd ~

echo "#!/bin/sh
cd libjpeg-turbo-1.5.3
./tjbench ../jpeg-test-1.JPG -nowrite > \$LOG_FILE
echo \$? > ~/test-exit-status" > tjbench
chmod +x tjbench
