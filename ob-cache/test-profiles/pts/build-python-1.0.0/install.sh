#!/bin/sh
echo "#!/bin/sh
cd Python-3.10.6
make -j \$NUM_CPU_CORES 2>&1
echo \$? > ~/test-exit-status" > build-python
chmod +x build-python
