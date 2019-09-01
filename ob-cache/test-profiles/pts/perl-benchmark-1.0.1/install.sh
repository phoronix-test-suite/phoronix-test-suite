#!/bin/bash

unzip perlbench-20160525.zip
echo $? > ~/install-exit-status
echo "#!/bin/sh
cd perlbench-master
rm -rf perlbench-results/*
perl perlbench-runtests \$@ >\$LOG_FILE
cat perlbench-results/*/perls/*/tests/* >> \$LOG_FILE" > perl-benchmark
chmod +x perl-benchmark
