#!/bin/sh

# tar -xf level-zero-tests-master-20200404.tar.xz
# For now use the Git clone otherwise the third party Google test dir doesn't get initialized
git clone https://github.com/oneapi-src/level-zero-tests/ level-zero-tests-master
git checkout c51833ac2094fcd11f4ed875e6224c5e76c26daf

cd level-zero-tests-master/
mkdir build
cd build
cmake -D CMAKE_INSTALL_PREFIX=$PWD/../out ..
cmake --build . --config Release --target install
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd level-zero-tests-master/out
LD_LIBRARY_PATH=/usr/local/lib:\$LD_LIBRARY_PATH ./\$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > oneapi-level-zero
chmod +x oneapi-level-zero
