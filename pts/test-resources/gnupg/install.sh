#!/bin/sh

cd $1

THIS_DIR=$(pwd)
mkdir $THIS_DIR/gnupg_

tar -xvf gnupg-1.4.9.tar.gz
cd gnupg-1.4.9/
./configure --prefix=$THIS_DIR/gnupg_
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf gnupg-1.4.9/

echo trondheim-pts-1234567890 > passphrase

echo "#!/bin/sh
time -f \"Encryption Time: %e Seconds\" ./gnupg_/bin/gpg -c --passphrase-file passphrase -o /dev/null 1gbfile 2>&1" > gnupg
chmod +x gnupg
