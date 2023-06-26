#!/bin/bash
# Hack as scipy otherwise seems to have issues...
TEST_HOME=$HOME
export HOME=$DEBUG_REAL_HOME
#pip3 install --user numpy
# Build our own BLAS to deal with NUM_THREADS limits with default Ubuntu packages on high core count systems....
mkdir $TEST_HOME/blas-install
unzip -o OpenBLAS-437c0bf2b4697339d96c7bd0bb0bcdac09eccba1.zip
cd OpenBLAS-437c0bf2b4697339d96c7bd0bb0bcdac09eccba1
make NUM_THREADS=512 USE_OPENMP=1 USE_THREADS=1 DYNAMIC_ARCH=0 NO_AFFINITY=1 NO_WARMUP=1 BUILD_RELAPACK=0 COMMON_OPT="-O3" CFLAGS="-O3" FCOMMON_OPT="-O3 -fopenmp" FCFLAGS="-O3 -fopenmp"
make PREFIX=$HOME/blas-install/ NUM_THREADS=512 USE_OPENMP=1 USE_THREADS=1 DYNAMIC_ARCH=0 NO_AFFINITY=1 NO_WARMUP=1 BUILD_RELAPACK=0 COMMON_OPT="-O3" CFLAGS="-O3" FCOMMON_OPT="-O3 -fopenmp" FCFLAGS="-O3 -fopenmp" install
cd $TEST_HOME

export PATH=$HOME/blas-install/bin:$HOME.local/bin:$HOME/.local/bin:$HOME:$PATH
export LD_LIBRARY_PATH=$HOME/blas-install/lib:$HOME/blas-install/include:$LD_LIBRARY_PATH

tar -xf numpy-1.24.3.tar.gz
cd numpy-1.24.3
rm -f site.cfg
cp site.cfg.example site.cfg
echo "[openblas]
libraries = openblas
library_dirs = $HOME/blas-install/lib
include_dirs = $HOME/blas-install/include
runtime_library_dirs = $HOME/blas-install/lib" >> site.cfg
pip3 install --user . 2>&1
echo $? > $TEST_HOME/install-exit-status
pip3 install -U --user setuptools cython doit pydevtool pybind11 pythran meson mesonpy
pip3 install --use-pep517 -U --user pandas==2.0.0 matplotlib memory_profiler rich_click

cd $TEST_HOME
echo "import numpy as np
try:
	incdir = os.path.relpath(np.get_include())
except Exception:
	incdir = np.get_include()
print(\"NUMPYDIR: \" + incdir)" > test.py
echo "TEST PY"
python3 test.py 2>&1
NUMPY_DIR=`python3 test.py | cut -d':' -f2 | xargs`
rm -rf scipy
tar -xf scipy-20230505.tar.xz
cd scipy
rm -f site.cfg
cp site.cfg.example site.cfg
echo "[openblas]
libraries = openblas
library_dirs = $HOME/blas-install/lib
include_dirs = $HOME/blas-install/include
runtime_library_dirs = $HOME/blas-install/lib" >> site.cfg
sed -i 's/1.1.0/1.0.0/g' meson.build
sed -i "47 i incdir_numpy = '$NUMPYDIR'" scipy/meson.build
pip3 install --user .

cd $TEST_HOME
tar -xf joblib-1.2.0.tar.gz
pip3 install --user joblib-1.2.0/

# INSTALL THREADPOOLCTL
tar -xf threadpoolctl-3.1.0.tar.gz
pip3 install --user threadpoolctl-3.1.0/

tar -xf scikit-learn-1.2.2.tar.gz
pip3 install --user scikit-learn-1.2.2/
tar -xf scikit-learn-benchmark-improvements-1.tar.xz
cd scikit-learn-1.2.2
patch -p0 < ../benchmarks-better.patch
cd benchmarks/
rm -f bench_multilabel_metrics.py
rm -f bench_plot_randomized_svd.py
sed -i 's/plt.show()/ /g' bench_feature_expansions.py 
sed -i 's/plt.show()/ /g' bench_glm.py
sed -i 's/plt.show()/ /g' bench_glmnet.py
sed -i 's/plt.show()/ /g' bench_hist_gradient_boosting.py
sed -i 's/plt.show()/ /g' bench_isolation_forest.py
sed -i 's/plt.show()/ /g' bench_isotonic.py
sed -i 's/plt.show()/ /g' bench_kernel_pca_solvers_time_vs_n_components.py
sed -i 's/plt.show()/ /g' bench_kernel_pca_solvers_time_vs_n_samples.py
sed -i 's/plt.show()/ /g' bench_lasso.py
sed -i 's/plt.show()/ /g' bench_lof.py
sed -i 's/plt.show()/ /g' bench_online_ocsvm.py
sed -i 's/plt.show()/ /g' bench_plot_fastkmeans.py
sed -i 's/plt.show()/ /g' bench_plot_hierarchical.py
sed -i 's/plt.show()/ /g' bench_plot_incremental_pca.py
sed -i 's/plt.show()/ /g' bench_plot_lasso_path.py
sed -i 's/plt.show()/ /g' bench_plot_neighbors.py
sed -i 's/plt.show()/ /g' bench_plot_nmf.py
sed -i 's/plt.show()/ /g' bench_plot_omp_lars.py
sed -i 's/plt.show()/ /g' bench_plot_parallel_pairwise.py
sed -i 's/plt.show()/ /g' bench_plot_polynomial_kernel_approximation.py
sed -i 's/plt.show()/ /g' bench_plot_svd.py
sed -i 's/plt.show()/ /g' bench_plot_ward.py
sed -i 's/plt.show()/ /g' bench_rcv1_logreg_convergence.py
sed -i 's/plt.show()/ /g' bench_sample_without_replacement.py
sed -i 's/plt.show()/ /g' bench_sgd_regression.py
sed -i 's/plt.show()/ /g' bench_tree.py

pip3 install --use-pep517 --user glmnet glm

# Cache the datasets or anything else that may get downloaded...
#for f in "bench_l*.py"
#do
#  python3 $f
#done
cd $TEST_HOME
echo "#!/bin/sh
TEST_HOME=\$HOME
export HOME=\$DEBUG_REAL_HOME
cd scikit-learn-1.2.2/benchmarks/
python3 bench_\$@
echo \$? > \$TEST_HOME/test-exit-status" > scikit-learn
chmod +x scikit-learn
