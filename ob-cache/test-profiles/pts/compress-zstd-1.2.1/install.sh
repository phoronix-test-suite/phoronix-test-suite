#!/bin/sh

tar -xvf zstd-1.4.5.tar.gz
cd zstd-1.4.5
make
echo $? > ~/install-exit-status
cd ~

cat > compress-zstd <<EOT
#!/bin/sh
./zstd-1.4.5/zstd -T\$NUM_CPU_CORES \$@ ubuntu-18.04.3-desktop-amd64.iso > \$LOG_FILE 2>&1
sed -i -e "s/\r/\n/g" \$LOG_FILE 
EOT
chmod +x compress-zstd
