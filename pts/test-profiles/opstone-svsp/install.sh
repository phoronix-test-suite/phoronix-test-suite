#!/bin/sh

chmod +x opstone-ssp-athlon64
chmod +x opstone-ssp-pentium4

echo "#!/bin/sh

case \$OS_ARCH in
	\"x86_64\" )
	echo y | ./opstone-ssp-athlon64 \$@ > \$LOG_FILE 2>&1
	;;
	* )
	echo y | ./opstone-ssp-pentium4 \$@ > \$LOG_FILE 2>&1
	;;
esac" > opstone-svsp
chmod +x opstone-svsp
