#!/bin/sh

case $OS_TYPE in
	"MacOSX" )
		gzip -d -f Geekbench21-MacOSX.dmg.gz
		# TODO: verify this works

		echo "#!/bin/sh
		echo 'n' | ./Geekbench21-MacOSX.dmg > \$LOG_FILE 2>&1
		echo \$? > ~/test-exit-status" > geekbench
		chmod +x geekbench
	;;
	"Linux" )
		tar -xvf Geekbench21-Linux.tar.gz

		echo "#!/bin/sh
		cd Geekbench21-Linux/
		echo 'n' | ./geekbench > \$LOG_FILE 2>&1
		echo \$? > ~/test-exit-status" > geekbench
		chmod +x geekbench
	;;
esac
