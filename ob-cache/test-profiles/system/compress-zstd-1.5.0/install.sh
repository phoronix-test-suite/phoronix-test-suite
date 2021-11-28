#!/bin/sh

if which zstd>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: zstd is not found on the system! This test profile needs a working Zstandard zstd binary in the PATH."
	echo 2 > ~/install-exit-status
fi

cat > compress-zstd <<EOT
#!/bin/sh
zstd -T\$NUM_CPU_CORES \$@ FreeBSD-12.2-RELEASE-amd64-memstick.img > \$LOG_FILE 2>&1
sed -i -e "s/\r/\n/g" \$LOG_FILE 

zstd -V > ~/pts-footnote 2>&1
EOT
chmod +x compress-zstd
