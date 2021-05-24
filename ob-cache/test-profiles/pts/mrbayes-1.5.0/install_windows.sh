#!/bin/sh

unzip -o MrBayes-3.2.7-WIN.zip

cd MrBayes-3.2.7-WIN/
cat>job.nex<<EOT
begin mrbayes;
   set autoclose=yes nowarn=yes;
   execute examples/primates.nex;
   lset nst=2;
   mcmc nruns=1 ngen=1500000 samplefreq=10000;
   sump burnin=250;
   sumt burnin=250;
end;
EOT

cd ~
cat>mrbayes<<EOT
#!/bin/sh
cd MrBayes-3.2.7-WIN/
./bin/mb.3.2.7-win64.exe job.nex 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x mrbayes

