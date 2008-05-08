#!/bin/sh

cd $1

THIS_DIR=$(pwd)
mkdir $THIS_DIR/fftw_
mkdir $THIS_DIR/mpich2_
mkdir $THIS_DIR/gromacs333_

tar -xvf fftw-3.1.2.tar.gz
cd fftw-3.1.2/
./configure --prefix=$THIS_DIR/fftw_ --enable-float --enable-sse --enable-threads
make -j $NUM_CPU_JOBS
make install
make distclean
./configure --prefix=$THIS_DIR/fftw_ --enable-sse2 --enable-threads --enable-type-prefix
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf fftw-3.1.2/

tar -xvf mpich2-1.0.7.tar.gz
cd mpich2-1.0.7/
./configure --prefix=$THIS_DIR/mpich2_ --enable-fast=all --with-pm=gforker
make
make install
cd ..
rm -rf mpich2-1.0.7/

tar -xvf gromacs-3.3.3.tar.gz
cd gromacs-3.3.3/
./configure --prefix=$THIS_DIR/gromacs333_ --enable-mpi CPPFLAGS=-I$THIS_DIR/fftw_/include LDFLAGS=-L$THIS_DIR/fftw_/lib PATH=$THIS_DIR/mpich2_/bin/:$PATH
make -j $NUM_CPU_JOBS PATH=$THIS_DIR/mpich2_/bin/:$PATH
make install
make clean
./configure --prefix=$THIS_DIR/gromacs333_ CPPFLAGS=-I$THIS_DIR/fftw_/include LDFLAGS=-L$THIS_DIR/fftw_/lib --program-suffix="_single"
make mdrun
make install-mdrun
cd ..
rm -rf gromacs-3.3.3/

echo "#!/bin/sh
if [ -d $THIS_DIR/gmxbench ]
  then
    rm -rf $THIS_DIR/gmxbench/
fi
if [ -f $THIS_DIR/flopcount ]
  then
    rm -f $THIS_DIR/flopcount
fi
mkdir $THIS_DIR/gmxbench
tar -xvf gmxbench-3.0.tar.gz -C $THIS_DIR/gmxbench/ &>/dev/null

case "\$1" in
\"villin\")
	cd $THIS_DIR/gmxbench/d.villin/
	;;
\"dppc\")
	cd $THIS_DIR/gmxbench/d.dppc/
	;;
\"lzm\")
	cd $THIS_DIR/gmxbench/d.lzm/
	mv cutoff.mdp grompp.mdp
	;;
\"poly-ch2\")
	cd $THIS_DIR/gmxbench/d.poly-ch2/
	;;
*)
	exit
	;;
esac
#cat grompp.mdp | sed 's/nsteps                   = 5000/nsteps                   = 5000/' > grompp.mdp.new
#rm -f grompp.mdp
#mv grompp.mdp.new grompp.mdp

case "\$2" in
\"mpi\")
	$THIS_DIR/gromacs333_/bin/grompp -np \$NUM_CPU_CORES -nov &>/dev/null
	$THIS_DIR/mpich2_/bin/mpiexec -np \$NUM_CPU_CORES $THIS_DIR/gromacs333_/bin/mdrun &> $THIS_DIR/flopcount
	;;
\"single-node\")
	$THIS_DIR/gromacs333_/bin/grompp -nov &>/dev/null
	$THIS_DIR/gromacs333_/bin/mdrun_single &> $THIS_DIR/flopcount
	;;
*)
	exit
	;;
esac

grep -C 1 'Performance:' $THIS_DIR/flopcount

cd $THIS_DIR/" > gromacs
chmod +x gromacs
