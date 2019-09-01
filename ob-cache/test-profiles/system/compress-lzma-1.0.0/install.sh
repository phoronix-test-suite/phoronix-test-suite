#!/bin/sh

if which lzma >/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: lzma is not found on the system!"
	echo 2 > ~/install-exit-status
fi

cat > compress-lzma <<EOT
#!/bin/sh
lzma --version | cut -d \" \" -f 5 > ~/pts-test-version 2>/dev/null
lzma -q -c ./compressfile > /dev/null 2>&1
EOT
chmod +x compress-lzma
