#!/bin/sh

cd $1

tar -xvf pts-graph-benchmark-1.tar.gz

echo "#!/bin/sh

time -f \"pts_Graph Time: %e Seconds\" php pts-graph-benchmark/pts_graph_benchmark.php 2>&1" > pts-graph
chmod +x pts-graph
