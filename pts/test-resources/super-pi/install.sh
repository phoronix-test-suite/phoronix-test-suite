#!/bin/sh

tar -xvf super_pi.tar.gz

# make wrapper shell script for total line
echo "#!/bin/sh
./super_pi \$@ > \$LOG_FILE" > superpi
chmod +x superpi

