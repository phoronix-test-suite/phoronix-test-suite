#!/bin/bash

if which pip3 >/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Python pip3 is not found on the system! This test profile needs Python pip3 to proceed."
	echo 2 > ~/install-exit-status
fi

tar -xf pyhpc-benchmarks-3.0.tar.gz
cd pyhpc-benchmarks-3.0

# Lock the versions for this test profile version
pip3 install --user numpy==1.19.5 torch==1.10.0 tensorflow==2.6.0 numba==0.54.1 click==7.1.2 scipy==1.6.0 jax==0.2.24 jaxlib==0.1.73 aesara==2.2.4

cd ~
echo "#!/bin/sh
cd pyhpc-benchmarks-3.0
python3 run.py \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > pyhpc
chmod +x pyhpc
