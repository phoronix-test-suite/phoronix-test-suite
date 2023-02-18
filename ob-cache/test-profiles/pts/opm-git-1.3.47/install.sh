#!/bin/bash
FOOTNOTE_INFO="Build Time `date`"
#Download and build opm simulators with MPI support from git
for repo in opm-common opm-grid opm-material opm-models opm-simulators
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
        git clone $repo_url "$repo_dir"
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
	git pull --no-edit origin pull/${!git_checkout_env_var_check}/head
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
   
   cd "$repo_dir"
   FOOTNOTE_INFO="$FOOTNOTE_INFO $repo = `git rev-parse --short HEAD`"
   cd ~

    mkdir "$repo_dir"/build
    pushd "$repo_dir"/build
    make clean
    cmake -DCMAKE_BUILD_TYPE=Release -DUSE_MPI=ON -DBUILD_TESTING=OFF ..
    make -j $NUM_CPU_PHYSICAL_CORES 
    ecode=$?
    echo $? > ~/install-exit-status
    test $? -eq 0 || exit 1
    popd
done

echo $FOOTNOTE_INFO > ~/install-footnote

git clone https://github.com/OPM/opm-tests
cd opm-tests
git pull
cd ~

# SETUP OMEGA IF PRESENT
if test -f $HOME/omega-opm-2.tar.gz
then
  pushd opm-tests
  tar -xf ~/omega-opm-2.tar.gz
  popd
fi

cd opm-tests
tar -xf ~/Norne-4C.tar.gz
cd ~
tar -xf opm-benchmark-extras-1.tar.xz

tar -xf Smeaheia_Simulation_Models.tar.xz
unzip -o punqs3.zip
cd punqs3
echo "22a23,25
> AQUDIMS
>    4*  2 200
> /
145a149
> /
448c452
< 'PRO*'  'SHUT'  6* 120.0 /
---
> 'PRO*'  'SHUT' 'BHP' 5* 120.0 /" > punqs3.patch
patch PUNQS3.DATA punqs3.patch
cd ~

#####################################################
# Run benchmark
#####################################################

echo "#!/bin/bash

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

# Check if there are Numa nodes
if lscpu | grep node1 ; then
    MPI_MAP_BY=\"--map-by numa\"
else
    MPI_MAP_BY=\"--map-by socket\"
fi

if [ \$1 = \"upscale_relperm_benchmark\" ]
then
	nice mpirun \$MPIRUN_AS_ROOT_ARG -np \$NPROC \$MPI_MAP_BY --report-bindings \$HOSTFILE ./opm-upscaling/build/bin/upscale_relperm_benchmark > \$LOG_FILE 2>&1
	echo \$? > ~/install-exit-status
elif [ \$1 = \"flow_mpi_norne\" ]
then
	cd opm-tests/norne
	rm -f NORNE_ATW2013.SMSPEC
	nice mpirun \$MPIRUN_AS_ROOT_ARG -np \$NPROC \$MPI_MAP_BY --report-bindings \$HOSTFILE ../../opm-simulators/build/bin/flow NORNE_ATW2013.DATA > \$LOG_FILE 2>&1
	echo \$? > ~/install-exit-status
	~/summary-parse.sh NORNE_ATW2013.SMSPEC >> \$LOG_FILE
elif [ \$1 = \"flow_mpi_norne_4c_msw\" ]
then
	cd opm-tests/Norne-4C
	rm -f NORNE_ATW2013_4C_MSW.SMSPEC
	nice mpirun \$MPIRUN_AS_ROOT_ARG -np \$NPROC \$MPI_MAP_BY --report-bindings \$HOSTFILE ../../opm-simulators/build/bin/flow NORNE_ATW2013_4C_MSW.DATA > \$LOG_FILE 2>&1
	echo \$? > ~/install-exit-status
	~/summary-parse.sh NORNE_ATW2013_4C_MSW.SMSPEC >> \$LOG_FILE
elif [ \$1 = \"flow_ebos_extra\" ]
then
	cd opm-tests/omega-opm
	rm -f OMEGA-0.SMSPEC
	nice mpirun \$MPIRUN_AS_ROOT_ARG -np \$NPROC \$MPI_MAP_BY --report-bindings \$HOSTFILE ../../opm-simulators/build/bin/flow OMEGA-0.DATA > \$LOG_FILE 2>&1
	echo \$? > ~/install-exit-status
	~/summary-parse.sh OMEGA-0.SMSPEC >> \$LOG_FILE
elif [ \$1 = \"flow_mpi_extra\" ]
then
	cd opm-tests/omega-opm
	rm -f OMEGA-0.SMSPEC
	nice mpirun \$MPIRUN_AS_ROOT_ARG -np \$NPROC \$MPI_MAP_BY --report-bindings \$HOSTFILE ../../opm-simulators/build/bin/flow OMEGA-0.DATA > \$LOG_FILE 2>&1
	echo \$? > ~/install-exit-status
	~/summary-parse.sh OMEGA-0.SMSPEC >> \$LOG_FILE
elif [ \$1 = \"drogon\" ]
then
	cd opm-tests/drogon/model
	rm -f DROGON_PRED.SMSPEC
	nice mpirun \$MPIRUN_AS_ROOT_ARG -np \$NPROC \$MPI_MAP_BY --report-bindings \$HOSTFILE ../../../opm-simulators/build/bin/flow DROGON_HIST.DATA
	nice mpirun \$MPIRUN_AS_ROOT_ARG -np \$NPROC \$MPI_MAP_BY --report-bindings \$HOSTFILE ../../../opm-simulators/build/bin/flow DROGON_PRED.DATA >> \$LOG_FILE 2>&1
	echo \$? > ~/install-exit-status
	~/summary-parse.sh DROGON_PRED.SMSPEC >> \$LOG_FILE
elif [ \$1 = \"spe10_model_1\" ]
then
	cd opm-tests/spe10
	rm -f SPE10-MOD01-02.SMSPEC
	nice mpirun \$MPIRUN_AS_ROOT_ARG -np \$NPROC \$MPI_MAP_BY --report-bindings \$HOSTFILE ../../opm-simulators/build/bin/flow SPE10-MOD01-02.DATA >> \$LOG_FILE 2>&1
	echo \$? > ~/install-exit-status
	~/summary-parse.sh SPE10-MOD01-02.SMSPEC >> \$LOG_FILE
elif [ \$1 = \"spe10_model_2\" ]
then
	cd opm-tests/spe10
	rm -f SPE10-MOD02-02.SMSPEC
	nice mpirun \$MPIRUN_AS_ROOT_ARG -np \$NPROC \$MPI_MAP_BY --report-bindings \$HOSTFILE ../../opm-simulators/build/bin/flow --linear-solver=\"cpr\" SPE10-MOD02-02.DATA >> \$LOG_FILE 2>&1
	echo \$? > ~/install-exit-status
	~/summary-parse.sh SPE10-MOD02-02.SMSPEC >> \$LOG_FILE
elif [ \$1 = \"smeaheia\" ]
then
	cd Simulation_Models/data
	rm -f GASSNOVA_SIMULATION_MODEL_FF_SMEAHEIA_21.SMSPEC
	nice mpirun \$MPIRUN_AS_ROOT_ARG -np \$NPROC \$MPI_MAP_BY --report-bindings \$HOSTFILE ../../opm-simulators/build/bin/flow Gassnova_simulation_model_FF_SMEAHEIA_21.DATA >> \$LOG_FILE 2>&1
	echo \$? > ~/install-exit-status
	~/summary-parse.sh GASSNOVA_SIMULATION_MODEL_FF_SMEAHEIA_21.SMSPEC >> \$LOG_FILE
elif [ \$1 = \"punqs3\" ]
then
	cd punqs3
	rm -f PUNQS3.SMSPEC
	nice mpirun \$MPIRUN_AS_ROOT_ARG -np \$NPROC \$MPI_MAP_BY --report-bindings \$HOSTFILE ../opm-simulators/build/bin/flow PUNQS3.DATA >> \$LOG_FILE 2>&1
	echo \$? > ~/install-exit-status
	~/summary-parse.sh PUNQS3.SMSPEC >> \$LOG_FILE
else
	nice mpirun \$MPIRUN_AS_ROOT_ARG -np \$NPROC \$MPI_MAP_BY --report-bindings \$HOSTFILE opm-simulators/build/bin/flow \$1 > \$LOG_FILE 2>&1
	echo \$? > ~/install-exit-status
fi

cd ~
TEST_EXIT_STATUS=\`cat ~/install-exit-status\`
if [ \$TEST_EXIT_STATUS -eq 0 ]
then
	\$PHP_BIN report-additional-data.php > ~/pts-footnote 
fi" > opm-git
chmod +x opm-git
