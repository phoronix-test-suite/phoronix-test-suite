#!/bin/sh
USERNAME=`basename $DEBUG_REAL_HOME`
HOME=\$DEBUG_REAL_HOME /cygdrive/c/Program\ Files\ \(x86\)/Steam/steam.exe steam://install/312670
mkdir -p /cygdrive/c/Users/$USERNAME/Local\ Settings/Application\ Data/Strange\ Brigade/
echo "#!/bin/bash
rm -f /cygdrive/c/Users/$USERNAME/Documents/StrangeBrigade_Benchmark/SB__*.txt
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
\" > /cygdrive/c/Users/$USERNAME/Local\ Settings/Application\ Data/Strange\ Brigade/GraphicsOptions.ini
HOME=\$DEBUG_REAL_HOME /cygdrive/c/Program\ Files\ \(x86\)/Steam/steam.exe -applaunch 312670 -benchmark 
sleep 30
until [ -e /cygdrive/c/Users/$USERNAME/Documents/StrangeBrigade_Benchmark/SB__*.txt ]
do
     sleep 5
done
cat /cygdrive/c/Users/$USERNAME/Documents/StrangeBrigade_Benchmark/SB__*.txt > \$LOG_FILE" > strange-brigade
chmod +x strange-brigade