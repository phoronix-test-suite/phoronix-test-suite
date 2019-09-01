#!/bin/sh

HOME=$DEBUG_REAL_HOME steam steam://install/337000

unzip -o deusex-preferences-2.zip

cat>drop-warning.patch<<EOT
diff -ur /tmp/old/prefs-1080p-high.xml ./prefs-1080p-high.xml
--- /tmp/old/prefs-1080p-high.xml	2017-05-22 17:13:28.000000000 +0200
+++ ./prefs-1080p-high.xml	2018-12-05 17:29:59.247140826 +0100
@@ -79,6 +79,9 @@
                         <value name="ShowAssertAlerts" type="integer">0</value>
                         <value name="SoftwareUpdatedAskedUser" type="integer">1</value>
                         <value name="SoftwareUpdatedCanCheck" type="integer">1</value>
+                        <key name="SpecificationAlerts">
+                            <value name="LnxDistributionUnsupported" type="integer">1</value>
+                        </key>
                     </key>
                 </key>
             </key>
diff -ur /tmp/old/prefs-1080p-low.xml ./prefs-1080p-low.xml
--- /tmp/old/prefs-1080p-low.xml	2017-05-22 17:17:12.000000000 +0200
+++ ./prefs-1080p-low.xml	2018-12-05 17:30:02.837140810 +0100
@@ -79,6 +79,9 @@
                         <value name="ShowAssertAlerts" type="integer">0</value>
                         <value name="SoftwareUpdatedAskedUser" type="integer">1</value>
                         <value name="SoftwareUpdatedCanCheck" type="integer">1</value>
+                        <key name="SpecificationAlerts">
+                            <value name="LnxDistributionUnsupported" type="integer">1</value>
+                        </key>
                     </key>
                 </key>
             </key>
diff -ur /tmp/old/prefs-1080p-ultra.xml ./prefs-1080p-ultra.xml
--- /tmp/old/prefs-1080p-ultra.xml	2017-05-22 17:16:22.000000000 +0200
+++ ./prefs-1080p-ultra.xml	2018-12-05 17:29:51.097140861 +0100
@@ -79,6 +79,9 @@
                         <value name="ShowAssertAlerts" type="integer">0</value>
                         <value name="SoftwareUpdatedAskedUser" type="integer">1</value>
                         <value name="SoftwareUpdatedCanCheck" type="integer">1</value>
+                        <key name="SpecificationAlerts">
+                            <value name="LnxDistributionUnsupported" type="integer">1</value>
+                        </key>
                     </key>
                 </key>
             </key>
diff -ur /tmp/old/prefs-1440p-high.xml ./prefs-1440p-high.xml
--- /tmp/old/prefs-1440p-high.xml	2017-05-22 17:19:50.000000000 +0200
+++ ./prefs-1440p-high.xml	2018-12-05 17:30:08.977140784 +0100
@@ -79,6 +79,9 @@
                         <value name="ShowAssertAlerts" type="integer">0</value>
                         <value name="SoftwareUpdatedAskedUser" type="integer">1</value>
                         <value name="SoftwareUpdatedCanCheck" type="integer">1</value>
+                        <key name="SpecificationAlerts">
+                            <value name="LnxDistributionUnsupported" type="integer">1</value>
+                        </key>
                     </key>
                 </key>
             </key>
diff -ur /tmp/old/prefs-4k-low.xml ./prefs-4k-low.xml
--- /tmp/old/prefs-4k-low.xml	2017-05-22 17:20:47.000000000 +0200
+++ ./prefs-4k-low.xml	2018-12-05 17:30:12.827140768 +0100
@@ -79,6 +79,9 @@
                         <value name="ShowAssertAlerts" type="integer">0</value>
                         <value name="SoftwareUpdatedAskedUser" type="integer">1</value>
                         <value name="SoftwareUpdatedCanCheck" type="integer">1</value>
+                        <key name="SpecificationAlerts">
+                            <value name="LnxDistributionUnsupported" type="integer">1</value>
+                        </key>
                     </key>
                 </key>
             </key>
EOT
patch -p0 < drop-warning.patch

echo "#!/bin/bash
killall -9 DeusExMD
rm -f \$DEBUG_REAL_HOME/.local/share/feral-interactive/Deus\ Ex\ Mankind\ Divided/VFS/User/AppData/Roaming/Deus\ Ex\ -\ Mankind\ Divided/*.txt
. steam-env-vars.sh
cat \$1.xml > \$DEBUG_REAL_HOME/.local/share/feral-interactive/Deus\ Ex\ Mankind\ Divided/preferences
cd \$DEBUG_REAL_HOME/.steam/steam/steamapps/common/Deus\ Ex\ Mankind\ Divided/bin
sleep 4
./DeusExMD -benchmark
cat \$DEBUG_REAL_HOME/.local/share/feral-interactive/Deus\ Ex\ Mankind\ Divided/VFS/User/AppData/Roaming/Deus\ Ex\ -\ Mankind\ Divided/*.txt > \$LOG_FILE" > deus-exmd
chmod +x deus-exmd
