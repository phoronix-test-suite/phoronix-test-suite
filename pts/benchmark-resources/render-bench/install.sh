#!/bin/sh

cd $1

if [ ! -f render_bench.tar.gz ]
  then
     wget http://www.phoronix-test-suite.com/benchmark-files/render_bench.tar.gz -O render_bench.tar.gz
fi

rm -rf render_bench/
tar -xvf render_bench.tar.gz
cd render_bench/
make
cd ..

echo "#!/bin/sh
cd render_bench/
/usr/bin/time -f \"Total Render Time: %e Seconds\" ./render_bench 2>&1" > render-bench-test
chmod +x render-bench-test
