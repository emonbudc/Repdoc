#!/usr/bin/env python3
"""
Installation script for Math Captcha Solver Service
Installs dependencies and optionally starts the service.
"""

import subprocess
import sys
import os
import time

def run_command(cmd, description=""):
    """Run a command and return success status."""
    print(f"\n{'='*60}")
    print(f"📦 {description or cmd}")
    print(f"{'='*60}")
    try:
        result = subprocess.run(cmd, shell=True, check=True, capture_output=True, text=True)
        if result.stdout:
            print(result.stdout)
        return True
    except subprocess.CalledProcessError as e:
        print(f"❌ Error: {e}")
        if e.stdout:
            print(f"STDOUT: {e.stdout}")
        if e.stderr:
            print(f"STDERR: {e.stderr}")
        return False

def check_python():
    """Check Python version."""
    version = sys.version_info
    print(f"🐍 Python version: {version.major}.{version.minor}.{version.micro}")
    if version.major < 3 or (version.major == 3 and version.minor < 8):
        print("❌ Python 3.8+ required")
        return False
    print("✅ Python version OK")
    return True

def install_requirements():
    """Install Python requirements."""
    req_file = os.path.join(os.path.dirname(__file__), "requirements.txt")
    if not os.path.exists(req_file):
        print(f"❌ requirements.txt not found at {req_file}")
        return False
    
    # Upgrade pip first
    run_command(f"{sys.executable} -m pip install --upgrade pip", "Upgrading pip")
    
    # Install requirements
    return run_command(f"{sys.executable} -m pip install -r {req_file}", "Installing requirements")

def verify_install():
    """Verify key packages can be imported."""
    packages = [
        ("fastapi", "FastAPI"),
        ("uvicorn", "Uvicorn"),
        ("cv2", "OpenCV"),
        ("numpy", "NumPy"),
        ("PIL", "Pillow"),
        ("transformers", "Transformers"),
    ]
    
    print("\n🔍 Verifying imports...")
    all_ok = True
    for module, name in packages:
        try:
            __import__(module)
            print(f"  ✅ {name}")
        except ImportError as e:
            print(f"  ❌ {name}: {e}")
            all_ok = False
    
    return all_ok

def download_model():
    """Pre-download the TrOCR model."""
    print("\n📥 Pre-downloading TrOCR model (this may take a while)...")
    try:
        from transformers import TrOCRProcessor, VisionEncoderDecoderModel
        print("Loading model...")
        processor = TrOCRProcessor.from_pretrained("microsoft/trocr-small-printed")
        model = VisionEncoderDecoderModel.from_pretrained("microsoft/trocr-small-printed")
        print("✅ Model downloaded and cached")
        return True
    except Exception as e:
        print(f"❌ Model download failed: {e}")
        return False

def start_service():
    """Start the FastAPI service."""
    main_file = os.path.join(os.path.dirname(__file__), "main.py")
    print(f"\n🚀 Starting service on http://localhost:8001")
    print(f"   Press Ctrl+C to stop")
    print(f"{'='*60}\n")
    
    try:
        subprocess.run([sys.executable, main_file], check=True)
    except KeyboardInterrupt:
        print("\n\n🛑 Service stopped by user")
    except subprocess.CalledProcessError as e:
        print(f"\n❌ Service failed: {e}")

def main():
    print("🔧 Math Captcha Solver - Installation Script")
    print("=" * 60)
    
    # Change to script directory
    os.chdir(os.path.dirname(os.path.abspath(__file__)))
    
    # Check Python
    if not check_python():
        sys.exit(1)
    
    # Install requirements
    if not install_requirements():
        print("\n❌ Installation failed")
        sys.exit(1)
    
    # Verify
    if not verify_install():
        print("\n❌ Verification failed")
        sys.exit(1)
    
    # Download model
    print("\n📥 Downloading TrOCR model (~1.5GB, first time only)...")
    if not download_model():
        print("⚠️  Model download failed, will retry on first request")
    
    print("\n" + "="*60)
    print("✅ Installation complete!")
    print("="*60)
    
    # Ask to start service
    response = input("\n🚀 Start service now? (y/N): ").strip().lower()
    if response in ('y', 'yes'):
        start_service()
    else:
        print("\nTo start manually later:")
        print(f"  cd {os.path.dirname(__file__)}")
        print(f"  python main.py")
        print(f"\nService will run at: http://localhost:8001")
        print(f"Health check:      http://localhost:8001/health")

if __name__ == "__main__":
    main()