#!/bin/sh

7z x 7z2106-src.7z -aoa
cd CPP/7zip/Bundles/Alone2
make -j $NUM_CPU_CORES -f makefile.gcc
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
./CPP/7zip/Bundles/Alone2/_o/7zz b > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > compress-7zip
chmod +x compress-7zip
