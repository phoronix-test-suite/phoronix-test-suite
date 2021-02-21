#!/bin/sh

tar -xf askap-benchmarks-1.0.tar.gz

cd ~/askap-benchmarks-1.0/attic/tConvolveOpenCL/
make
cd ~/askap-benchmarks-1.0/attic/tConvolveCuda
make
cd ~/askap-benchmarks-1.0/current/tConvolveMPI
sed -i 's/CXX=CC/CXX=mpic++/g' Makefile
make
cd ~/askap-benchmarks-1.0/attic/tConvolveOMP
make
cd ~/askap-benchmarks-1.0/attic/tConvolveMT
make
cd ~/askap-benchmarks-1.0/current/tHogbomCleanOMP
cp ../../data/dirty_4096.img dirty.img
cp ../../data/psf_4096.img psf.img
make
echo $? > ~/install-exit-status

cd ~/
echo "#!/bin/sh
cd askap-benchmarks-1.0/

case \"\$1\" in
\"tConvolveOpenCL\")
	cd attic/tConvolveOpenCL
	./tConvolveOpenCL > \$LOG_FILE
	;;
\"tConvolveCuda\")
	cd attic/tConvolveCuda
	./tConvolveCuda > \$LOG_FILE
	;;
\"tConvolveMPI\")
	cd current/tConvolveMPI
	mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES ./tConvolveMPI > \$LOG_FILE
	;;
\"tConvolveOMP\")
	cd attic/tConvolveOMP
	OMP_NUM_THREADS=\$NUM_CPU_CORES ./tConvolveOMP > \$LOG_FILE
	;;
\"tConvolveMT\")
	cd attic/tConvolveMT
	./tConvolveMT \$NUM_CPU_CORES > \$LOG_FILE
	;;
\"tHogbomCleanOMP\")
	cd current/tHogbomCleanOMP
	./tHogbomCleanOMP > \$LOG_FILE
	;;
esac
echo \$? > ~/test-exit-status" > askap
chmod +x askap
