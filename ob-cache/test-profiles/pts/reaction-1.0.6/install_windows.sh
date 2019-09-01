#!/bin/sh

unzip -o Reaction-1.0-win32.zip

mv Reaction Reaction-game

mkdir -p ~/.Reaction/Boomstick
unzip -o reaction-pts-data-1.zip
mv Reaction.cfg ~/.Reaction/Boomstick
mv demos/ ~/.Reaction/Boomstick

echo "#!/bin/sh
cd Reaction-game/
./Reaction.x86.exe +timedemo 1 +set demodone \"quit\" +set demoloop1 \"demo pts; set nextdemo vstr demodone\" +vstr demoloop1 +set com_speeds 1 \$@ > \$LOG_FILE 2>&1" > reaction
chmod +x reaction
