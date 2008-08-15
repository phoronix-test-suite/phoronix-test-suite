#!/bin/sh

chmod +x quake4-linux-1.4.2.x86.run

./quake4-linux-1.4.2.x86.run --noexec --target .
chmod +x quake4-real

echo "#!/bin/sh
./bin/Linux/x86/quake4.x86 \$@ | grep fps" > quake4
chmod +x quake4

tar -xvf quake4-pts-demo-1.tar.gz
mkdir q4base/demos
mv -f pts.demo q4base/demos/pts.demo

if [ -f quake4-game-files.tar ]
  then
     tar -k -C $1/q4base -xvf $1/quake4-game-files.tar
fi
if [ -f quake4-key.tar ]
  then
     tar -xvf quake4-key.tar
fi


echo "Quake 4 Game Files (*.pk4) Must Be Copied Into $1/q4base"
echo "Also Copy Your Game Key File To $HOME/.quake4/q4base/quake4key (If Not Already There)"

