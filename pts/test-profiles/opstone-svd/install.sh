#!/bin/sh

chmod +x opstone-svd-athlon64
chmod +x opstone-svd-pentium4

echo "#!/bin/sh

case \$OS_ARCH in
	\"x86_64\" )
	echo y | ./opstone-svd-athlon64 \$@ > \$LOG_FILE 2>&1
	;;
	* )
	echo y | ./opstone-svd-pentium4 \$@ > \$LOG_FILE 2>&1
	;;
esac" > opstone-svd
chmod +x opstone-svd
