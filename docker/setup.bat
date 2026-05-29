@echo off
REM ---------------------------------------------------------------------------
REM  Generate a self-signed SSL certificate for https://<PROJECT_NAME>/ on
REM  Windows. PROJECT_NAME is read from the repo's .env file (or .env.example
REM  as fallback, or finally the parent directory name).
REM  Requires OpenSSL (ships with Git for Windows, Laragon or standalone).
REM ---------------------------------------------------------------------------
setlocal ENABLEDELAYEDEXPANSION

set "SCRIPT_DIR=%~dp0"
set "ROOT_DIR=%SCRIPT_DIR%.."
set "SSL_DIR=%SCRIPT_DIR%apache\ssl"

REM --- Resolve PROJECT_NAME --------------------------------------------------
set "PROJECT_NAME="
call :readEnv "%ROOT_DIR%\.env"
if "!PROJECT_NAME!"=="" call :readEnv "%ROOT_DIR%\.env.example"
if "!PROJECT_NAME!"=="" (
    for %%I in ("%ROOT_DIR%") do set "PROJECT_NAME=%%~nxI"
    echo [info] PROJECT_NAME=!PROJECT_NAME! ^(fallback to folder name^)
)

if not exist "%SSL_DIR%" mkdir "%SSL_DIR%"

if exist "%SSL_DIR%\!PROJECT_NAME!.crt" if exist "%SSL_DIR%\!PROJECT_NAME!.key" (
    echo [ok] Certificate already exists at %SSL_DIR%\!PROJECT_NAME!.crt
    goto :eof
)

REM --- Locate openssl --------------------------------------------------------
set "OPENSSL="
where openssl >nul 2>nul && set "OPENSSL=openssl"
if "!OPENSSL!"=="" if exist "C:\Program Files\Git\usr\bin\openssl.exe" set "OPENSSL=C:\Program Files\Git\usr\bin\openssl.exe"
if "!OPENSSL!"=="" if exist "C:\laragon\bin\apache\httpd-2.4.54-win64-VS16\bin\openssl.exe" set "OPENSSL=C:\laragon\bin\apache\httpd-2.4.54-win64-VS16\bin\openssl.exe"
if "!OPENSSL!"=="" (
    echo [error] Could not find openssl.exe on PATH.
    echo         Install Git for Windows ^(includes openssl^) or run docker\setup.sh from Git Bash.
    exit /b 1
)

echo [info] Generating self-signed certificate for !PROJECT_NAME!...

set "CONF=%TEMP%\ssl_!PROJECT_NAME!.conf"
> "%CONF%" echo [req]
>>"%CONF%" echo default_bits = 2048
>>"%CONF%" echo prompt = no
>>"%CONF%" echo default_md = sha256
>>"%CONF%" echo distinguished_name = dn
>>"%CONF%" echo req_extensions = v3_req
>>"%CONF%" echo.
>>"%CONF%" echo [dn]
>>"%CONF%" echo CN=!PROJECT_NAME!
>>"%CONF%" echo O=Development
>>"%CONF%" echo C=RO
>>"%CONF%" echo.
>>"%CONF%" echo [v3_req]
>>"%CONF%" echo subjectAltName = @alt_names
>>"%CONF%" echo.
>>"%CONF%" echo [alt_names]
>>"%CONF%" echo DNS.1 = !PROJECT_NAME!
>>"%CONF%" echo DNS.2 = www.!PROJECT_NAME!
>>"%CONF%" echo DNS.3 = localhost
>>"%CONF%" echo IP.1  = 127.0.0.1

"!OPENSSL!" req -x509 -nodes -days 3650 -newkey rsa:2048 ^
    -config "%CONF%" ^
    -extensions v3_req ^
    -keyout "%SSL_DIR%\!PROJECT_NAME!.key" ^
    -out    "%SSL_DIR%\!PROJECT_NAME!.crt"

del /q "%CONF%"

echo.
echo [ok] Certificate generated:
echo      %SSL_DIR%\!PROJECT_NAME!.crt
echo      %SSL_DIR%\!PROJECT_NAME!.key
echo.
echo [next] Add this line to your hosts file ^(run Notepad as administrator^):
echo        127.0.0.1  !PROJECT_NAME!
echo        File: C:\Windows\System32\drivers\etc\hosts
goto :eof

REM ---------------------------------------------------------------------------
REM :readEnv  <envFile>
REM Reads PROJECT_NAME=... from the given .env-like file and stores it in
REM the PROJECT_NAME variable. No-op if the file does not exist or the
REM variable is not present.
REM ---------------------------------------------------------------------------
:readEnv
if not exist "%~1" exit /b 0
for /f "usebackq tokens=1,* delims==" %%A in ("%~1") do (
    set "key=%%A"
    set "value=%%B"
    if /I "!key!"=="PROJECT_NAME" (
        set "PROJECT_NAME=!value!"
        echo [info] PROJECT_NAME=!PROJECT_NAME! ^(from %~nx1^)
    )
)
exit /b 0
