#!/bin/sh

tar -zxvf MrBayes-3.2.7a.tar.gz
cd MrBayes-3.2.7a
./configure
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cat>job.nex<<EOT
begin mrbayes;
   set autoclose=yes nowarn=yes;
   execute examples/primates.nex;
   lset nst=2;
   mcmc nruns=1 ngen=1000000 samplefreq=10000;
   sump burnin=250;
   sumt burnin=250;
end;
EOT

cd ~
cat>mrbayes<<EOT
#!/bin/sh
cd MrBayes-3.2.7a/
mpiexec --allow-run-as-root -np \$NUM_CPU_PHYSICAL_CORES ./src/mb job.nex 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x mrbayes

