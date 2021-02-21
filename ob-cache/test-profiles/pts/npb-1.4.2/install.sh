#!/bin/bash

tar -zxf NPB3.4.1.tar.gz
MPI_CC=mpicc
if [ ! "X$MPI_PATH" = "X" ] && [ -d $MPI_PATH ] && [ -d $MPI_INCLUDE ] && [ -x $MPI_CC ] && [ -e $MPI_LIBS ]
then
	# PRE-SET MPI
	echo "Using pre-set environment variables."
elif [ -d /usr/lib/x86_64-linux-gnu/openmpi/lib/openmpi ]
then
	# OpenMPI
	MPI_PATH=/usr/lib/x86_64-linux-gnu/openmpi/lib/openmpi/
	MPI_INCLUDE=/usr/include/openmpi/
	MPI_LIBS=/usr/lib/x86_64-linux-gnu/libmpi.so
	MPI_CC=/usr/bin/mpicc.openmpi
	MPI_VERSION=`$MPI_CC -showme:version 2>&1 | grep MPI | cut -d "(" -f1  | cut -d ":" -f2`
elif [ -d /usr/lib/x86_64-linux-gnu/openmpi/lib/openmpi3 ]
then
	# OpenMPI
	MPI_PATH=/usr/lib/x86_64-linux-gnu/openmpi/lib/openmpi3/
	MPI_INCLUDE=/usr/include/
	MPI_LIBS=/usr/lib/x86_64-linux-gnu/libmpi.so
	MPI_CC=/usr/bin/mpicc.openmpi
	MPI_VERSION=`$MPI_CC -showme:version 2>&1 | grep MPI | cut -d "(" -f1  | cut -d ":" -f2`
elif [ -d /usr/lib/openmpi/include ]
then
	# OpenMPI
	MPI_PATH=/usr/lib/openmpi
	MPI_INCLUDE=/usr/lib/openmpi/include
	MPI_LIBS=/usr/lib/openmpi/lib/libmpi.so
	MPI_CC=/usr/bin/mpicc.openmpi
	MPI_VERSION=`$MPI_CC -showme:version 2>&1 | grep MPI | cut -d "(" -f1  | cut -d ":" -f2`
elif [ -d /usr/lib/mpich/include ]
then
	# MPICH
	MPI_PATH=/usr/lib/mpich
	MPI_INCLUDE=/usr/lib/mpich/include
	MPI_LIBS=/usr/lib/mpich/lib/libmpich.so.1.0
	MPI_CC=/usr/bin/mpicc.mpich
	MPI_VERSION=`$MPI_CC -v 2>&1 | grep "MPICH version"` 
elif [ -d /usr/include/mpich2 ]
then
	# MPICH2
	MPI_PATH=/usr/include/mpich2
	MPI_INCLUDE=/usr/include/mpich2
	MPI_LIBS=/usr/lib/mpich2/lib/libmpich.so
	MPI_CC=/usr/bin/mpicc.mpich2
	MPI_VERSION=`$MPI_CC -v 2>&1 | grep "MPICH2 version"` 
fi

if [ ! "X$MPI_VERSION" = "X" ]
then
	echo $MPI_VERSION > ~/install-footnote
fi

if [ "X$CFLAGS_OVERRIDE" = "X" ]
then
          CFLAGS="$CFLAGS -O3 -march=native"
else
          CFLAGS="$CFLAGS_OVERRIDE"
fi
CCVERSION=`cc -dumpversion`
echo "Compiler Version is $CCVERSION"
if [ "$CCVERSION" -gt 9 ]; then
    CFLAGS="$CFLAGS -fallow-argument-mismatch"
fi

# Should have all the necessary variables for both OpenMP and MPI tests
echo "F77 = gfortran
MPIFC = mpif90
MPIF77 = mpif77
FLINK	= \$(MPIF77)
FMPI_LIB  = -L$MPI_LIBS
FMPI_INC = -I$MPI_INCLUDE
FFLAGS	= $CFLAGS
FLINKFLAGS = \$(FFLAGS)
MPICC = $MPI_CC
CLINK	= $MPI_CC
CMPI_LIB  = -L$MPI_LIBS
CMPI_INC = -I$MPI_INCLUDE
CFLAGS	= $CFLAGS
CLINKFLAGS = \$(CFLAGS)
CC	= cc -g
BINDIR	= ../bin
RAND   = randi8
C_LIB  = -lm
WTIME  = wtime.c
" > NPB3.4.1/NPB3.4-MPI/config/make.def

# Copy over OpenMP make for when using that...
cp NPB3.4.1/NPB3.4-MPI/config/make.def NPB3.4/NPB3.4-OMP/config/make.def

cd ~/NPB3.4.1/NPB3.4-MPI/

make bt CLASS=A
make bt CLASS=C
make ep CLASS=C
make ep CLASS=D
make ft CLASS=A
make ft CLASS=B
make ft CLASS=C
make lu CLASS=A
make lu CLASS=C
make sp CLASS=A
make sp CLASS=B
make is CLASS=D
make mg CLASS=C
make cg CLASS=C
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd NPB3.4.1/NPB3.4-MPI/

if [ \"X\$NUM_CPU_PHYSICAL_CORES\" = \"X\" ]
then
	NUM_THREADS=\$NUM_CPU_CORES
else
	NUM_THREADS=\$NUM_CPU_PHYSICAL_CORES
fi

if [ ! \"X\$HOSTFILE\" = \"X\" ] && [ -f \$HOSTFILE ]
then
	HOSTFILE=\"--hostfile \$HOSTFILE\"
elif [ -f /etc/hostfile ]
then
	HOSTFILE=\"--hostfile /etc/hostfile\"
else
	HOSTFILE=\"\"
fi

mpiexec --allow-run-as-root -np \$NUM_THREADS \$HOSTFILE ./bin/\$@.x > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > npb
chmod +x npb
