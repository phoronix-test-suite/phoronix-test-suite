#!/bin/sh

mkdir $HOME/gnupg_

tar -zxvf gnupg-1.4.10.tar.gz
cd gnupg-1.4.10/
./configure --prefix=$HOME/gnupg_
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
make install
cd ..
rm -rf gnupg-1.4.10/
rm -rf gnupg_/share/

echo pts-1234567890 > passphrase

echo "#!/bin/sh
./gnupg_/bin/gpg -c --no-options --passphrase-file passphrase -o /dev/null encryptfile 2>&1" > gnupg
chmod +x gnupg
