#!/bin/sh

tar -xf firefox-84.0.source.tar.xz
cd firefox-84.0/
./mach create-mach-environment

echo "#!/bin/sh
cd firefox/
make -s -j \$NUM_CPU_CORES 2>&1
echo \$? > ~/test-exit-status" > build-firefox

chmod +x build-firefox

 
