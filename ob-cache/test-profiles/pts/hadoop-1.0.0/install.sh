#!/bin/sh
if [ $OS_ARCH = "aarch64" ]
then
	tar -xf hadoop-3.3.6-aarch64.tar.gz
else
	tar -xf hadoop-3.3.6.tar.gz
fi
cd hadoop-3.3.6
ssh-keygen -t rsa -P '' -f ~/.ssh/id_rsa
cat ~/.ssh/id_rsa.pub >> ~/.ssh/authorized_keys
echo "
export JAVA_HOME=/usr" >> etc/hadoop/hadoop-env.sh
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<?xml-stylesheet type=\"text/xsl\" href=\"configuration.xsl\"?>

<configuration>
        <property>
                <name>dfs.datanode.data.dir</name>
                <value>file:$HOME/data</value>
        </property>
</configuration>" > etc/hadoop/hdfs-site.xml
cd ~
echo "#!/bin/bash
cd ~/hadoop-3.3.6/sbin
JAVA_HOME=/usr echo \"Y\" | ../bin/hadoop namenode -format
JAVA_HOME=/usr ./start-all.sh
sleep 2

cd ~/hadoop-3.3.6/bin
JAVA_HOME=/usr ./hadoop \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-stats

cd ~/hadoop-3.3.6/sbin
JAVA_HOME=/usr ./stop-all.sh
sleep 2
rm -rf ~/tmp
rm -rf ~/data" > hadoop
chmod +x hadoop
