#!/bin/sh
tar -xf nekRS-23.0.tar.gz
cd nekRS-23.0/
rm -rf ~/.local/nekrs
echo "\n" | ./nrsconfig
cmake --build ./build --target install -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
MASKED_COMPILER_DIR=`dirname \`which g++\``/
sed -i "s,$MASKED_COMPILER_DIR, ,g" $HOME/.local/nekrs/nekrs.conf
cd ~
cat>nekrs<<EOT
#!/bin/sh
export NEKRS_HOME=\$HOME/.local/nekrs
export PATH=\$NEKRS_HOME/bin:\$PATH
cd ~/.local/nekrs/examples/\$1
mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES nekrs --cimode=1 --setup \$2 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x nekrs

