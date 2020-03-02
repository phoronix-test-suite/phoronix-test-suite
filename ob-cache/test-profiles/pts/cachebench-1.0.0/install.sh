#!/bin/sh

tar -zxvf llcbench.tar.gz
cd llcbench/

make linux-mpich
make cache-bench
echo $? > ~/install-exit-status

cd ..

echo "#!/bin/sh
cd llcbench/cachebench/
./cachebench \$@ > \$LOG_FILE" > cachebench
chmod +x cachebench
