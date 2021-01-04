#!/bin/sh

tar -zxvf hmmer-3.3.1.tar.gz
cd hmmer-3.3.1/
./configure --enable-threads
if [ $OS_TYPE = "BSD" ]
then
	gmake -j $NUM_CPU_CORES
else
	make -j $NUM_CPU_CORES
fi
echo $? > ~/install-exit-status
cd ~
gunzip Pfam_ls.gz -c > hmmer-3.3.1/tutorial/Pfam_ls

cat>hmmer<<EOT
#!/bin/sh
cd hmmer-3.3.1/tutorial
../src/hmmsearch --cpu \$NUM_CPU_CORES Pfam_ls 7LESS_DROME
echo \$? > ~/test-exit-status
EOT
chmod +x hmmer
