#!/bin/sh


unzip -o scikit-learn-20160921.zip

echo "#!/bin/sh
cd scikit-learn-master/benchmarks/
python bench_random_projections.py
echo \$? > ~/test-exit-status" > scikit-learn
chmod +x scikit-learn
