#!/bin/sh

if which openssl >/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: OpenSSL is not found on the system!"
	echo 2 > ~/install-exit-status
fi

echo "#!/bin/sh
openssl version | cut -d \" \" -f 2 > ~/pts-test-version 2>/dev/null
openssl speed rsa4096 -multi \$NUM_CPU_CORES > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > openssl
chmod +x openssl


