#!/bin/sh
tar -xf gpaw-23.6.0.tar.gz
cd gpaw-23.6.0
pip3 install --user .
echo $? > ~/install-exit-status
~/.local/bin/gpaw install-data $HOME/gpaw-data --register
cd ~
unzip -o gpaw-benchmarks-5786f2f09f35a01e938323eb0fe32490e0763aef.zip
cat>gpaw<<EOT
#!/bin/sh
cd gpaw-benchmarks-5786f2f09f35a01e938323eb0fe32490e0763aef/\$1/
rm -f output.txt
export OMP_NUM_THREADS=1
mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES python3 input.py > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
cat output.txt >> \$LOG_FILE
EOT
chmod +x gpaw

