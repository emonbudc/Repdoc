@echo off
echo Starting Math Captcha Solver Service...
echo.
echo Make sure you have installed dependencies:
echo   pip install -r requirements.txt
echo.
echo The service will be available at http://localhost:8001
echo Health check: http://localhost:8001/health
echo Solve endpoint: POST http://localhost:8001/solve
echo.
cd /d "%~dp0"
python main.py
pause