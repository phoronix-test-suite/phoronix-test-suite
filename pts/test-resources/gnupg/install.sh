#!/bin/sh

THIS_DIR=$(pwd)
mkdir $THIS_DIR/gnupg_

tar -xvf gnupg-1.4.9.tar.gz
cd gnupg-1.4.9/
./configure --prefix=$THIS_DIR/gnupg_
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf gnupg-1.4.9/

echo pts-1234567890 > passphrase

echo "#!/bin/sh
\$TIMER_START
./gnupg_/bin/gpg -c --no-options --passphrase-file passphrase -o /dev/null 2gbfile 2>&1
\$TIMER_STOP" > gnupg
chmod +x gnupg
