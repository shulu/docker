#!/bin/bash

update=$2

if [[ $update = "update" ]];
then
	update="-s update_vendors=true";
else
	update="";
fi;

if [[ $1 = "master" ]];
then
	cap HOSTS="rekall-1" $update deploy
# elif [[ $1 = "api" ]];
# then
	# cap -f Capfile2 HOSTS="ciyo-5,ciyo-7,ciyo-8" $update deploy
# elif [[ $1 = "servicio" ]]
# then
	# cap -f Capfile2 HOSTS="ciyo-7" $update deploy
else
	echo "Missing Host Type(master or api or servicio)"
	echo "Example:"
	echo "deploy-ciyo [master|api|servicio] [update]"
	exit 2
fi;
