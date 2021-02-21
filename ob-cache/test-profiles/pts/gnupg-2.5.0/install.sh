#!/bin/sh

mkdir $HOME/gnupg_

tar -jxvf gnupg-2.2.27.tar.bz2
cd gnupg-2.2.27/
./configure --prefix=$HOME/gnupg_
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
make install
cd ~
rm -rf gnupg-1.4.22/
rm -rf gnupg_/share/
mkdir .gnupg/
echo "#!/bin/sh
echo 1234567890 | ./gnupg_/bin/gpg -c --no-options --batch --yes --passphrase-fd 0 -o /dev/null ubuntu-20.04-desktop-amd64.iso 2>&1
echo \$? > ~/test-exit-status" > gnupg
chmod +x gnupg
