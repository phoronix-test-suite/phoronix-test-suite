#!/bin/sh
if which openssl >/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: OpenSSL is not found on the system! No openssl in PATH."
	echo 2 > ~/install-exit-status
fi
echo "#!/bin/sh
openssl version > ~/pts-footnote 2>/dev/null
openssl speed -multi \$NUM_CPU_CORES -seconds 30 \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > openssl
chmod +x openssl


