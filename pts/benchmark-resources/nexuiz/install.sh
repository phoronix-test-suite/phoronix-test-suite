#!/bin/sh

cd $1

if [ ! -f nexuiz-24.zip ]
  then
     wget http://internap.dl.sourceforge.net/sourceforge/nexuiz/nexuiz-24.zip -O nexuiz-24.zip
fi

unzip -o nexuiz-24.zip

echo "#!/bin/sh
cd Nexuiz
./nexuiz-linux-glx.sh +exec normal.cfg \$@ | grep fps" > nexuiz
chmod +x nexuiz

