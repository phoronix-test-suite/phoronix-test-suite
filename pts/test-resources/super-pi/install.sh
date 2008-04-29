#!/bin/sh

cd $1

tar -xvf super_pi.tar.gz

# make wrapper shell script for total line
echo "#!/bin/sh
./super_pi \$@ | grep Total" > superpi
chmod +x superpi

