#!/bin/sh
USERNAME=`basename $DEBUG_REAL_HOME`
unzip -o f1-2022-prefs-1.zip
cp -f *.xml /cygdrive/c/Users/$USERNAME/Documents/My\ Games/F1\ 22/hardwaresettings
echo "#!/bin/bash
rm -f /cygdrive/c/Users/$USERNAME/Documents/My\ Games/F1\ 22/benchmark/*.csv
rm -f /cygdrive/c/Users/$USERNAME/Documents/My\ Games/F1\ 22/benchmark/*.xml
cd /cygdrive/c/Users/$USERNAME/Documents/My\ Games/F1\ 22/hardwaresettings
cp -f \$3 hardware_settings_config.xml
sed -ie \"s/1920/\$1/g\" hardware_settings_config.xml
sed -ie \"s/1080/\$2/g\" hardware_settings_config.xml
CPU_STR=`powershell -NoProfile "Get-WmiObject -Class Win32_Processor | Select-Object -Property Name -ExpandProperty Name" | xargs`
sed -i \"s/73BF/\$GPU_DEVICE_ID/g\" hardware_settings_config.xml
sed -i \"s/AMD Ryzen 9 7950X 16-Core Processor/\$CPU_STR/g\" hardware_settings_config.xml
HOME=\$DEBUG_REAL_HOME  /cygdrive/c/Program\ Files\ \(x86\)/Steam/steam.exe -applaunch 1692250 -benchmark example_benchmark.xml
sleep 30
until [ -e /cygdrive/c/Users/$USERNAME/Documents/My\ Games/F1\ 22/benchmark/*.xml ]
do
     sleep 5
done
cat /cygdrive/c/Users/$USERNAME/Documents/My\ Games/F1\ 22/benchmark/*.xml | grep results | sed \"s/\\\"/ /g\" > \$LOG_FILE
cat /cygdrive/c/Users/$USERNAME/Documents/My\ Games/F1\ 22/benchmark/*.csv >> \$LOG_FILE" > f122
chmod +x f122