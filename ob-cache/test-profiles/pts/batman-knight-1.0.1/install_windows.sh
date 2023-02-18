#!/bin/bash
HOME=$DEBUG_REAL_HOME steam steam://install/208650
unzip -o GFXSettings-Batman-Knight-1.zip
echo "#!/bin/bash
rm -f \"C:\Program Files (x86)\Steam\steamapps\common\Batman Arkham Knight\BmGame\Logs\benchmark.log\"
cp -f GFXSettings-\$3-BatmanArkhamKnight.xml \"\$DEBUG_REAL_HOME\Documents\WB Games\Batman Arkham Knight/GFXSettings.BatmanArkhamKnight.xml\"
HOME=\$DEBUG_REAL_HOME /cygdrive/c/Program\ Files\ \(x86\)/Steam/steamapps/common/Batman\ Arkham\ Knight/Binaries/Win64/BatmanAK.exe batentry?Area=UnderAce_A4,CityZ_08,CityZ_07,BatEntry__Benchmark_ChJ7_Bm?Chapters=0,A0,B0,C0,D0,E0,F0,G0,H0,I0,J7,K0,L0,M0,N0,O0,P0,Q0,R0,S0,T0,U0,V0,W0,X0,Y0,Z0,_F0,_G0,_K0,_M0?NoLevelOffsets?NoFadeIn?Start=BMAK_Benchmark_Start -NOLOGO ResX=\$1 ResY=\$2 &
sleep 30
while [ ! -f /cygdrive/c/Program\ Files\ \(x86\)/Steam/steamapps/common/Batman\ Arkham\ Knight/BmGame/Logs/benchmark.log ]
do
  sleep 2
done
killall -9 BatmanAK
cat /cygdrive/c/Program\ Files\ \(x86\)/Steam/steamapps/common/Batman\ Arkham\ Knight/BmGame/Logs/benchmark.log > \$LOG_FILE" > batman-knight
chmod +x batman-knight
