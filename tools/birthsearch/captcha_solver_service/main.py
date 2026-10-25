from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Optional
import uvicorn
from solver_optimized import solve_captcha_from_base64

app = FastAPI(title="Math Captcha Solver Service", version="1.0.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

class CaptchaRequest(BaseModel):
    image_base64: str
    mime_type: Optional[str] = "image/png"

class CaptchaResponse(BaseModel):
    ok: bool
    answer: Optional[str] = None
    error: Optional[str] = None

@app.get("/health")
async def health_check():
    return {"status": "healthy", "service": "math-captcha-solver"}

@app.post("/solve", response_model=CaptchaResponse)
async def solve_captcha(request: CaptchaRequest):
    result = solve_captcha_from_base64(request.image_base64)
    return CaptchaResponse(**result)

if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=8001)