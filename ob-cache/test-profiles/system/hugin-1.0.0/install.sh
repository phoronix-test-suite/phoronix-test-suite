#!/bin/sh

if which hugin_executor>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Hugin is not found on the system! This test profile needs a working hugin_executor in the PATH."
	echo 2 > ~/install-exit-status
fi

cd ~
echo "#!/bin/sh
cd pano-pto
hugin_executor -a --prefix=prefix -t \$NUM_CPU_CORES pano-original.pto 
hugin_executor -s --prefix=prefix -t \$NUM_CPU_CORES pano-original.pto 
echo \$? > ~/test-exit-status" > hugin
chmod +x hugin
