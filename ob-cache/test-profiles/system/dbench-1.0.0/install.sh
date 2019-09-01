#!/bin/sh

if which dbench>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Dbench is not found on the system! This test profile needs a working installation in the PATH."
	echo 2 > ~/install-exit-status
fi

echo "#!/bin/sh
NPROCS=\$NUM_CPU_CORES dbench \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > dbench
chmod +x dbench
