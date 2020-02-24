#!/bin/sh

tar -zxvf hpcc-1.5.0.tar.gz
cd hpcc-1.5.0

# Find MPI To Use
if [ ! "X$MPI_PATH" = "X" ] && [ -d $MPI_PATH ] && [ -d $MPI_INCLUDE ] && [ -x $MPI_CC ] && [ -e $MPI_LIBS ]
then
	# PRE-SET MPI
	echo "Using pre-set environment variables."
elif [ -d /usr/lib/openmpi/include ]
then
	# OpenMPI On Ubuntu
	MPI_PATH=/usr/lib/openmpi
	MPI_INCLUDE=/usr/lib/openmpi/include
	MPI_LIBS=/usr/lib/openmpi/lib/libmpi.so
	MPI_CC=/usr/bin/mpicc.openmpi
	MPI_VERSION=`$MPI_CC -showme:version 2>&1 | grep MPI | cut -d "(" -f1  | cut -d ":" -f2`
elif [ -d /usr/lib/x86_64-linux-gnu/openmpi/ ] && [ -d /usr/include/openmpi/ ]
then
        # OpenMPI On Debian
        MPI_PATH=/usr/lib/x86_64-linux-gnu/openmpi
        MPI_INCLUDE=/usr/include/openmpi/
        MPI_LIBS=/usr/lib/x86_64-linux-gnu/openmpi/lib/libmpi.so
        MPI_CC=/usr/bin/mpicc
        MPI_VERSION=`$MPI_CC -showme:version 2>&1 | grep MPI | cut -d "(" -f1  | cut -d ":" -f2`
elif [ -d /usr/lib64/openmpi ] && [ -x /usr/bin/mpicc ]
then
	# OpenMPI On Clear Linux
	MPI_PATH=/usr/lib64/openmpi
	MPI_INCLUDE=/usr/include/
	MPI_LIBS=/usr/lib64/libmpi.so
	MPI_CC=/usr/bin/mpicc
	MPI_VERSION=`$MPI_CC -showme:version 2>&1 | grep MPI | cut -d "(" -f1  | cut -d ":" -f2`
elif [ -d /usr/lib64/openmpi ] && [ -d /usr/lib64/openmpi/bin/mpicc ]
then
	# OpenMPI On RHEL
	MPI_PATH=/usr/lib64/openmpi
	MPI_INCLUDE=/usr/include/openmpi-x86_64/
	MPI_LIBS=/usr/lib64/openmpi/lib/libmpi.so
	MPI_CC=/usr/lib64/openmpi/bin/mpicc
	MPI_VERSION=`$MPI_CC -showme:version 2>&1 | grep MPI | cut -d "(" -f1  | cut -d ":" -f2`
elif [ -d /usr/lib/mpich/include ] && [ -x /usr/bin/mpicc.mpich ]
then
	# MPICH
	MPI_PATH=/usr/lib/mpich
	MPI_INCLUDE=/usr/lib/mpich/include
	MPI_LIBS=/usr/lib/mpich/lib/libmpich.so
	MPI_CC=/usr/bin/mpicc.mpich
	MPI_VERSION=`$MPI_CC -v 2>&1 | grep "MPICH version"`
elif [ -d /usr/lib/mpich/include ]
then
	# MPICH
	MPI_PATH=/usr/lib/mpich
	MPI_INCLUDE=/usr/lib/mpich/include
	MPI_LIBS=/usr/lib/libmpich.so.1.0
	MPI_CC=/usr/lib/mpich/bin/mpicc.mpich
	MPI_VERSION=`$MPI_CC -v 2>&1 | grep "MPICH version"`
elif [ -d /usr/include/mpich2 ]
then
	# MPICH2
	MPI_PATH=/usr/include/mpich2
	MPI_INCLUDE=/usr/include/mpich2
	MPI_LIBS=/usr/lib/mpich2/lib/libmpich.so
	MPI_CC=/usr/bin/mpicc.mpich2
	MPI_VERSION=`$MPI_CC -v 2>&1 | grep "MPICH2 version"`
elif [ -d /usr/include/mpich2-x86_64 ]
then
	# MPICH2
	MPI_PATH=/usr/include/mpich2-x86_64
	MPI_INCLUDE=/usr/include/mpich2-x86_64
	MPI_LIBS=/usr/lib64/mpich2/lib/libmpich.so
	MPI_CC=/usr/bin/mpicc
	MPI_VERSION=`$MPI_CC -v 2>&1 | grep "MPICH2 version"`
fi

# Find Linear Algebra Package To Use
if [ ! "X$LA_PATH" = "X" ] && [ -d $LA_PATH ] && [ -d $LA_INCLUDE ] && [ -e $LA_LIBS ]
then
	# PRE-SET MPI
	echo "Using pre-set environment variables."
elif [ -d /usr/lib/libblas ]
then
	# libblas
	LA_PATH=/usr/lib
	LA_INCLUDE=/usr/include
	LA_LIBS="-lblas"
	LA_VERSION="BLAS"
elif [ -d /usr/lib/openblas-base ]
then
	# OpenBLAS
	LA_PATH=/usr/lib/openblas-base
	LA_INCLUDE=/usr/include
	LA_LIBS=/usr/lib/openblas-base/libopenblas.so.0
	LA_VERSION="OpenBLAS"
elif [ -d /usr/lib/atlas-base ]
then
	# ATLAS
	LA_PATH=/usr/lib/atlas-base
	LA_INCLUDE=/usr/include
	LA_LIBS="-llapack -lf77blas -lcblas -latlas"
	LA_VERSION="ATLAS"
elif [ -d /usr/lib64/atlas ]
then
	# ATLAS
	LA_PATH=/usr/lib64/atlas
	LA_INCLUDE=/usr/include
	LA_LIBS="-L$LA_PATH -lblas"
	LA_VERSION="ATLAS"
elif [ -d /usr/lib/x86_64-linux-gnu/atlas ]
then
	# ATLAS on Ubuntu
	LA_PATH=/usr/lib/x86_64-linux-gnu/atlas
	LA_INCLUDE=/usr/include/x86_64-linux-gnu/
	LA_LIBS="-L$LA_PATH -lblas"
	LA_VERSION="ATLAS"
elif [ -d /usr/lib/x86_64-linux-gnu/blas ]
then
	# OpenBLAS on Ubuntu
	LA_PATH=/usr/lib/x86_64-linux-gnu/blas
	LA_INCLUDE=/usr/include/x86_64-linux-gnu/
	LA_LIBS="-L$LA_PATH -lblas"
	LA_VERSION="OpenBLAS"
fi

if [ ! "X$MPI_VERSION" = "X" ]
then
	VERSION_INFO=$MPI_VERSION
	if [ ! "X$LA_VERSION" = "X" ]
	then
		VERSION_INFO="$LA_VERSION + $VERSION_INFO"
	fi

	echo $VERSION_INFO > ~/install-footnote
fi

if [ "X$CFLAGS_OVERRIDE" = "X" ]
then
          CFLAGS="$CFLAGS -O3 -march=native"
else
          CFLAGS="$CFLAGS_OVERRIDE"
fi

if [ "X$MPI_LD" = "X" ]
then
	MPI_LD=$MPI_CC
fi

# Make.pts generation
echo "
SHELL        = /bin/sh
CD           = cd
CP           = cp
LN_S         = ln -s
MKDIR        = mkdir
RM           = /bin/rm -f
TOUCH        = touch
ARCH         = \$(arch)
TOPdir       = ../../..
INCdir       = \$(TOPdir)/include
BINdir       = \$(TOPdir)/bin/\$(ARCH)
LIBdir       = \$(TOPdir)/lib/\$(ARCH)
HPLlib       = \$(LIBdir)/libhpl.a 

# MPI

MPdir        = $MPI_PATH
MPinc        = -I$MPI_INCLUDE
MPlib        = $MPI_LIBS

# BLAS or VSIPL

LAdir        = $LA_PATH
LAinc        = -I$LA_INCLUDE
LAlib        = $LA_LIBS

# F77 / C interface 

F2CDEFS      =

# HPL includes / libraries / specifics

HPL_INCLUDES = -I\$(INCdir) -I\$(INCdir)/\$(ARCH) \$(LAinc) \$(MPinc)
HPL_LIBS     = \$(HPLlib) \$(LAlib) \$(MPlib) -lm
#HPL_OPTS     = -DHPL_CALL_CBLAS
HPL_DEFS     = \$(F2CDEFS) \$(HPL_OPTS) \$(HPL_INCLUDES)
CC           = $MPI_CC
CCNOOPT      = \$(HPL_DEFS)
CCFLAGS      = \$(HPL_DEFS) -fomit-frame-pointer $CFLAGS -funroll-loops
LINKER       = $MPI_LD
LINKFLAGS    = $LDFLAGS
ARCHIVER     = ar
ARFLAGS      = r
RANLIB       = echo
" > hpl/Make.pts

cd hpl/
make arch=pts
cd ..
make arch=pts
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd hpcc-1.5.0

if [ \"X\$MPI_NUM_THREADS\" = \"X\" ]
then
	MPI_NUM_THREADS=\$NUM_CPU_CORES
fi

if [ ! \"X\$HOSTFILE\" = \"X\" ] && [ -f \$HOSTFILE ]
then
	\$HOSTFILE=\"--hostfile \$HOSTFILE\"
elif [ -f /etc/hostfile ]
then
	\$HOSTFILE=\"--hostfile /etc/hostfile\"
fi

# HPL.dat generation
# http://pic.dhe.ibm.com/infocenter/lnxinfo/v3r0m0/index.jsp?topic=%2Fliaai.hpctune%2Fbaselinehpcc_gccatlas.htm

PQ=0
P=\$(echo \"scale=0;sqrt(\$MPI_NUM_THREADS)\" |bc -l)
Q=\$P
PQ=\$((\$P*\$Q))

while [ \$PQ -ne \$MPI_NUM_THREADS ]; do
    Q=\$((\$MPI_NUM_THREADS/\$P))
    PQ=\$((\$P*\$Q))
    if [ \$PQ -ne \$MPI_NUM_THREADS ] && [ \$P -gt 1 ]; then P=\$((\$P-1)); fi
done

if [ \"X\$N\" = \"X\" ] || [ \"X\$NB\" = \"X\" ]
then
	# SYS_MEMORY * about .62% of that, go from MB to bytes and divide by 8
	N=\$(echo \"scale=0;sqrt(\${SYS_MEMORY}*0.62*1048576/8)\" |bc -l)
	NB=\$((256 - 256 % \$MPI_NUM_THREADS))
	N=\$((\$N - \$N % \$NB))
fi

echo \"HPLinpack benchmark input file
Innovative Computing Laboratory, University of Tennessee
HPL.out      output file name (if any)
6            device out (6=stdout,7=stderr,file)
1            # of problems sizes (N)
\$N
1            # of NBs
\$NB          NBs
0            PMAP process mapping (0=Row-,1=Column-major)
1            # of process grids (P x Q)
\$P           Ps
\$Q           Qs
16.0         threshold
1            # of panel fact
2            PFACTs (0=left, 1=Crout, 2=Right)
1            # of recursive stopping criterium
4            NBMINs (>= 1)
1            # of panels in recursion
2            NDIVs
1            # of recursive panel fact.
2            RFACTs (0=left, 1=Crout, 2=Right)
1            # of broadcast
1            BCASTs (0=1rg,1=1rM,2=2rg,3=2rM,4=Lng,5=LnM)
1            # of lookahead depth
0            DEPTHs (>=0)
1            SWAP (0=bin-exch,1=long,2=mix)
64           swapping threshold
0            L1 in (0=transposed,1=no-transposed) form
0            U  in (0=transposed,1=no-transposed) form
1            Equilibration (0=no,1=yes)
8            memory alignment in double (> 0)
##### This line (no. 32) is ignored (it serves as a separator). ######
0                      		Number of additional problem sizes for PTRANS
1200 10000 30000        	values of N
0                       	number of additional blocking sizes for PTRANS
40 9 8 13 13 20 16 32 64       	values of NB
\" > HPL.dat
cp HPL.dat hpccinf.txt

PATH=\$PATH:$MPI_PATH/bin
LD_PRELOAD=$MPI_LIBS mpirun -np \$MPI_NUM_THREADS \$HOSTFILE ./hpcc
echo \$? > ~/test-exit-status

cat hpccoutf.txt > \$LOG_FILE" > hpcc
chmod +x hpcc
