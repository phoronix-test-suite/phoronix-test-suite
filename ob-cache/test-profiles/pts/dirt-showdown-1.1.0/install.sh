#!/bin/sh

HOME=$DEBUG_REAL_HOME steam steam://install/201700

echo "#!/bin/sh
cd \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/DiRT\ Showdown

rm -f \$DEBUG_REAL_HOME/.local/share/vpltd/dirt/GameDocuments/My\ Games/DiRT\ Showdown/benchmarks/*.xml

case \"\$3\" in
1) echo \"<?xml version=\\\"1.0\\\" encoding=\\\"UTF-8\\\" ?>
<hardware_settings_config version=\\\"62\\\" deviceId=\\\"0x0001\\\">
	<cpu>
		<threadStrategy workerMapFile=\\\"system/workerMap8Core.xml\\\" forceFeedbackProcessor=\\\"6\\\" dvdStorageProcessor=\\\"7\\\" dataSetMonitorProcessor=\\\"4\\\" renderProcessor=\\\"0\\\" updateProcessor=\\\"2\\\" fileStreamProcessor=\\\"5\\\" />
	</cpu>
	<audio_card>
		<audio mixing=\\\"rapture3D\\\" />
	</audio_card>
	<graphics_card>
		<directx forcedx10=\\\"false\\\" />
		<eyefinity force=\\\"\\\" osd=\\\"\\\" />
		<stereo enabled=\\\"false\\\" separation=\\\"0.015\\\" convergence=\\\"1.5\\\" />
		<advanced forward=\\\"false\\\" global_illumination=\\\"false\\\" />
		<resolution width=\\\"\$1\\\" height=\\\"\$2\\\" aspect=\\\"auto\\\" fullscreen=\\\"true\\\" vsync=\\\"0\\\" multisampling=\\\"\$4\\\">
			<refreshRate rate=\\\"59\\\" />
		</resolution>
		<gamma level=\\\"1.0\\\" />
	</graphics_card>
	<shadows enabled=\\\"true\\\" size=\\\"768\\\" maskQuality=\\\"1\\\" />
	<particles enabled=\\\"true\\\" wind=\\\"false\\\" dynamicRes=\\\"true\\\" />
	<crowd enabled=\\\"true\\\" detail=\\\"0\\\" />
	<cloth enabled=\\\"true\\\" tessellation=\\\"false\\\" />
	<postprocess quality=\\\"0\\\" />
	<groundcover mode=\\\"atoc\\\" clutter=\\\"false\\\" />
	<objects lod=\\\"0.75\\\" maxlod=\\\"1\\\" />
	<trees lod=\\\"0.75\\\" maxlod=\\\"1\\\" />
	<vehicles characterQuality=\\\"0\\\" lodQuality=\\\"0\\\" />
	<envmap faces=\\\"0\\\" size=\\\"256\\\" drawallobjects=\\\"false\\\" />
	<water update=\\\"false\\\" detail=\\\"0\\\" tessellation=\\\"false\\\" />
	<skidmarks enabled=\\\"false\\\" />
	<dynamic_ambient_occ enabled=\\\"true\\\" quality=\\\"0\\\" />
	<night_lighting volumes=\\\"false\\\" lights=\\\"150\\\" shadows=\\\"false\\\" />
	<physics environmentalDamage=\\\"true\\\" vehicleDamage=\\\"true\\\" />
	<input device_type=\\\"auto\\\" />
	<motion enabled=\\\"true\\\" ip=\\\"dbox\\\" port=\\\"20777\\\" delay=\\\"1\\\" extradata=\\\"0\\\" />
</hardware_settings_config>\" > \$DEBUG_REAL_HOME/.local/share/vpltd/dirt/GameDocuments/My\ Games/DiRT\ Showdown/hardwaresettings/hardware_settings_config.xml ;;

2) echo \"<?xml version=\\\"1.0\\\" encoding=\\\"UTF-8\\\" ?>
<hardware_settings_config version=\\\"62\\\" deviceId=\\\"0x0001\\\">
	<cpu>
		<threadStrategy workerMapFile=\\\"system/workerMap8Core.xml\\\" forceFeedbackProcessor=\\\"6\\\" dvdStorageProcessor=\\\"7\\\" dataSetMonitorProcessor=\\\"4\\\" renderProcessor=\\\"0\\\" updateProcessor=\\\"2\\\" fileStreamProcessor=\\\"5\\\" />
	</cpu>
	<audio_card>
		<audio mixing=\\\"rapture3D\\\" />
	</audio_card>
	<graphics_card>
		<directx forcedx10=\\\"false\\\" />
		<eyefinity force=\\\"\\\" osd=\\\"\\\" />
		<stereo enabled=\\\"false\\\" separation=\\\"0.015\\\" convergence=\\\"1.5\\\" />
		<advanced forward=\\\"false\\\" global_illumination=\\\"false\\\" />
		<resolution width=\\\"\$1\\\" height=\\\"\$2\\\" aspect=\\\"auto\\\" fullscreen=\\\"true\\\" vsync=\\\"0\\\" multisampling=\\\"\$4\\\">
			<refreshRate rate=\\\"59\\\" />
		</resolution>
		<gamma level=\\\"1.0\\\" />
	</graphics_card>
	<shadows enabled=\\\"true\\\" size=\\\"2048\\\" maskQuality=\\\"1\\\" />
	<particles enabled=\\\"true\\\" wind=\\\"true\\\" dynamicRes=\\\"false\\\" />
	<crowd enabled=\\\"true\\\" detail=\\\"2\\\" />
	<cloth enabled=\\\"true\\\" tessellation=\\\"true\\\" />
	<postprocess quality=\\\"2\\\" />
	<groundcover mode=\\\"blended\\\" clutter=\\\"true\\\" />
	<objects lod=\\\"1.25\\\" maxlod=\\\"0\\\" />
	<trees lod=\\\"1.25\\\" maxlod=\\\"0\\\" />
	<vehicles characterQuality=\\\"2\\\" lodQuality=\\\"2\\\" />
	<envmap faces=\\\"6\\\" size=\\\"512\\\" drawallobjects=\\\"false\\\" />
	<water update=\\\"true\\\" detail=\\\"2\\\" tessellation=\\\"true\\\" />
	<skidmarks enabled=\\\"true\\\" />
	<dynamic_ambient_occ enabled=\\\"true\\\" quality=\\\"1\\\" />
	<night_lighting volumes=\\\"true\\\" lights=\\\"0\\\" shadows=\\\"true\\\" />
	<physics environmentalDamage=\\\"true\\\" vehicleDamage=\\\"true\\\" />
	<input device_type=\\\"auto\\\" />
	<motion enabled=\\\"true\\\" ip=\\\"dbox\\\" port=\\\"20777\\\" delay=\\\"1\\\" extradata=\\\"0\\\" />
</hardware_settings_config>\" > \$DEBUG_REAL_HOME/.local/share/vpltd/dirt/GameDocuments/My\ Games/DiRT\ Showdown/hardwaresettings/hardware_settings_config.xml ;;
3 ) echo \"<?xml version=\\\"1.0\\\" encoding=\\\"UTF-8\\\" ?>
<hardware_settings_config version=\\\"62\\\" deviceId=\\\"0x0001\\\">
	<cpu>
		<threadStrategy workerMapFile=\\\"system/workerMap8Core.xml\\\" forceFeedbackProcessor=\\\"6\\\" dvdStorageProcessor=\\\"7\\\" dataSetMonitorProcessor=\\\"4\\\" renderProcessor=\\\"0\\\" updateProcessor=\\\"2\\\" fileStreamProcessor=\\\"5\\\" />
	</cpu>
	<audio_card>
		<audio mixing=\\\"rapture3D\\\" />
	</audio_card>
	<graphics_card>
		<directx forcedx10=\\\"false\\\" />
		<eyefinity force=\\\"\\\" osd=\\\"\\\" />
		<stereo enabled=\\\"false\\\" separation=\\\"0.015\\\" convergence=\\\"1.5\\\" />
		<advanced forward=\\\"true\\\" global_illumination=\\\"true\\\" />
		<resolution width=\\\"\$1\\\" height=\\\"\$2\\\" aspect=\\\"auto\\\" fullscreen=\\\"true\\\" vsync=\\\"0\\\" multisampling=\\\"\$4\\\">
			<refreshRate rate=\\\"59\\\" />
		</resolution>
		<gamma level=\\\"1.0\\\" />
	</graphics_card>
	<shadows enabled=\\\"true\\\" size=\\\"2048\\\" maskQuality=\\\"2\\\" />
	<particles enabled=\\\"true\\\" wind=\\\"true\\\" dynamicRes=\\\"false\\\" />
	<crowd enabled=\\\"true\\\" detail=\\\"3\\\" />
	<cloth enabled=\\\"true\\\" tessellation=\\\"true\\\" />
	<postprocess quality=\\\"2\\\" />
	<groundcover mode=\\\"blended\\\" clutter=\\\"true\\\" />
	<objects lod=\\\"1.5\\\" maxlod=\\\"0\\\" />
	<trees lod=\\\"1.5\\\" maxlod=\\\"0\\\" />
	<vehicles characterQuality=\\\"2\\\" lodQuality=\\\"2\\\" />
	<envmap faces=\\\"6\\\" size=\\\"1024\\\" drawallobjects=\\\"true\\\" />
	<water update=\\\"true\\\" detail=\\\"2\\\" tessellation=\\\"true\\\" />
	<skidmarks enabled=\\\"true\\\" />
	<dynamic_ambient_occ enabled=\\\"true\\\" quality=\\\"2\\\" />
	<night_lighting volumes=\\\"true\\\" lights=\\\"0\\\" shadows=\\\"true\\\" />
	<physics environmentalDamage=\\\"true\\\" vehicleDamage=\\\"true\\\" />
	<input device_type=\\\"auto\\\" />
	<motion enabled=\\\"true\\\" ip=\\\"dbox\\\" port=\\\"20777\\\" delay=\\\"1\\\" extradata=\\\"0\\\" />
</hardware_settings_config>\" > \$DEBUG_REAL_HOME/.local/share/vpltd/dirt/GameDocuments/My\ Games/DiRT\ Showdown/hardwaresettings/hardware_settings_config.xml ;;
esac

HOME=\$DEBUG_REAL_HOME LD_LIBRARY_PATH=\$DEBUG_REAL_HOME/.steam/ubuntu12_32:\$DEBUG_REAL_HOME/.steam/ubuntu12_32/panorama:\$DEBUG_REAL_HOME/.steam/ubuntu12_32/steam-runtime/i386/lib/i386-linux-gnu:\$DEBUG_REAL_HOME/.steam/ubuntu12_32/steam-runtime/i386/lib:\$DEBUG_REAL_HOME/.steam/ubuntu12_32/steam-runtime/i386/usr/lib/i386-linux-gnu:\$DEBUG_REAL_HOME/.steam/ubuntu12_32/steam-runtime/i386/usr/lib:\$DEBUG_REAL_HOME/.steam/ubuntu12_32/steam-runtime/amd64/lib/x86_64-linux-gnu:\$DEBUG_REAL_HOME/.steam/ubuntu12_32/steam-runtime/amd64/lib:\$DEBUG_REAL_HOME/.steam/ubuntu12_32/steam-runtime/amd64/usr/lib/x86_64-linux-gnu:\$DEBUG_REAL_HOME/.steam/ubuntu12_32/steam-runtime/amd64/usr/lib::/usr/lib32:\$DEBUG_REAL_HOME/.steam/ubuntu12_32:\$DEBUG_REAL_HOME/.steam/ubuntu12_64:\$DEBUG_REAL_HOME/.steam/steam/steamapps/common/DiRT\ Showdown:\$DEBUG_REAL_HOME/.steam/steam/steamapps/common/DiRT\ Showdown/bin ./dirt -benchmark \$@
cat \$DEBUG_REAL_HOME/.local/share/vpltd/dirt/GameDocuments/My\ Games/DiRT\ Showdown/benchmarks/*.xml | sed \"s/\\\"/ /g\" > \$LOG_FILE" > dirt-showdown
chmod +x dirt-showdown
