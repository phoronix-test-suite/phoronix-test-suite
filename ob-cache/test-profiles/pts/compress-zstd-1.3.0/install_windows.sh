#!/bin/sh

unzip -o zstd-v1.4.9-win64.zip

# The 1.4.9 zip seems to be weird and double compressed...
unzip -o zstd-v1.4.9-win64

cat > compress-zstd <<EOT
#!/bin/sh
./zstd.exe -T\$NUM_CPU_CORES \$@ FreeBSD-12.2-RELEASE-amd64-memstick.img > \$LOG_FILE 2>&1
sed -i -e "s/\r/\n/g" \$LOG_FILE 
EOT
chmod +x compress-zstd
