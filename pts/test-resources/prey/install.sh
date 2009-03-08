#!/bin/sh

unzip -o prey-installer-02192009.bin
rm -rf guis/
rm -rf meta/
rm -rf scripts/
mkdir -p .prey/base/
tar -C .prey/base/ -jxf prey-retail-client-pts-1.tar.bz2
mv data/prey-linux-data/base/* data/prey-linux-x86/base

echo "#!/bin/sh
cd data/prey-linux-x86/
./prey.x86 \$@ > \$LOG_FILE
cat \$LOG_FILE | grep fps" > prey
chmod +x prey

if [ -f prey-base-files.tar ]
  then
     tar -C data/prey-linux-x86/base/ -k -xvf prey-base-files.tar
  else
     echo "Prey Retail Game Files (*.pk4) Must Be Copied Into $1/data/prey-linux-x86/base/"
fi
if [ -f preykey.tar ]
  then
     tar -C .prey/base/ -xvf preykey.tar
  else
     echo "Copy Your Game Key File To $HOME/.prey/base/preykey"
fi

