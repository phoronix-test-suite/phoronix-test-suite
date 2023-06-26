#!/bin/sh
echo "#!/bin/sh
cd node-v19.8.1
make -s -j \$NUM_CPU_CORES 2>&1
echo \$? > ~/test-exit-status" > build-nodejs
chmod +x build-nodejs
