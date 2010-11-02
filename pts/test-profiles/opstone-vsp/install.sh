#!/bin/sh

chmod +x opstone-sp-athlon64
chmod +x opstone-sp-pentium4

echo "#!/bin/sh

case \$OS_ARCH in
	\"x86_64\" )
	echo y | ./opstone-sp-athlon64 \$@ > \$LOG_FILE 2>&1
	;;
	* )
	echo y | ./opstone-sp-pentium4 \$@ > \$LOG_FILE 2>&1
	;;
esac" > opstone-vsp
chmod +x opstone-vsp
