#!/bin/sh

mkdir $HOME/gnupg_

tar -xvf gnupg-1.4.9.tar.gz
cd gnupg-1.4.9/
./configure --prefix=$HOME/gnupg_
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
make install
cd ..
rm -rf gnupg-1.4.9/
rm -rf gnupg_/share/

echo pts-1234567890 > passphrase

echo "#!/bin/sh
\$TIMER_START
./gnupg_/bin/gpg -c --no-options --passphrase-file passphrase -o /dev/null encryptfile 2>&1
\$TIMER_STOP" > gnupg
chmod +x gnupg
