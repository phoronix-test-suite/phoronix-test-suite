#!/bin/sh

chmod +x cp2k-8.2-Linux-x86_64.ssmp
tar -xjf cp2k-8.2.tar.bz2

mv cp2k-8.2/benchmarks .

echo "#!/bin/bash

./cp2k-8.2-Linux-x86_64.ssmp \$@ > \$LOG_FILE 2>&1" > cp2k
chmod +x cp2k
