#!/bin/sh

tar -zxvf phpbench-0.8.1.tar.gz

echo "#!/bin/sh
cd phpbench-0.8.1/
php phpbench.php \$@ > \$LOG_FILE 2> /dev/null" > phpbench
chmod +x phpbench
