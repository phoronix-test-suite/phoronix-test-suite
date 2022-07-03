#!/bin/sh

tar -xf speedtest-cli-2.1.3.tar.gz

cd speedtest-cli-2.1.3
chmod +x speedtest.py
cd ~

echo "#!/bin/sh
cd speedtest-cli-2.1.3

sleep 5
python3 ./speedtest.py > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
" > speedtest-cli

chmod +x speedtest-cli
