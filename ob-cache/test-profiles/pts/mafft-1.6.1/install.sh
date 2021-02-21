#!/bin/sh

rm -rf $HOME/mafft_
mkdir $HOME/mafft_
tar -xvf mafft-7.471-without-extensions-src.tgz
cd mafft-7.471-without-extensions/core/

make clean

sed -i -e "s|PREFIX = /usr/local|PREFIX = $HOME/mafft_|g" Makefile

make -j $NUM_CPU_CORES ENABLE_MULTITHREAD=-Denablemultithread
echo $? > ~/install-exit-status
make install
cd ~/
cp -f mafft-7.471-without-extensions/scripts/mafft mafft_/
rm -rf mafft-7.471-without-extensions/

cp mafft-ex1-lsu-rna.txt mafft_

cat>mafft<<EOT
#!/bin/sh
cd mafft_/
./mafft --thread \$NUM_CPU_CORES --auto mafft-ex1-lsu-rna.txt > \$LOG_FILE
echo \$? > ~/test-exit-status
EOT
chmod +x mafft
