#!/bin/sh

rm -rf $HOME/fftw_
rm -rf $HOME/mpich2_
rm -rf $HOME/gromacs40_
mkdir $HOME/fftw_
mkdir $HOME/mpich2_
mkdir $HOME/gromacs40_

tar -xvf fftw-3.2.1.tar.gz
cd fftw-3.2.1/
./configure --prefix=$HOME/fftw_ --enable-float --enable-sse --enable-threads
make -j $NUM_CPU_JOBS
make install
make distclean
./configure --prefix=$HOME/fftw_ --enable-sse2 --enable-threads
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf fftw-3.2.1/

tar -xvf mpich2-1.0.8p1.tar.gz
cd mpich2-1.0.8p1/
./configure --prefix=$HOME/mpich2_ --enable-fast=all --with-pm=gforker --disable-option-checking
make
make install
cd ..
rm -rf mpich2-1.0.8p1/

tar -xvf gromacs-4.0.5.tar.gz
cd gromacs-4.0.5/
./configure --prefix=$HOME/gromacs40_ --enable-mpi --program-suffix="_SSE_MPI" CPPFLAGS=-I$HOME/fftw_/include LDFLAGS=-L$HOME/fftw_/lib PATH=$HOME/mpich2_/bin/:$PATH
make -j $NUM_CPU_JOBS PATH=$HOME/mpich2_/bin/:$PATH
make install
make clean
./configure --prefix=$HOME/gromacs40_ --enable-mpi --disable-float --program-suffix="_SSE2_MPI" CPPFLAGS=-I$HOME/fftw_/include LDFLAGS=-L$HOME/fftw_/lib PATH=$HOME/mpich2_/bin/:$PATH
make -j $NUM_CPU_JOBS PATH=$HOME/mpich2_/bin/:$PATH
make install
make clean
cd ..
rm -rf gromacs-4.0.5/

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

\$HOME/gromacs40_/bin/grompp_\$PRECISION\_MPI -nov 1>/dev/null 2>&1

case \"\$2\" in
\"mpi\")
	\$HOME/mpich2_/bin/mpiexec -np \$NUM_CPU_CORES \$HOME/gromacs40_/bin/mdrun_\$PRECISION\_MPI 1>\$HOME/flopcount 2>&1
	;;
\"single-node\")
	\$HOME/gromacs40_/bin/mdrun_\$PRECISION\_MPI -npme 1 1>\$HOME/flopcount 2>&1
	;;
*)
	exit
	;;
esac

grep -C 1 'Performance:' \$HOME/flopcount" > gromacs
chmod +x gromacs
