#!/bin/bash
rm -rf cp2k-2023.1
tar -xjf cp2k-2023.1.tar.bz2
cd cp2k-2023.1/tools/toolchain
./install_cp2k_toolchain.sh --with-libxsmm=install --with-openblas=install --with-fftw=install --with-cmake=system
EXITQ=$?
if [ $EXITQ -ne 0 ]; then
	./install_cp2k_toolchain.sh --with-libxsmm=install --with-openblas=system --with-fftw=install --with-cmake=system
fi
cp install/arch/* ~/cp2k-2023.1/arch/
source $HOME/cp2k-2023.1/tools/toolchain/install/setup
cd ~/cp2k-2023.1/
make -j $NUM_CPU_CORES ARCH=local VERSION="psmp"
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/bash
cd cp2k-2023.1
OMP_NUM_THREADS=1 mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES ./exe/local/cp2k.popt \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > cp2k
chmod +x cp2k
