#!/bin/sh

unzip -o lz4_win64_v1_9_3.zip
cat > compress-lz4 <<EOT
#!/bin/sh
./lz4.exe \$@ ubuntu-18.04.3-desktop-amd64.iso > \$LOG_FILE 2>&1
EOT
chmod +x compress-lz4
