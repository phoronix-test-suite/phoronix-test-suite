#!/bin/sh
unzip -o jpeg-test-1.zip
tar -xzvf libjpeg-turbo-2.0.2.tar.gz
cd libjpeg-turbo-2.0.2
mkdir build
cd build
cmake ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~

echo "#!/bin/sh
cd libjpeg-turbo-2.0.2/build
./tjbench ../../jpeg-test-1.JPG -nowrite > \$LOG_FILE
echo \$? > ~/test-exit-status" > tjbench
chmod +x tjbench
