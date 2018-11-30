#!/bin/bash

action='deploy'
case $2 in
    'update')
        action="-s update_vendors=true deploy";
        ;;

    'rollback')
        action="deploy:rollback";
        ;;
esac

case $1 in
    'master')
        cap HOSTS="ciyo-6" $action
        ;;

    'api')
	    cap -f Capfile2 HOSTS="ciyo-5" $action
	    cap -f Capfile4 HOSTS="ciyo-7" $action
	    cap -f Capfile3 HOSTS="ciyo-8,ciyo-9" $action
        ;;

    'servicio')
	    cap -f Capfile4 HOSTS="ciyo-7" $action
        ;;

    'rel')
	    cap -f Capfile3 HOSTS="ciyo-8" $action
        ;;

    'new')

	    cap HOSTS="ciyo-9" "deploy:setup"
	    cap -f Capfile4 HOSTS="ciyo-9" $action
        ;;

    'all')
	    cap -f Capfile2 HOSTS="ciyo-5" $action
	    cap -f Capfile4 HOSTS="ciyo-7" $action
	    cap -f Capfile3 HOSTS="ciyo-8,ciyo-9" $action
	    cap HOSTS="ciyo-6" $action
        ;;

     * )
        echo "Missing Host Type(master or api or servicio or rel)"
        echo "Example:"
        echo "./deploy-ciyo.sh [master|api|servicio|rel] [nil|update|rollback]"
        exit 2
        ;;
esac