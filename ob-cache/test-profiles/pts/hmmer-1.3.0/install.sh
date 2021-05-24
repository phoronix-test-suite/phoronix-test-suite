#!/bin/sh

tar -zxvf hmmer-3.3.2.tar.gz
cd hmmer-3.3.2/
./configure --enable-mpi
if [ $OS_TYPE = "BSD" ]
then
	gmake -j $NUM_CPU_CORES
else
	make -j $NUM_CPU_CORES
fi
echo $? > ~/install-exit-status
cd ~
gunzip Pfam_ls.gz -c > hmmer-3.3.2/tutorial/Pfam_ls

cat>hmmer<<EOT
#!/bin/sh
cd hmmer-3.3.2/tutorial
 mpirun --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES ../src/hmmsearch Pfam_ls 7LESS_DROME
echo \$? > ~/test-exit-status
EOT
chmod +x hmmer
