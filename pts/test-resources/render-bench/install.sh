#!/bin/sh

rm -rf render_bench/
tar -zxvf render_bench.tar.gz
cd render_bench/
make
cd ..

echo "#!/bin/sh
cd render_bench/
\$TIMER_START
./render_bench 2>&1
\$TIMER_STOP" > render-bench-test
chmod +x render-bench-test
