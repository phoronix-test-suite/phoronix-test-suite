#!/bin/sh

tar -zxvf fs_mark-3.3.tar.gz
cd fs_mark-3.3/
mkdir scratch
make
echo $? > ~/install-exit-status

echo "#!/bin/sh
cd fs_mark-3.3/
./fs_mark \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > ~/fs-mark
chmod +x ~/fs-mark
