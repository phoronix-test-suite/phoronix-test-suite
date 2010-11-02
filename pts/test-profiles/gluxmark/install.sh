#!/bin/sh

unzip -o gluxMark2.2_src.zip
cd gluxMark2.2_src/
make bin/bench
echo \$? > ~/install-exit-status
cd ~/

echo "#!/bin/sh
cd gluxMark2.2_src/
./bin/bench \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > gluxmark
chmod +x gluxmark
