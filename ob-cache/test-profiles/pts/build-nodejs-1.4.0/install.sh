#!/bin/sh
echo "#!/bin/sh
cd node-v21.7.2
make -s -j \$NUM_CPU_CORES 2>&1
echo \$? > ~/test-exit-status" > build-nodejs
chmod +x build-nodejs
