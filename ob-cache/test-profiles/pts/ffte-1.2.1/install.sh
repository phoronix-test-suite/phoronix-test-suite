#!/bin/sh

tar -xzvf ffte-7.0.tgz
cd ~/ffte-7.0/tests/
make speed3d
echo $? > ~/install-exit-status

cd ~/

cat>ffte<<EOT
#!/bin/sh
cd ~/ffte-7.0/
echo 256,256,256 | ./tests/speed3d > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x ffte

