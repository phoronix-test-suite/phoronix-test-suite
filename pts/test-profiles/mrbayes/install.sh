#!/bin/sh

mkdir $HOME/mpich2_

tar -zxvf mpich2-1.0.8p1.tar.gz
cd mpich2-1.0.8p1/
./configure --prefix=$HOME/mpich2_ --enable-fast=all --with-pm=gforker --disable-option-checking
make
make install
cd ..
rm -rf mpich2-1.0.8p1/

tar -zxvf mrbayes-3.1.2.tar.gz
cd mrbayes-3.1.2/
sed -i -e "s/MPI ?= no/MPI ?= yes/g" Makefile

SSE=$(grep sse /proc/cpuinfo)
if [ ! "$SSE" = "" ]
 then
	sed -i -e "s/OPTFLAGS ?= -O3/OPTFLAGS ?= -O3 -msse -mfpmath=sse/g" Makefile
fi
#kludge to remove readline dependency. I don't think it affects the speed, so it can probably stay.
sed -i -e "s/USEREADLINE ?= yes/USEREADLINE ?= no/g" Makefile
make -j $NUM_CPU_JOBS PATH=$HOME/mpich2_/bin/:$PATH

cat>job.nex<<EOT
begin mrbayes;
   set autoclose=yes nowarn=yes;
   execute primates.nex;
   lset nst=6 rates=invgamma;
   mcmc ngen=30000 samplefreq=10;
   sump burnin=250;
   sumt burnin=250;
end;
EOT

cd ..

cat>mb<<EOT
#!/bin/sh
cd mrbayes-3.1.2/
../mpich2_/bin/mpiexec -np \$NUM_CPU_CORES ./mb job.nex 1>/dev/null 2>&1
EOT
chmod +x mb

cat>mrbayes<<EOT
#!/bin/sh
./mb 2>&1
EOT
chmod +x mrbayes

