#!/bin/sh

echo "#!/bin/sh
cd mysql-5.1.30/
make -s -j \$NUM_CPU_JOBS 2>&1" > build-mysql

chmod +x build-mysql
