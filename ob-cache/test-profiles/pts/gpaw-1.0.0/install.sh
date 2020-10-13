#!/bin/sh

tar -xf gpaw-20.1.0.tar.gz
cd gpaw-20.1.0
pip3 install --user .
echo $? > ~/install-exit-status
~/.local/bin/gpaw install-data $HOME/gpaw-data --register

cd ~
tar -xf gpaw-benchmarks-20180130.tar.xz

cat>gpaw<<EOT
#!/bin/sh
cd gpaw-benchmarks/\$1/
rm -f output.txt
export OMP_NUM_THREADS=1
mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES python3 input.py > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
cat output.txt >> \$LOG_FILE
EOT
chmod +x gpaw

