#!/bin/sh

unzip -o S-20181019.zip

echo "#!/bin/sh

if [ \"\$1\" = \"r\" ]; then
	mix=\"10 0\"
else
	mix=\"5 5\"
fi

case \$2 in
gnome-terminal)
	comm=\"replay-startup-io gnometerm\"
;;
xterm)
	comm=\"replay-startup-io xterm\"
;;
lowriter)
	comm=\"replay-startup-io lowriter\"
;;
esac

mylog=$(pwd)/tmplog 

cd S-master

if [ ! \"X\$6\" = \"X\" ]
then
echo Invoking special dir >\$mylog
sed -i \"s<.*BASE_DIR=.*<BASE_DIR=\$3<\" def_config_params.sh >>\$mylog 2>&1
else
echo Invoking local dir >\$mylog
sed -i 's<.*BASE_DIR=.*<BASE_DIR=\$PWD/../workfiles<' def_config_params.sh >>\$mylog 2>&1
fi

cd comm_startup_lat
./comm_startup_lat.sh \"\" \$mix seq 3 \"\$comm\" >> \$mylog
egrep \"Latency statistics\" -A 2 \$mylog | tail -n 1 | awk \"{ printf \\\"Average start-up time: %g\\n\\\",  \\\$3 }\" | tail -n 1 > \$LOG_FILE
rm \$mylog
" > startuptime-run
chmod +x startuptime-run

