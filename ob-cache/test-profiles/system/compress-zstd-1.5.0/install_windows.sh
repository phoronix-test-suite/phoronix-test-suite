#!/bin/sh

unzip -o zstd-v1.5.0-win64.zip

# The 1.4.9 zip seems to be weird and double compressed...
unzip -o zstd-v1.5.0-win64


cat > compress-zstd <<EOT
#!/bin/sh
./zstd.exe -T\$NUM_CPU_CORES \$@ FreeBSD-12.2-RELEASE-amd64-memstick.img > /dev/null 2>&1
sed -i -e "s/\r/\n/g" \$LOG_FILE 

./zstd.exe -V > ~/pts-footnote 2>&1
EOT
chmod +x compress-zstd
