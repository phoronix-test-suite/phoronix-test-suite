#!/bin/sh

tar -xvf llcbench.tar.gz
cd llcbench/

make linux-mpich
make cache-bench

cd ..

echo "#!/bin/sh
cd llcbench/cachebench/
./cachebench \$@ > \$LOG_FILE" > cachebench
chmod +x cachebench
