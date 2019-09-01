#!/bin/sh

tar -xvjf rt-tests-0.84.tar.bz2
cd rt-tests-0.84
make all

cd ~
echo "#!/bin/sh
cd rt-tests-0.84/
./cyclictest \$@ > \$LOG_FILE 2>&1" > cyclictest
chmod +x cyclictest
