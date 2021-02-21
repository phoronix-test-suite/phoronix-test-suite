#!/bin/sh

pip install --user cython

tar -xf cython-0.29.21.tar.gz
cd cython-0.29.21/Demos/benchmarks
python3 setup.py build_ext --inplace
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd cython-0.29.21/Demos/benchmarks
python3 -c 'import nqueens;print(nqueens.test_n_queens(1000))' > \$LOG_FILE
echo \$? > ~/test-exit-status" > cython-bench 
chmod +x cython-bench
