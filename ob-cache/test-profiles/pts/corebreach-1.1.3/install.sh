#!/bin/sh

unzip -o CoreBreachPTS.zip

echo "#!/bin/sh
case \$OS_TYPE in
	\"MacOSX\")
		./CoreBreachPTS/CoreBreach.app/Contents/MacOS/CoreBreach-SDL \$@ > \$LOG_FILE 2>&1
	;;
	*)
		cd CoreBreachPTS/
		./CoreBreach.sh \$@ > \$LOG_FILE 2>&1
	;;
esac" > corebreach
chmod +x corebreach
