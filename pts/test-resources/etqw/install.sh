#!/bin/sh

cd $1

chmod +x ETQW-client-1.4-full.x86.run
unzip -o ETQW-client-1.4-full.x86.run

echo "#!/bin/sh
cd data
./etqw.x86 \$@ | grep fps" > etqw
chmod +x etqw

if [ ! -f etqw-demo.tar.bz2 ]
  then
     wget http://www.phoronix-test-suite.com/benchmark-files/etqw-demo-1.tar.bz2 -O etqw-demo.tar.bz2
fi
tar -jxvf etqw-demo.tar.bz2
mkdir data/base/demos
mv -f pts.ndm data/base/demos/pts.ndm

# etqw_pts.cfg
# pts.ndm is 2816 frames so we wait a little longer to get the fps
# AND WE QUIT (the bench blocks otherwise) 
echo "
set com_unlockFPS \"1\"
timenetdemo pts
wait 2818
echo ======================
echo wait '# of frames + 2'
echo timenetdemo ended
echo quit
echo ======================
quit" > data/base/etqw_pts.cfg

# Since the game is installed, we search for the game directory
# where there are the ET:QW Game Files (*.mega) and (*.pk4)
# in order to "symlink" them

b=`pwd`
GAME_FILES_TYPE_DIR="megatextures"

CreateSymlinks () {
   cd $ETQW_BASE_MEGAT
   cd ..
   ETQW_BASE=`pwd`
   echo "   *.pk4  found in $ETQW_BASE 
   *.mega found in $ETQW_BASE_MEGAT"
#   echo "\\nwe are here : $b"  
   mkdir $b/data/base/megatextures/
   ln -s $ETQW_BASE_MEGAT/*.mega  $b'/data/base/megatextures'
   ln -s $ETQW_BASE/*.pk4  $b'/data/base'
   echo "symlinks created in
   $b/data/base/megatextures
   $b/data/base"
}


ETQW_BASE_MEGAT=`find -P $HOME -name $GAME_FILES_TYPE_DIR`
if [ "$ETQW_BASE_MEGAT" != "" ] #  found in '/home/username/'
  then
       CreateSymlinks
       exit 0
fi

ETQW_BASE_MEGAT=`find -P /usr/ -name $GAME_FILES_TYPE_DIR`
if [ "$ETQW_BASE_MEGAT" != "" ] #  found in '/usr/'
  then
      CreateSymlinks
      exit 0
fi

ETQW_BASE_MEGAT=`find -P / -name $GAME_FILES_TYPE_DIR`
if [ "$ETQW_BASE_MEGAT" != "" ] #  found in '/' so 
  then
      CreateSymlinks
       exit 0
fi	
           
echo  "no megatexture found
copy ET:QW Game Files (*.mega) in bases/megatextures
copy ET:QW Game Files(*.pk4) in bases"

