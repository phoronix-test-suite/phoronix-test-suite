#!/bin/sh

tar -zxvf hpl-2.3.tar.gz
cd hpl-2.3

./configure
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd hpl-2.3/testing

if [ \"X\$MPI_NUM_THREADS\" = \"X\" ]
then
	MPI_NUM_THREADS=\$NUM_CPU_PHYSICAL_CORES
fi


# HPL.dat generation
# http://pic.dhe.ibm.com/infocenter/lnxinfo/v3r0m0/index.jsp?topic=%2Fliaai.hpctune%2Fbaselinehpcc_gccatlas.htm
export OMP_NUM_THREADS=0
PQ=0
P=\$(echo \"scale=0;sqrt(\$MPI_NUM_THREADS)\" |bc -l)
Q=\$P
PQ=\$((\$P*\$Q))

while [ \$PQ -ne \$MPI_NUM_THREADS ]; do
    Q=\$((\$MPI_NUM_THREADS/\$P))
    PQ=\$((\$P*\$Q))
    if [ \$PQ -ne \$MPI_NUM_THREADS ] && [ \$P -gt 1 ]; then P=\$((\$P-1)); fi
done

if [ \"X\$N\" = \"X\" ] || [ \"X\$NB\" = \"X\" ]
then
	# SYS_MEMORY * about .5 of that, go from MB to bytes and divide by 8
	N=\$(echo \"scale=0;sqrt(\${SYS_MEMORY}*0.6*1048576/8)\" |bc -l)
	NB=\$((256 - 256 % \$MPI_NUM_THREADS))
	N=\$((\$N - \$N % \$NB))
fi

echo \"HPLinpack benchmark input file
Innovative Computing Laboratory, University of Tennessee
HPL.out      output file name (if any)
6            device out (6=stdout,7=stderr,file)
1            # of problems sizes (N)
\$N
1            # of NBs
\$NB          NBs
0            PMAP process mapping (0=Row-,1=Column-major)
1            # of process grids (P x Q)
\$P           Ps
\$Q           Qs
16.0         threshold
1            # of panel fact
2            PFACTs (0=left, 1=Crout, 2=Right)
1            # of recursive stopping criterium
4            NBMINs (>= 1)
1            # of panels in recursion
2            NDIVs
1            # of recursive panel fact.
2            RFACTs (0=left, 1=Crout, 2=Right)
1            # of broadcast
1            BCASTs (0=1rg,1=1rM,2=2rg,3=2rM,4=Lng,5=LnM)
1            # of lookahead depth
0            DEPTHs (>=0)
1            SWAP (0=bin-exch,1=long,2=mix)
64           swapping threshold
0            L1 in (0=transposed,1=no-transposed) form
0            U  in (0=transposed,1=no-transposed) form
1            Equilibration (0=no,1=yes)
8            memory alignment in double (> 0)
##### This line (no. 32) is ignored (it serves as a separator). ######
0                      		Number of additional problem sizes for PTRANS
1200 10000 30000        	values of N
0                       	number of additional blocking sizes for PTRANS
40 9 8 13 13 20 16 32 64       	values of NB
\" > HPL.dat

mpirun --allow-run-as-root -np \$MPI_NUM_THREADS ./xhpl > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > hpl
chmod +x hpl
