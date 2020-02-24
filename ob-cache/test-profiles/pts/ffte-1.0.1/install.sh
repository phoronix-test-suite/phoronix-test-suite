#!/bin/sh

tar -xzvf ffte-5.0.tgz
cd ~/ffte-5.0/tests/
make
cd ~/ffte-5.0/mpi/tests/
make

cd ~/

cat>ffte<<EOT
#!/bin/sh
cd ~/ffte-5.0/

# Very simple test right now... Please feel free to extend and submit patches.
# We should also be doing something like: \$@ > \$LOG_FILE 2>&1
# Instead of the static text below as the above statement would get it from the passed XML test settings

echo 64 | ./tests/speed1d > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x ffte

