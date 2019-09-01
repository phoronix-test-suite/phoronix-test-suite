#!/bin/bash

tar -xzf powertop-2.2.tar.gz
cd powertop-2.2
./configure
make
echo $? > ~/install-exit-status

cd ..

cat > powertop-wakeups << EOT
#!/bin/sh

rm  pt_pts.csv
./powertop-2.2/src/powertop --csv=pt_pts.csv --time=60 > /dev/null 2>&1
cat pt_pts.csv > \$LOG_FILE
EOT

chmod +x powertop-wakeups
