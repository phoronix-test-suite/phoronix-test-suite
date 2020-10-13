#!/bin/sh

unzip -o zstd-v1.4.5-win64.zip
cat > compress-zstd <<EOT
#!/bin/sh
./zstd.exe -T\$NUM_CPU_CORES \$@ ubuntu-18.04.3-desktop-amd64.iso > \$LOG_FILE 2>&1
sed -i -e "s/\r/\n/g" \$LOG_FILE 
EOT
chmod +x compress-zstd
