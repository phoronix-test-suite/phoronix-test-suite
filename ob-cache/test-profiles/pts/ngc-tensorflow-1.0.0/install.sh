#!/bin/sh

if which docker>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Docker is not found on the system! This test profile needs a working docker installation in the PATH. Also make sure you log into the NVIDIA GPU Cloud (nvcr.io) with your user otherwise this test will fail."
	echo 2 > ~/install-exit-status
fi


echo "#!/bin/bash
HOME=\$DEBUG_REAL_HOME docker run --runtime=nvidia --rm nvcr.io/nvidia/tensorflow:18.09-py3 \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > ngc-tensorflow
chmod +x ngc-tensorflow
