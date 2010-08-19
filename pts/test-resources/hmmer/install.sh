#!/bin/sh

mkdir -p $HOME/hmmer_

tar -zxvf hmmer-2.3.2.tar.gz
cd hmmer-2.3.2/
./configure --enable-threads --prefix=$HOME/hmmer_
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
make install
cd ..
cp -r hmmer-2.3.2/tutorial hmmer_
rm -rf hmmer-2.3.2/
gunzip Pfam_ls.gz -c > hmmer_/tutorial/Pfam_ls

cat>hmmpfam<<EOT
#!/bin/sh
cd hmmer_/tutorial
../bin/hmmpfam -E 0.1 Pfam_ls 7LES_DROME > /dev/null
cd ../..
EOT
chmod +x hmmpfam

cat>hmmer<<EOT
#!/bin/sh
./hmmpfam 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x hmmer

