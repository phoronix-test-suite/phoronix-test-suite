#!/bin/sh

unzip -o mafft-7.471-win64-signed.zip
cp mafft-ex1-lsu-rna.txt mafft-win/usr/bin

cat>mafft<<EOT
#!/bin/sh
cd mafft-win/usr/bin
./mafft --thread \$NUM_CPU_CORES --auto mafft-ex1-lsu-rna.txt > \$LOG_FILE
EOT
chmod +x mafft
