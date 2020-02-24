#!/bin/bash

WDIR=$PWD
cd $WDIR

# NUM_CPU_CORES is always exported by the Phoronix Test Suite, but keep this logic here just for external testing purposes
if test -z $NUM_CPU_CORES
then
  if test -n `which nproc`
  then
    NUM_CPU_CORES=`nproc`
  else
    NUM_CPU_CORES=1
  fi
fi

# Download source from github
FOOTNOTE_INFO="Build Time `date`"

# Make Install folder
mkdir $WDIR/Install

# Download and build libecl from git
if [ -d libecl ]; then 
   pushd libecl
   git pull
   popd
else
   git clone --depth 1 https://github.com/Statoil/libecl.git
fi

mkdir libecl/build
pushd libecl/build
cmake .. -DCMAKE_BUILD_TYPE=Release
make -j $NUM_CPU_CORES 
popd

#Download and build opm simulators with MPI support from git
for repo in opm-common opm-grid opm-material ewoms opm-simulators
do

    # Determine if to clone from main URL or different repo, based upon REPO_{$repo} environment variable
    echo "### "
    echo "### $repo"
    echo "### "
    repo_env_var_check=REPO_`echo "$repo" | awk '{print toupper($0)}' | sed 's/-/_/g'`
    if test -z ${!repo_env_var_check}
    then
       repo_url="https://github.com/OPM/$repo.git"
       echo "$repo_env_var_check is not set, using $repo_url"
    else
       echo "$repo_env_var_check is set to ${!repo_env_var_check}"
       repo_url=${!repo_env_var_check}
    fi

    # use the base64 of the Git URL as the basis for the directory to avoid collissions
    repo_dir=`base64 <<< "$repo_url"`
    echo "$repo directory is $repo_dir"
    if [ -d "$repo_dir" ]; then 
        pushd "$repo_dir"
        git pull
        popd
    else
        git clone --depth 1 $repo_url "$repo_dir"
    fi

    # Determine if checking out Git master (default) or some other point based upon REPO_{$repo}_CHECKOUT environment variable
    git_checkout_env_var_check=${repo_env_var_check}_CHECKOUT
    if test -z ${!git_checkout_env_var_check}
    then
    	repo_checkout="master"
        echo "$git_checkout_env_var_check is not set, checking out $repo_checkout"
        pushd "$repo_dir"
        git checkout $repo_checkout
        popd
    elif [[ "${!git_checkout_env_var_check}" =~ ^[0-9]+$ ]]
    then
    	repo_checkout=${!git_checkout_env_var_check}
        echo "$git_checkout_env_var_check is a number, assuming it's a GitHub pull request - ${!git_checkout_env_var_check}"
        pushd "$repo_dir"
	BRANCHNAME=pr-${!git_checkout_env_var_check}-`date +%s`
        git fetch origin pull/${!git_checkout_env_var_check}/head:$BRANCHNAME
	git checkout $BRANCHNAME
        popd
    else
    	repo_checkout=${!git_checkout_env_var_check}
        echo "$git_checkout_env_var_check is set, checking out ${!git_checkout_env_var_check}"
        pushd "$repo_dir"
        git checkout $repo_checkout
        popd
    fi

   # symlinks for the default directory to the BASE64'd directories intended for this round of testing
   unlink $repo
   ln -s "$repo_dir" $repo

    mkdir "$repo_dir"/build
    pushd "$repo_dir"/build
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
  tar -xf ~/omega-opm.tar.gz
  popd
fi

#####################################################
# Run benchmark
#####################################################

echo "<?php 

\$log_file = file_get_contents(getenv('LOG_FILE'));
function get_value_from_line(\$log_file, \$s)
{
	\$s .= ': ';
	\$log_file .= PHP_EOL;
	if((\$x = strpos(\$log_file, \$s)) !== false)
	{
		\$log_file = substr(\$log_file, (\$x + strlen(\$s)));
		\$log_file = substr(\$log_file, 0, strpos(\$log_file, PHP_EOL));

		if(str_replace(array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 0, '%', ' ', '.', '(', ')', 'Failed:', ';'), '', \$log_file) == null)
		{
			\$log_file = str_replace('Failed:' , 'F:', \$log_file);
			return trim(\$log_file);
		}
	}
}
if((\$x = strpos(\$log_file, 'End of simulation')) !== false)
{
	\$log_file = substr(\$log_file, \$x);
	foreach(array('Assembly time (seconds)', 'Linear solve time (seconds)', 'Update time (seconds)', 'Output write time (seconds)', 'Overall Well Iterations', 'Overall Linearizations', 'Overall Newton Iterations', 'Overall Linear Iterations') as \$a)
	{
		\$val = get_value_from_line(\$log_file, \$a);
		if(\$val == null)
		{
			continue;
		}
		if((\$x = strpos(\$a, ' (')) !== false)
		{
			\$a = substr(\$a, 0, \$x);
		}
		echo ucwords(str_replace('Overall ', '', \$a)) . ': ' . \$val . PHP_EOL;
	}

}" > report-additional-data.php

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
if [ \`whoami\` != \"root\" ]
then
  MPIRUN_AS_ROOT_ARG=\"\"
fi

if dmesg | grep -q NUMA; then
    MPI_MAP_BY=\"--map-by numa\"
else
    MPI_MAP_BY=\"--map-by socket\"
fi
MPI_MAP_BY=\"--map-by socket\"

if [ \$1 = \"upscale_relperm_benchmark\" ]
then
	nice mpirun \$MPIRUN_AS_ROOT_ARG -np \$NPROC \$MPI_MAP_BY --report-bindings \$HOSTFILE ./opm-upscaling/build/bin/upscale_relperm_benchmark --tolerance-mb=1e-5 --max-strict-iter=4 > \$LOG_FILE 2>&1
elif [ \$1 = \"flow_mpi_norne\" ]
then
	cd opm-data/norne
	nice mpirun \$MPIRUN_AS_ROOT_ARG -np \$NPROC \$MPI_MAP_BY --report-bindings \$HOSTFILE ../../opm-simulators/build/bin/flow NORNE_ATW2013.DATA --tolerance-mb=1e-5 --max-strict-iter=4 > \$LOG_FILE 2>&1
elif [ \$1 = \"flow_ebos_extra\" ]
then
	cd opm-data/omega-opm
	nice mpirun \$MPIRUN_AS_ROOT_ARG -np \$NPROC \$MPI_MAP_BY --report-bindings \$HOSTFILE ../../opm-simulators/build/bin/flow OMEGA-0.DATA --tolerance-mb=1e-5 --max-strict-iter=4 > \$LOG_FILE 2>&1
elif [ \$1 = \"flow_mpi_extra\" ]
then
	cd opm-data/omega-opm
	nice mpirun \$MPIRUN_AS_ROOT_ARG -np \$NPROC \$MPI_MAP_BY --report-bindings \$HOSTFILE ../../opm-simulators/build/bin/flow OMEGA-0.DATA --tolerance-mb=1e-5 --max-strict-iter=4 > \$LOG_FILE 2>&1
else
	nice mpirun \$MPIRUN_AS_ROOT_ARG -np \$NPROC \$MPI_MAP_BY --report-bindings \$HOSTFILE opm-simulators/build/bin/flow \$1 --tolerance-mb=1e-5 --max-strict-iter=4 > \$LOG_FILE 2>&1
fi

cd ~
\$PHP_BIN report-additional-data.php > ~/pts-footnote 

# echo \$? > ~/test-exit-status" > opm-git
chmod +x opm-git
