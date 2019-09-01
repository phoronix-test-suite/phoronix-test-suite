#!/bin/sh

case $OS_ARCH in
	"x86_64" )
	tar -xvzf Reaction-1.0-Linux-x86_64.tar.gz
	;;
	* )
	tar -xvzf Reaction-1.0-Linux-i386.tar.gz
	;;
esac

mv Reaction Reaction-game

mkdir -p ~/.Reaction/Boomstick
unzip -o reaction-pts-data-1.zip
mv Reaction.cfg ~/.Reaction/Boomstick
mv demos ~/.Reaction/Boomstick

echo "#!/bin/sh
cd Reaction-game/
case \$OS_ARCH in
	\"x86_64\" )
	./Reaction.x86_64 +timedemo 1 +set demodone \"quit\" +set demoloop1 \"demo pts; set nextdemo vstr demodone\" +vstr demoloop1 +set com_speeds 1 \$@ > \$LOG_FILE 2>&1
	;;
	* )
	./Reaction.i386 +timedemo 1 +set demodone \"quit\" +set demoloop1 \"demo pts; set nextdemo vstr demodone\" +vstr demoloop1 +set com_speeds 1 \$@ > \$LOG_FILE 2>&1
	;;
esac" > reaction
chmod +x reaction
