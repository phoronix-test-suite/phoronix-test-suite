#!/bin/sh

# Nexuiz 2.4.2

cd $1

if [ -x /usr/games/nexuiz -a -r /usr/share/games/nexuiz/data/music20080229.pk3 ]; then
cat > nexuiz <<'EOT'
#!/bin/sh
/usr/games/nexuiz +exec normal.cfg $@ | grep fps
EOT
else

unzip -o nexuiz-242.zip

echo "#!/bin/sh
cd Nexuiz
./nexuiz-linux-glx.sh +exec normal.cfg \$@ | grep fps" > nexuiz
fi
chmod +x nexuiz
