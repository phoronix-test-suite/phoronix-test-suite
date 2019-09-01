#!/bin/sh

tar -xf BLAKE2-20170307.tar.xz
cd BLAKE2-20170307/bench
make
echo \$? > ~/install-exit-status
cd ~/

echo "#!/bin/sh
cd BLAKE2-20170307/bench
./blake2s > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > blake2
chmod +x blake2
