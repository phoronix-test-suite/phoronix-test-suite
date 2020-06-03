#!/bin/sh

unzip -o Smokin_Guns_1.1.zip
unzip -o smokinguns-benchmark-3.zip

mv -f q3config.cfg Smokin\'\ Guns\ 1.1/smokinguns
mv -f demos/ Smokin\'\ Guns\ 1.1/smokinguns/

echo "#!/bin/sh
cd Smokin\'\ Guns\ 1.1/
./smokinguns.exe +timedemo 1 +set demodone \"quit\" +set demoloop1 \"vid_restart; demo pts12; set nextdemo vstr demodone\" +vstr demoloop1 \$@ > \$LOG_FILE" > smokin-guns
chmod +x smokin-guns
