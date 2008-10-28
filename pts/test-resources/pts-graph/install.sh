#!/bin/sh

tar -xvf pts-graph-benchmark-1.tar.gz

echo "#!/bin/sh

\$TIMER_START
\$PHP_BIN pts-graph-benchmark/pts_graph_benchmark.php 2>&1
\$TIMER_STOP" > pts-graph
chmod +x pts-graph
