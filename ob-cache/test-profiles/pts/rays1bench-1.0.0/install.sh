#!/bin/sh

unzip -o rays1bench-20200109.zip

cd rays1bench-master
python3 bench.py --latest --compile-only
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd rays1bench-master

python3 bench.py  --latest > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > rays1bench
chmod +x rays1bench
