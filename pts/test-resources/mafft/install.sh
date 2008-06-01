#!/bin/sh

THIS_DIR=$(pwd)

mkdir $THIS_DIR/mafft_

tar -xvf mafft-6.240-src.tgz
cd mafft-6.240/src/

make clean
sed -i -e "s|PREFIX = /usr/local/lib/mafft|PREFIX = $THIS_DIR/mafft_|g" Makefile

make -j $NUM_CPU_JOBS
make install
cd ../..
cp -f mafft-6.240/scripts/mafft mafft_/

bunzip2 pyruvate_decarboxylase.fasta.bz2 -c > mafft_/pyruvate_decarboxylase.fasta

cat>align<<EOT
#!/bin/sh
cd mafft_/
./mafft --localpair --maxiterate 10000 pyruvate_decarboxylase.fasta 1>/dev/null 2>&1
cd ..
EOT
chmod +x align

cat>mafft<<EOT
#!/bin/sh
/usr/bin/time -f "Sequence Alignment Time: %e Seconds" ./align 2>&1 | grep Seconds
EOT
chmod +x mafft