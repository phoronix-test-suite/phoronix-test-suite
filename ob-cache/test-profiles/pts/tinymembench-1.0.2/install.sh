#!/bin/sh

unzip -o tinymembench-20180528.zip
cd tinymembench-master
make
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
cd tinymembench-master
./tinymembench > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > tinymembench
chmod +x tinymembench
