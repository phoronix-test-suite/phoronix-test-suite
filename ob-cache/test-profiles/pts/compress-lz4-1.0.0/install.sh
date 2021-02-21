#!/bin/sh

tar -xf lz4-1.9.3.tar.gz
cd lz4-1.9.3/
make
echo $? > ~/install-exit-status
cd ~

cat > compress-lz4 <<EOT
#!/bin/sh
./lz4-1.9.3/lz4 \$@ ubuntu-18.04.3-desktop-amd64.iso > \$LOG_FILE 2>&1
echo $? > ~/test-exit-status
EOT
chmod +x compress-lz4
