#!/bin/sh
pip3 install --user scikit-learn==1.1.3 scipy==1.9.3 pandas==1.5.1
tar -xf scikit-learn-1.1.3.tar.gz
cd scikit-learn-1.1.3/benchmarks/
# Cache the datasets or anything else that may get downloaded...
python3 bench_random_projections.py
python3 bench_mnist.py
python3 bench_tsne_mnist.py
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
cd scikit-learn-1.1.3/benchmarks/
python3 bench_\$@
echo \$? > ~/test-exit-status" > scikit-learn
chmod +x scikit-learn
