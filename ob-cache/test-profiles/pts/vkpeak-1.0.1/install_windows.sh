#!/bin/sh

unzip -o vkpeak-20210424-windows.zip

cat>vkpeak<<EOT
#!/bin/sh
cd vkpeak-20210424-windows
./vkpeak.exe 0  > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x vkpeak
