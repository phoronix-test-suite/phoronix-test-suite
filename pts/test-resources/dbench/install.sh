#!/bin/sh

tar -xvf dbench-4.0.tar.gz
cd dbench-4.0/
./autogen.sh
./configure
make

echo "#!/bin/sh
cd dbench-4.0/
./dbench \$@ -c client.txt > \$LOG_FILE 2>&1" > ../dbench
chmod +x ../dbench
