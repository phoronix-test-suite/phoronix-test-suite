#!/bin/sh

unzip -o CloverLeaf_OpenMP-20181012.zip

cd CloverLeaf_OpenMP-master/
COMPILER=GNU make
echo $? > ~/install-exit-status
cd ~/

echo "#!/bin/sh
cd CloverLeaf_OpenMP-master/
rm -f clover.out
cp -f InputDecks/clover_bm.in clover.in
OMP_NUM_THREADS=\$NUM_CPU_CORES ./clover_leaf \$@
cat clover.out > \$LOG_FILE
echo \$? > ~/test-exit-status" > cloverleaf
chmod +x cloverleaf
