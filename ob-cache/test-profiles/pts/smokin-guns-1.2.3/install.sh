#!/bin/sh

unzip -o Smokin_Guns_1.1.zip
unzip -o smokinguns-benchmark-3.zip

mv -f q3config.cfg Smokin\'\ Guns\ 1.1/smokinguns/
mv -f demos/ Smokin\'\ Guns\ 1.1/smokinguns/
chmod +x Smokin\'\ Guns\ 1.1/smokinguns.i386

echo "#!/bin/sh
cd Smokin\'\ Guns\ 1.1/
./smokinguns.i386 +timedemo 1 +set demodone \"quit\" +set demoloop1 \"demo pts12; set nextdemo vstr demodone\" +vstr demoloop1 +r_mode -1 +vid_restart \$@ > \$LOG_FILE 2>&1" > smokin-guns
chmod +x smokin-guns
