#!/bin/sh
if which steam>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Steam is not found on the system! This test profile needs a working Steam installation in the PATH"
	echo 2 > ~/install-exit-status
fi
HOME=$DEBUG_REAL_HOME steam steam://install/312670
mkdir -p ~/.steam/steam/steamapps/compatdata/312670/pfx/drive_c/users/steamuser/Local\ Settings/Application\ Data/Strange\ Brigade/
echo "#!/bin/bash
rm -f \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/312670/pfx/drive_c/users/steamuser/My\ Documents/StrangeBrigade_Benchmark/SB__*.txt
echo \"[Display Settings]

###### Preference of D3D12 or Vulkan - used when bypassing the launcher (e.g. via -benchmark commandline). ###### 
###### 0 = Vulkan, 1 = D3D12 ###### 
D3D12 = 0

Resolution_Width = \$1
Resolution_Height = \$2

###### 0.5 to 2.0 ###### 
RenderScale = 1.000000

###### 0 = Exclusive Fullscreen, 1 = Window, 2 = Fullscreen Borderless Window ###### 
Windowed = 0

###### 1 = On, 0 = Off ###### 
MotionBlur = 1

###### 1 = On, 0 = Off ###### 
AmbientOcclusion = 1

###### 1 = On, 0 = Off ###### 
VSync = 0

###### 1 = On, 0 = Off ###### 
ReduceMouseLag = 0

###### 1 = On, 0 = Off ###### 
AsyncCompute = 1

###### 1 = On, 0 = Off ###### 
Tessellation = 0

###### 0 = Low, 1 = Medium, 2 = High, 3 = Ultra ###### 
TextureDetail = \$3

###### 0 = Low, 1 = Medium, 2 = High, 3 = Ultra ###### 
ShadowDetail = \$3

###### 0 = Off, 1 = Low, 2 = Medium, 3 = High, 4 = Ultra ###### 
AntiAliasing = \$3

###### 0 = Low, 1 = Medium, 2 = High, 3 = Ultra ###### 
DrawDistance = \$3

###### 1 = Off, highest value is 16 ###### 
AnisotropicFiltering = 4

######  0 = Low, 1 = Medium, 2 = High, 3 = Ultra ###### 
SSReflectionsQuality = 1

###### 0.0 = None, 1.0 = Full ###### 
Brightness = 0.500000

###### 0 = Off, 1 = On ###### 
ObscuranceFields = 0

###### 0 = Off, 1 = Normal, 2 = High ###### 
ReverbQuality = 1

###### 0 = Off, 1 = On ###### 
HDR = 0
\" > \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/312670/pfx/drive_c/users/steamuser/Local\ Settings/Application\ Data/Strange\ Brigade/GraphicsOptions.ini

HOME=\$DEBUG_REAL_HOME steam -applaunch 312670 -benchmark 
sleep 30
while pgrep -x \"StrangeBrigade_\" > /dev/null; do
    sleep 2
done
sleep 3
cat \$DEBUG_REAL_HOME/.steam/steam/steamapps/compatdata/312670/pfx/drive_c/users/steamuser/My\ Documents/StrangeBrigade_Benchmark/SB__*.txt > \$LOG_FILE" > strange-brigade
chmod +x strange-brigade