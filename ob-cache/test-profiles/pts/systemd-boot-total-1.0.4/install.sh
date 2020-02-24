#!/bin/sh

SA=`which systemd-analyze`
echo $? > ~/install-exit-status

cat > systemd-boot-total << EOT
#!/bin/sh

OUTPUT="\$(${SA} | sed 's/[+|=]/\n/g'|\
sed 's/Startup finished in//'|\
sed 's/^ //'|\
sed 's/\(.*\)\ (\(.*\))/\2|\1/'|\
awk -F "|" '{if (NF == 1)print "total|"\$1;else print\$1"|"\$2}'|\
awk -F "|" '{\
    len=split(\$2,n," ");\
    total=0;\
    for(i=1;i<=len;i++)\
    {\
        l=length(n[i]);\
        if(n[i] ~ /min$/)\
            total+=substr(n[i],0,l-3)*60000;\
        else if(n[i] ~ /[0-9]s$/)\
            total+=substr(n[i],0,l-1)*1000;\
        else\
            total+=substr(n[i],0,l-2)\
    }\
    print \$1,total;\
}'| grep \$@ | cut -d' ' -f2)"

echo \$OUTPUT > \$LOG_FILE 2>&1

EOT

chmod +x systemd-boot-total
