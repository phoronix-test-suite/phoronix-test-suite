#!/bin/sh

tar -zxvf smallpt-1.tar.gz
g++ -fopenmp $CXXFLAGS smallpt.cpp -o smallpt-renderer
echo $? > ~/install-exit-status

echo "#!/bin/sh
./smallpt-renderer 100 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > smallpt
chmod +x smallpt
