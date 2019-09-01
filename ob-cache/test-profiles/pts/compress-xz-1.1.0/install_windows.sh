#!/bin/sh

unzip -o xz-5.2.4-windows.zip
cat > compress-xz <<EOT
#!/bin/sh
./bin_x86-64/xz.exe -7 -k -T\$NUM_CPU_CORES ubuntu-16.04.3-server-i386.img 2>&1
EOT
chmod +x compress-xz
