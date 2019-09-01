#!/bin/sh

chmod +x quake4-linux-1.4.2.x86.run

./quake4-linux-1.4.2.x86.run --noexec --target .
chmod +x bin/Linux/x86/quake4.x86

echo "#!/bin/sh
./bin/Linux/x86/quake4.x86 \$@ > \$LOG_FILE 2>&1
cat \$LOG_FILE | grep fps" > quake4
chmod +x quake4

tar -zxvf quake4-pts-demo-1.tar.gz
mkdir q4base/demos
mv -f pts.demo q4base/demos/pts.demo

if [ -f quake4-game-files.tar ]
  then
     tar -k -C ~/q4base -xvf ~/quake4-game-files.tar
  else
     echo "Quake 4 Game Files (*.pk4) Must Be Copied Into $1/q4base"
fi
if [ -f quake4-key.tar ]
  then
     tar -xvf quake4-key.tar

	if [ -f quake4key ]
	  then
		mkdir -p ~/.quake4/q4base/
		mv -f quake4key ~/.quake4/q4base/
	fi

  else
     echo "Copy Your Game Key File To $HOME/.quake4/q4base/quake4key (If Not Already There)"
fi

