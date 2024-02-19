#!/bin/sh
unzip -o cryptopp880.zip
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
echo "#!/bin/sh
./cryptest.exe \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status" > cryptopp
chmod +x cryptopp
