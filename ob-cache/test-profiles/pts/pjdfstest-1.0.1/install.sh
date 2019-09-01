#!/bin/sh

tar -xf pjdfstest-20180715.tar.xz
mv pjdfstest pjdfstest-install
cd pjdfstest-install
autoreconf -ifs
./configure
make pjdfstest
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd pjdfstest-install
prove -rv tests/ \$@ > \$LOG_FILE 2>&1
# echo \$? > ~/test-exit-status" > pjdfstest
chmod +x pjdfstest
