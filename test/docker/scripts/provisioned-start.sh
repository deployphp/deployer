#!/bin/bash
echo "Starting provisioned environment..."
service ssh start

while :
do
	echo "Provisioned environment is running"
	sleep 10
done
