#!/bin/bash
export PATH="$PATH:C:\WINDOWS\system32"
7z x -y cpu2017-1_0_5.iso -ocpu2017
cd cpu2017
chmod +x install.bat
echo " " | ./install.bat install
cd install
cp -f config/Example-VisualStudio.cfg config/pts.cfg

sed -i 's/rem set SHRC_COMPILER_PATH_SET=yes/set SHRC_COMPILER_PATH_SET=yes/g' shrc.bat
sed -i 's/rem  INCLUDE.  Check your compiler documentation./call "C:\\Program Files (x86)\\Microsoft Visual Studio\\2019\\Community\\VC\\Auxiliary\\Build\\vcvars64.bat"/g' shrc.bat


echo "Microsoft Visual Studio or similar is needed as a compiler. Install Visual Studio Community if needed via https://visualstudio.microsoft.com/thank-you-downloading-visual-studio/?sku=Community&rel=16." > ~/install-message

cd ~

echo "#!/bin/bash
cd cpu2017/install
rm -f result/CPU2017.*
/cygdrive/c/Windows/System32/cmd.exe /c \"shrc.bat & runcpu --config=pts \$@\"

cat result/CPU2017.*.txt > \$LOG_FILE
" > spec-cpu2017
chmod +x spec-cpu2017
