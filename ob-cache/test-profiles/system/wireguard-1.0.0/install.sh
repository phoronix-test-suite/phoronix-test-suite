#!/bin/sh

if which iperf3 >/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: iperf3 is not found on the system! This test profile needs the 'iperf3' command in the PATH"
	echo 2 > ~/install-exit-status
	exit
fi

if which wg >/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: WireGuard is not found on the system! This test profile needs the 'wg' command in the PATH"
	echo 2 > ~/install-exit-status
	exit
fi

tar -xf wireguard-for-pts-1.tar.xz
chmod +x wireguard-for-pts.sh

cat>wireguard<<EOT
#!/bin/sh
./wireguard-for-pts.sh > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x wireguard

