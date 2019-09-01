#!/bin/sh

echo "#!/bin/sh
cd firefox/
make -s -j \$NUM_CPU_JOBS 2>&1
echo \$? > ~/test-exit-status" > build-firefox

chmod +x build-firefox

 
