#!/bin/sh

7z x Bosphorus_1920x1080_120fps_420_8bit_YUV_RAW.7z
cp SvtAv1Enc-0.8.0.dll SvtAv1Enc.dll
cp SvtAv1EncApp-0.8.0.exe SvtAv1EncApp.exe
chmod +x SvtAv1EncApp.exe

echo "#!/bin/sh
./SvtAv1EncApp.exe \$@ > \$LOG_FILE 2>&1" > svt-av1
chmod +x svt-av1
