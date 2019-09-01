#!/bin/sh

tar -xvf cython-0.27.tar.gz
cd cython-0.27/Demos/benchmarks
python setup.py build_ext --inplace
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd cython-0.27/Demos/benchmarks
python -c 'import nqueens;print(nqueens.test_n_queens(1000))' > \$LOG_FILE
echo \$? > ~/test-exit-status" > cython-bench 
chmod +x cython-bench
