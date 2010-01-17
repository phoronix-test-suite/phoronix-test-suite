#!/bin/sh

case $OS_TYPE in
	"MacOSX" )
		gzip -d -f Geekbench21-MacOSX.dmg.gz

		echo "#!/bin/sh
		echo 'n' | open /Volumes/Geekbench\ 2.1/Geekbench.app/Contents/MacOS/Geekbench > \$LOG_FILE 2>&1
		echo \$? > ~/test-exit-status" > geekbench
		chmod +x geekbench
	;;
	"Linux" )
		tar -zxvf Geekbench21-Linux.tar.gz

		echo "#!/bin/sh
		cd Geekbench21-Linux/
		echo 'n' | ./geekbench > \$LOG_FILE 2>&1
		echo \$? > ~/test-exit-status" > geekbench
		chmod +x geekbench
	;;
esac
