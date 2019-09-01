#!/bin/sh

unzip -o mafft-7.392-win64-signed.zip
bunzip2 pyruvate_decarboxylase.fasta.bz2 -c > mafft-win/usr/bin

cat>mafft<<EOT
#!/bin/sh
cd mafft-win/usr/bin
./mafft --thread \$NUM_CPU_CORES --localpair --maxiterate 20000 pyruvate_decarboxylase.fasta > \$LOG_FILE
EOT
chmod +x mafft
