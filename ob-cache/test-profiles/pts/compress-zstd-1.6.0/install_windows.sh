#!/bin/sh
unzip -o zstd-v1.5.4-win64.zip
chmod +x zstd.exe
cat > compress-zstd <<EOT
#!/bin/sh
./zstd.exe -T\$NUM_CPU_CORES \$@ silesia.tar > \$LOG_FILE 2>&1
sed -i -e "s/\r/\n/g" \$LOG_FILE 
EOT
chmod +x compress-zstd
