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
zstd -19 ubuntu-16.04.3-server-i386.img > /dev/null 2>&1
EOT
chmod +x compress-zstd
