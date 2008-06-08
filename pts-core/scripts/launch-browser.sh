#!/bin/sh

if [ pts`which epiphany` != pts ]
then
	epiphany "$1"
elif [ pts`which firefox` != pts ]
then
	firefox "$1"
elif [ pts`which mozilla` != pts ]
then
	mozilla "$1"
else
	"URL: $1"
fi

