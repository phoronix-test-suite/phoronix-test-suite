#!/bin/bash

if which flow>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Open Porous Media (OPM) is not found on the system. The flow binary could not be found in the PATH. Binaries for RHEL / Ubuntu and download instructions can be found @ https://opm-project.org/"
	echo 2 > ~/install-exit-status
	exit
fi

tar -xf opm-data-norne-202007.tar.xz
tar -xf Norne-4C.tar.gz

echo "#!/bin/sh

NPROC=\$2

MPIRUN_AS_ROOT_ARG=\"--allow-run-as-root\"
if [ \`whoami\` != \"root\" ]
then
  MPIRUN_AS_ROOT_ARG=\"\"
fi

if [ \$1 = \"flow_mpi_norne\" ]
then
	cd norne
	nice mpirun \$MPIRUN_AS_ROOT_ARG -np \$NPROC --report-bindings \$HOSTFILE flow NORNE_ATW2013.DATA --tolerance-mb=1e-5 --max-strict-iter=4 > \$LOG_FILE 2>&1
elif [ \$1 = \"flow_mpi_norne_4c\" ]
then
	cd Norne-4C
	nice mpirun \$MPIRUN_AS_ROOT_ARG -np \$NPROC --report-bindings \$HOSTFILE flow NORNE_ATW2013_4C_MSW.DATA --tolerance-mb=1e-5 --max-strict-iter=4 > \$LOG_FILE 2>&1
fi

flow --version > ~/pts-footnote

# echo \$? > ~/test-exit-status" > opm
chmod +x opm
