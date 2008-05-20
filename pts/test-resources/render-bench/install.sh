#!/bin/sh

cd $1

rm -rf render_bench/
tar -xvf render_bench.tar.gz
cd render_bench/
make
cd ..

echo "#!/bin/sh
cd render_bench/
time -f \"Total Render Time: %e Seconds\" ./render_bench 2>&1" > render-bench-test
chmod +x render-bench-test
