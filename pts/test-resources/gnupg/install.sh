#!/bin/sh

mkdir $HOME/gnupg_

tar -xvf gnupg-2.0.11.tar.bz2
cd gnupg-2.0.11/
./configure --prefix=$HOME/gnupg_
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
make install
cd ..
rm -rf gnupg-2.0.11/
rm -rf gnupg_/share/

echo pts-1234567890 > passphrase

echo "#!/bin/sh

\$TIMER_START
./gnupg_/bin/gpg-agent --homedir \$HOME/.gnupg --batch --daemon ./gnupg_/bin/gpg2 -c --no-options --batch --passphrase pts-1234567890 -o /dev/null 2gbfile 2>&1
\$TIMER_STOP" > gnupg
chmod +x gnupg
