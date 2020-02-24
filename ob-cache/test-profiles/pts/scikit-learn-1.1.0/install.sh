#!/bin/sh

tar xvf scikit-learn-0.22.1.tar.gz

echo "#!/bin/sh
cd scikit-learn-0.22.1/benchmarks/
python3 bench_random_projections.py
echo \$? > ~/test-exit-status" > scikit-learn
chmod +x scikit-learn
