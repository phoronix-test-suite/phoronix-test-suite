#!/bin/sh
# Build our own BLAS to deal with NUM_THREADS limits with default Ubuntu packages on high core count systems....
mkdir ~/blas-install
unzip -o OpenBLAS-437c0bf2b4697339d96c7bd0bb0bcdac09eccba1.zip
cd OpenBLAS-437c0bf2b4697339d96c7bd0bb0bcdac09eccba1
make NUM_THREADS=512 USE_OPENMP=1
make PREFIX=$HOME/blas-install/ install
cd ~
pip3 install --user swig numpy faiss-cpu==1.7.4
tar -xf faiss-1.7.4.tar.gz
cd faiss-1.7.4
chmod +x benchs/bench_polysemous_sift1m.py
PATH=$HOME/.local/bin:$PATH LD_LIBRARY_PATH=$HOME/blas-install/lib/:$LD_LIBRARY_PATH PATH=$HOME/blas-install/bin/:$PATH cmake -B build -DFAISS_ENABLE_GPU=OFF -DBUILD_TESTING=OFF -DCMAKE_BUILD_TYPE=Release -DFAISS_OPT_LEVEL=avx2 -DFAISS_ENABLE_PYTHON=ON -DBLAS_LIBDIR=$HOME/blas-install/lib/ -DBLA_VENDOR=OpenBLAS -DBLAS_INCDIR=$HOME/blas-install/include/ .
cd build
make -j faiss
make demo_sift1M
echo $? > ~/install-exit-status
tar -xf ../../sift.tar.gz
mv sift sift1M
cd ~
echo "#!/bin/bash
cd faiss-1.7.4/build
./\$1 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > faiss
chmod +x faiss
