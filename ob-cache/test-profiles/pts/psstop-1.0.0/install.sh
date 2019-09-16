#!/bin/sh

mkdir psstop-3
tar -xvf psstop-3.tar.gz -C psstop-3
echo $? > ~/install-exit-status
cat>psstop<<EOT
#!/bin/sh
cd $HOME
./psstop-3/psstop | grep "Total" | cut -d'K' -f1 | cut -d' ' -f3> \$LOG_FILE
echo \$? > ~/test-exit-status" > psstop_memory
EOT
chmod +x psstop
