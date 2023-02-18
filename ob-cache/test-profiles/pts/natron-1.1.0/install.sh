#!/bin/sh
tar -xf Natron-2.4.3-Linux-x86_64-no-installer.tar.xz
unzip -o Natron_2.3.12_Spaceship.zip
echo "#!/bin/bash
mkdir -p ~/.config/INRIA

echo \"[General]
SoftwareVersionMajor=2
existingSettings=true
NatronCacheVersionSettingsKey=4
NATRON_SHORTCUTS_DEFAULT_VERSION=8
checkForUpdates=false
General=false
Threading=true
noRenderThreads=\$NUM_CPU_PHYSICAL_CORES
Rendering=false
GPU%20Rendering=false
Project%20Setup=false
maxOpenGLContexts=1
enableOpenGLRendering=disabled
Caching=false
Viewer=false\" > ~/.config/INRIA/Natron.conf
cd Natron-2.4.3-Linux-x86_64-no-installer/
./Natron -b ~/\$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > natron
chmod +x natron
