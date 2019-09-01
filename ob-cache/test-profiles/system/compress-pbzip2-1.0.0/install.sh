#!/bin/sh

if which pbzip2 >/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: PBZIP2 is not found on the system!"
	echo 2 > ~/install-exit-status
fi

cat > compress-pbzip2 <<EOT
#!/bin/sh
pbzip2 -c -p\$NUM_CPU_CORES -r -5 \$TEST_EXTENDS/linux-4.3.tar > /dev/null 2>&1
EOT
chmod +x compress-pbzip2
