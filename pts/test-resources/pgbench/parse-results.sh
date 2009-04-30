#!/bin/sh

awk '/excluding connections establishing/ { print $3}' <$LOG_FILE
