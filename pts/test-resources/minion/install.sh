#!/bin/sh

tar -xvf minion-0.8.1-src.tar.gz
cd minion-0.8.1/
mkdir build
cd build/
cmake ..
make minion
cd ../..

echo "#!/bin/sh
cd minion-0.8.1/
./build/minion \$@ > \$LOG_FILE 2>&1" > minion
chmod +x minion
