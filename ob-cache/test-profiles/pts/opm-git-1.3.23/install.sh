#!/bin/bash

# Install prerequisites and remove conflicting packages
# sudo apt-get install build-essential cmake git wget libblas-dev liblapack-dev libsuitesparse-dev libtrilinos-zoltan-dev mpi-default-dev mpi-default-bin libbz2-1.0 libbz2-dev libbz2-ocaml libbz2-ocaml-dev libdune-common-dev libdune-grid-dev libdune-geometry-dev libdune-istl-dev libsuperlu-dev libsuperlu5 libboost1.62-all-dev

# Clean up in case of any Git issues
WDIR=$PWD
cd $WDIR

if test -z $NUM_CPU_CORES
then
  if test -n `which nproc`
  then
    NUM_CPU_CORES=`nproc`
  else
    NUM_CPU_CORES=1
  fi
fi

# Remove all repositories from previous download
for repo in opm-data opm-common opm-material opm-grid opm-simulators libecl ewoms dune-common dune-geometry dune-grid dune-istl
do
  rm -rf $repo
done


# Download source from github
FOOTNOTE_INFO="Build Time `date`; "

# Build all modules

# Make Install folder
mkdir $WDIR/Install

#Download and build libecl from git
git clone --depth 1 https://github.com/Statoil/libecl.git
mkdir libecl/build ; 
pushd libecl/build
cmake .. -DCMAKE_BUILD_TYPE=Release
make -j $NUM_CPU_CORES 
popd

#Download and build opm simulators with MPI support from git
for repo in opm-common opm-grid opm-material ewoms opm-simulators
do
    git clone --depth 1 https://github.com/OPM/$repo.git
    mkdir $repo/build
    pushd $repo/build
    cmake -DCMAKE_BUILD_TYPE=Release \
          -DUSE_OPENMP=ON \
          -DCMAKE_PREFIX_PATH=$WDIR/Install \
          -DUSE_MPI=ON -Decl_DIR=$WDIR/libecl/build -DBUILD_TESTING=OFF ..
    make -j $NUM_CPU_CORES 
    ecode=$?
    echo $? > ~/install-exit-status
    test $? -eq 0 || exit 1
    popd
done

echo $FOOTNOTE_INFO > ~/install-footnote

# SETUP OMEGA IF PRESENT
if test -f $HOME/omega-opm.tar.gz
then
  git clone --depth 1 https://github.com/OPM/opm-data.git
  pushd opm-data
  tar -xvf ~/omega-opm.tar.gz
  popd
fi

######################################################
# Run benchmark
######################################################

echo "#!/bin/sh

NPROC=\$2

if [ ! \"X\$HOSTFILE\" = \"X\" ] && [ -f \$HOSTFILE ]
then
	HOSTFILE=\"--hostfile \$HOSTFILE\"
elif [ -f /etc/hostfile ]
then
	HOSTFILE=\"--hostfile /etc/hostfile\"
else
	HOSTFILE=\"\"
fi

MPIRUN_AS_ROOT_ARG=\"--allow-run-as-root\"
if [ `whoami` != \"root\" ]
then
  MPIRUN_AS_ROOT_ARG=\"\"
fi

if [ \$1 = \"upscale_relperm_benchmark\" ]
then
	nice mpirun \$MPIRUN_AS_ROOT_ARG -np \$NPROC --map-by numa --report-bindings \$HOSTFILE ./opm-upscaling/build/bin/upscale_relperm_benchmark --tolerance-mb=1e-5 --max-strict-iter=4 > \$LOG_FILE 2>&1
elif [ \$1 = \"flow_mpi_norne\" ]
then
	cd opm-data/norne
	nice mpirun \$MPIRUN_AS_ROOT_ARG -np \$NPROC --map-by numa --report-bindings \$HOSTFILE ../../opm-simulators/build/bin/flow NORNE_ATW2013.DATA --tolerance-mb=1e-5 --max-strict-iter=4 > \$LOG_FILE 2>&1
elif [ \$1 = \"flow_ebos_extra\" ]
then
	cd opm-data/omega-opm
	nice mpirun \$MPIRUN_AS_ROOT_ARG -np \$NPROC --map-by numa --report-bindings \$HOSTFILE ../../opm-simulators/build/bin/flow OMEGA-0.DATA --tolerance-mb=1e-5 --max-strict-iter=4 > \$LOG_FILE 2>&1
elif [ \$1 = \"flow_mpi_extra\" ]
then
	cd opm-data/omega-opm
	nice mpirun \$MPIRUN_AS_ROOT_ARG -np \$NPROC --map-by numa --report-bindings \$HOSTFILE ../../opm-simulators/build/bin/flow OMEGA-0.DATA --tolerance-mb=1e-5 --max-strict-iter=4 > \$LOG_FILE 2>&1
fi

# echo \$? > ~/test-exit-status" > opm-git
chmod +x opm-git
