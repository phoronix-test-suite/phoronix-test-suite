#!/bin/sh

tar -zxvf postmark_1.51.orig.tar.gz
cd postmark-1.51/
cc -O3 postmark-1.51.c -o postmark
echo $? > ~/install-exit-status
cd ~/

echo "#!/bin/sh
cd postmark-1.51/

echo \"set transactions \$1
set size \$2 \$3
set number \$4
run
quit\" > benchmark.pmrc
./postmark benchmark.pmrc > \$LOG_FILE 2>&1" > postmark
chmod +x postmark
