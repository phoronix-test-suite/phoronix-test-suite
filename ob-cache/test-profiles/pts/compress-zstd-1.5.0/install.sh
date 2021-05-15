#!/bin/sh

tar -xvf zstd-1.5.0.tar.gz
cd zstd-1.5.0
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~

cat > compress-zstd <<EOT
#!/bin/sh
./zstd-1.5.0/zstd -T\$NUM_CPU_CORES \$@ FreeBSD-12.2-RELEASE-amd64-memstick.img > \$LOG_FILE 2>&1
sed -i -e "s/\r/\n/g" \$LOG_FILE 
EOT
chmod +x compress-zstd
