@echo off

FOR /F %%i IN ('docker ps -a -q') DO (
	echo ==========================
	echo now clear container %%i
	Call docker stop %%i
	Call docker rm %%i
	echo %%i clear done
	echo ==========================
)
