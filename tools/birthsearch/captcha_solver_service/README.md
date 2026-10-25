# Math Captcha Solver Service

Python microservice using Hugging Face Transformers (TrOCR) + OpenCV to solve math captchas like those used by BDRIS.

## Setup

### 1. Install Python Dependencies

```bash
cd captcha_solver_service
pip install -r requirements.txt
```

First run will download the `microsoft/trocr-large-printed` model (~1.5GB) from Hugging Face.

### 2. Start the Service

```bash
# Windows
start_service.bat

# Or manually
python main.py
```

Service runs on `http://localhost:8001`

### 3. Verify Installation

```bash
curl http://localhost:8001/health
# {"status":"healthy","service":"math-captcha-solver"}
```

## API Endpoints

### Health Check
```
GET /health
```

### Solve Captcha
```
POST /solve
Content-Type: application/json

{
  "image_base64": "base64_encoded_image_data",
  "mime_type": "image/png"
}
```

Response:
```json
{
  "ok": true,
  "answer": "48"
}
```

Or on error:
```json
{
  "ok": false,
  "error": "Could not solve captcha"
}
```

## Integration with PHP

The PHP client (`includes/CaptchaSolverClient.php`) connects to this service.

Environment variable to configure service URL:
```
CAPTCHA_SOLVER_URL=http://localhost:8001
```

## How It Works

1. **Image Preprocessing** (OpenCV): Crops specific regions for left/right numbers
2. **OCR** (TrOCR): Uses `microsoft/trocr-large-printed` model for text recognition
3. **Math Logic**: Parses recognized numbers and computes the result

The solver is specifically designed for math captchas in format: `XX + Y = ?`

## Requirements

- Python 3.8+
- 4GB+ RAM (for model loading)
- Internet access on first run (to download model)

## Troubleshooting

- **Model download fails**: Check internet connection, try `HF_ENDPOINT=https://hf-mirror.com` for China
- **Out of memory**: Use smaller model or enable CPU offloading
- **Slow inference**: First request loads model into memory; subsequent requests are faster