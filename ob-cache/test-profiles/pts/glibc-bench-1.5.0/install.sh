#!/bin/sh

rm -rf glibc-benchmarks
tar -xvf glibc-benchmarks-1.tar.gz

echo "#!/bin/sh
cd glibc-benchmarks/
./\$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > glibc-bench
chmod +x glibc-bench
