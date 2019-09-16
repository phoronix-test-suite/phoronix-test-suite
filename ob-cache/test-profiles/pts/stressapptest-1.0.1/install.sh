#!/bin/sh

unzip -o v1.0.9.zip
cd stressapptest-1.0.9/
./configure
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~

echo "#!/bin/bash
cd stressapptest-1.0.9/
./src/stressapptest \$@ -l \$LOG_FILE" > stressapptest
chmod +x stressapptest
