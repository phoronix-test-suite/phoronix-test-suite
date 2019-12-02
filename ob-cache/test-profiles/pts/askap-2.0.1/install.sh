#!/bin/sh

tar -xf askap-benchmarks-20180423.tar.xz

cd ~/askap-benchmarks/tConvolveOpenCL
make
cd ~/askap-benchmarks/tConvolveCuda
make
cd ~/askap-benchmarks/tConvolveMPI
make
cd ~/askap-benchmarks/tConvolveOMP
make
cd ~/askap-benchmarks/tConvolveMT
make
echo $? > ~/install-exit-status

cd ~/
echo "#!/bin/sh
cd askap-benchmarks/

case \"\$1\" in
\"tConvolveOpenCL\")
	cd tConvolveOpenCL
	./tConvolveOpenCL > \$LOG_FILE
	;;
\"tConvolveCuda\")
	cd tConvolveCuda
	./tConvolveCuda > \$LOG_FILE
	;;
\"tConvolveMPI\")
	cd tConvolveMPI
	mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES ./tConvolveMPI > \$LOG_FILE
	;;
\"tConvolveOMP\")
	cd tConvolveOMP
	OMP_NUM_THREADS=\$NUM_CPU_PHYSICAL_CORES ./tConvolveOMP > \$LOG_FILE
	;;
\"tConvolveMT\")
	cd tConvolveMT
	./tConvolveMT \$NUM_CPU_PHYSICAL_CORES > \$LOG_FILE
	;;
esac
echo \$? > ~/test-exit-status" > askap
chmod +x askap
