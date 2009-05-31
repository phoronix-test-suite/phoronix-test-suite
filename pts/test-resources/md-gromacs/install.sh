#!/bin/sh

rm -rf $HOME/fftw_
rm -rf $HOME/mpich2_
rm -rf $HOME/gromacs333_
mkdir $HOME/fftw_
mkdir $HOME/mpich2_
mkdir $HOME/gromacs333_

tar -xvf fftw-3.1.2.tar.gz
cd fftw-3.1.2/
./configure --prefix=$HOME/fftw_ --enable-float --enable-sse --enable-threads
make -j $NUM_CPU_JOBS
make install
make distclean
./configure --prefix=$HOME/fftw_ --enable-sse2 --enable-threads --enable-type-prefix
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf fftw-3.1.2/

tar -xvf mpich2-1.0.7.tar.gz
cd mpich2-1.0.7/
./configure --prefix=$HOME/mpich2_ --enable-fast=all --with-pm=gforker
make
make install
cd ..
rm -rf mpich2-1.0.7/

tar -xvf gromacs-3.3.3.tar.gz
cd gromacs-3.3.3/
./configure --prefix=$HOME/gromacs333_ --enable-mpi --program-suffix="_SSE_MPI" CPPFLAGS=-I$HOME/fftw_/include LDFLAGS=-L$HOME/fftw_/lib PATH=$HOME/mpich2_/bin/:$PATH
make -j $NUM_CPU_JOBS PATH=$HOME/mpich2_/bin/:$PATH
make install
make clean
./configure --prefix=$HOME/gromacs333_ CPPFLAGS=-I$HOME/fftw_/include LDFLAGS=-L$HOME/fftw_/lib --program-suffix="_SSE"
make -j $NUM_CPU_JOBS
make install
make clean
./configure --prefix=$HOME/gromacs333_ --enable-mpi --disable-float --program-suffix="_SSE2_MPI" CPPFLAGS=-I$HOME/fftw_/include LDFLAGS=-L$HOME/fftw_/lib PATH=$HOME/mpich2_/bin/:$PATH
make -j $NUM_CPU_JOBS PATH=$HOME/mpich2_/bin/:$PATH
make install
make clean
./configure --prefix=$HOME/gromacs333_ CPPFLAGS=-I$HOME/fftw_/include LDFLAGS=-L$HOME/fftw_/lib --disable-float --program-suffix="_SSE2"
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf gromacs-3.3.3/

echo "#!/bin/sh
if [ -d \$HOME/gmxbench ]
  then
    rm -rf \$HOME/gmxbench/
fi
if [ -f \$HOME/flopcount ]
  then
    rm -f \$HOME/flopcount
fi
mkdir \$HOME/gmxbench
tar -xvf gmxbench-3.0.tar.gz -C \$HOME/gmxbench/ 1>/dev/null 2>&1

case \"\$3\" in
\"single\")
    PRECISION=\"SSE\"
    ;;
\"double\")
    PRECISION=\"SSE2\"
    ;;
*)
    exit
    ;;
esac

case \"\$1\" in
\"villin\")
	cd \$HOME/gmxbench/d.villin/
	;;
\"dppc\")
	cd \$HOME/gmxbench/d.dppc/
	;;
\"lzm\")
	cd \$HOME/gmxbench/d.lzm/
	mv cutoff.mdp grompp.mdp
	;;
\"poly-ch2\")
	cd \$HOME/gmxbench/d.poly-ch2/
	;;
*)
	exit
	;;
esac

case \"\$2\" in
\"mpi\")
        \$HOME/gromacs333_/bin/grompp_\$PRECISION\_MPI -np \$NUM_CPU_CORES -nov 1>/dev/null 2>&1
        \$HOME/mpich2_/bin/mpiexec -np \$NUM_CPU_CORES \$HOME/gromacs333_/bin/mdrun_\$PRECISION\_MPI 1>\$HOME/flopcount 2>&1
	;;
\"single-node\")
        \$HOME/gromacs333_/bin/grompp_\$PRECISION -nov 1>/dev/null 2>&1
        \$HOME/gromacs333_/bin/mdrun_\$PRECISION 1>\$HOME/flopcount 2>&1
	;;
*)
	exit
	;;
esac

grep -C 1 'Performance:' \$HOME/flopcount

cd \$HOME/" > gromacs
chmod +x gromacs
