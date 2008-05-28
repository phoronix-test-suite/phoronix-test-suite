#!/bin/sh

tar -xvf pts-graph-benchmark-1.tar.gz

echo "#!/bin/sh

/usr/bin/time -f \"pts_Graph Time: %e Seconds\" php pts-graph-benchmark/pts_graph_benchmark.php 2>&1" > pts-graph
chmod +x pts-graph
