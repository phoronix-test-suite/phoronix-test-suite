#!/bin/sh

cd $1

if [ ! -f wine-git ]
  then
     git clone git://source.winehq.org/git/wine.git wine-git
     cd wine-git/
  else
     cd wine-git/
     git pull
fi

make clean
./configure
make depend
make

cd ..

echo "#!/bin/sh
cd wine-git/
export WINETEST_PLATFORM=wine
./wine \$@" > wine
chmod +x wine
