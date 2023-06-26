#!/bin/sh
unzip -o john-c7cacb14f5ed20aca56a52f1ac0cd4d5035084b6.zip
cd john-c7cacb14f5ed20aca56a52f1ac0cd4d5035084b6/src/
CFLAGS="-O3 -march=native $CFLAGS" ./configure --disable-native-tests --disable-opencl
CFLAGS="-O3 -march=native $CFLAGS" gmake -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~/
echo "#!/bin/sh
cd john-c7cacb14f5ed20aca56a52f1ac0cd4d5035084b6/run/
./john \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > john-the-ripper
chmod +x john-the-ripper
