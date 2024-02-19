#!/bin/sh
unzip -o vkpeak-20230730-ubuntu.zip
cat>vkpeak<<EOT
#!/bin/sh
cd vkpeak-20230730-ubuntu
./vkpeak 0 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x vkpeak
