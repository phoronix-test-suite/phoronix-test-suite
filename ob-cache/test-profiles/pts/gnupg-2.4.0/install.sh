#!/bin/sh

mkdir $HOME/gnupg_

tar -jxvf gnupg-1.4.22.tar.bz2
cd gnupg-1.4.22/
./configure --prefix=$HOME/gnupg_
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
make install
cd ..
rm -rf gnupg-1.4.22/
rm -rf gnupg_/share/

echo pts-1234567890 > passphrase

echo "#!/bin/sh
./gnupg_/bin/gpg -c --no-options --passphrase-file passphrase -o /dev/null encryptfile 2>&1" > gnupg
chmod +x gnupg
