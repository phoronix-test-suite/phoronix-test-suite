#!/bin/sh
unzip -o CloverLeaf_OpenMP-20181012.zip
cd CloverLeaf_OpenMP-master/
COMPILER=GNU make
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
cd CloverLeaf_OpenMP-master/
rm -f clover.out
cp -f InputDecks/\$@.in clover.in
# x86-64 OpenMP runtime in LLVM and Intel compilers have default more efficient tree-based hyper barrier.
# And both runtimes support drop-in replacement for GNU's OpenMP runtime which uses plain barrier.
# AArch64 version uses plain barrier so it is not substituted here but manually can be added to \"grep -E \" expression below for testing.
# Trying to find LLVM OpenMP runtime
OMPFILE=\$(ldconfig -p &>/dev/null && ldconfig -p | grep -E \"x86-64\" | grep libomp.so | awk '{print \$4}')
if [[ -n \$OMPFILE ]]; then 
	export LD_PRELOAD=\$LD_PRELOAD:\$OMPFILE
else 
	# LLVM OpenMP runtime is not available. Trying to find OpenMP runtime from Intel compiler
	OMPFILE=\$(ldconfig -p &>/dev/null && ldconfig -p | grep -E \"x86-64\" | grep libiomp5.so | awk '{print \$4}')
	if [[ -n \$OMPFILE ]]; then 
		export LD_PRELOAD=\$LD_PRELOAD:\$OMPFILE
	fi
fi
# You can play with OMP_PLACES in LLVM OpenMP runtime to get better numbers on CPUs with many cores and several NUMA domains
# export OMP_PLACES=numa_domains
OMP_NUM_THREADS=\$NUM_CPU_CORES GOMP_SPINCOUNT=7000 OMP_WAIT_POLICY=active ./clover_leaf
cat clover.out > \$LOG_FILE
echo \$? > ~/test-exit-status" > cloverleaf
chmod +x cloverleaf
