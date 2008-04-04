#!/bin/sh

cd $1

if [ ! -f super_pi.tar.gz ]
  then
     wget http://www.phoronix-test-suite.com/benchmark-files/super_pi.tar.gz
fi

tar -xvf super_pi.tar.gz

# make wrapper shell script for total line
echo "#!/bin/sh
./super_pi \$@ | grep Total" > superpi
chmod +x superpi

