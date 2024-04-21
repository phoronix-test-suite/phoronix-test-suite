#!/bin/sh
echo "#!/bin/sh
cd php-8.3.4
make -s -j \$NUM_CPU_CORES 2>&1
echo \$? > ~/test-exit-status" > time-compile-php
chmod +x time-compile-php
