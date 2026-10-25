#!/usr/bin/env python3
"""
Memory-optimized Math Captcha Solver
Uses smaller TrOCR model and CPU offloading to prevent OOM kills.
"""

import cv2
import numpy as np
import base64
import os
import io
import gc
from PIL import Image

# Set environment variables for memory optimization BEFORE importing transformers
os.environ["PYTORCH_CUDA_ALLOC_CONF"] = "max_split_size_mb:128"
os.environ["TOKENIZERS_PARALLELISM"] = "false"

try:
    import torch
    # Force CPU mode to avoid GPU memory issues
    torch.set_num_threads(4)
    DEVICE = "cpu"
    print(f"🔧 Using device: {DEVICE}, threads: {torch.get_num_threads()}")
except ImportError:
    DEVICE = "cpu"
    print("⚠️  PyTorch not available, using CPU")

from transformers import pipeline, TrOCRProcessor, VisionEncoderDecoderModel

# Use smaller model: trocr-small-printed (~300MB) instead of large (~1.5GB)
MODEL_NAME = "microsoft/trocr-small-printed"

print(f"📥 Loading model: {MODEL_NAME} ...")
processor = TrOCRProcessor.from_pretrained(MODEL_NAME)
model = VisionEncoderDecoderModel.from_pretrained(MODEL_NAME)
model.to(DEVICE)
model.eval()

# Disable gradient computation for inference
for param in model.parameters():
    param.requires_grad = False

print(f"✅ Model loaded on {DEVICE}")

class MathCaptchaSolver:
    def __init__(self, image_array):
        self.image = image_array
        self.kernel = np.ones((2, 2), np.uint8)

    def enhance_legibility(self, cropped_image):
        gray = cv2.cvtColor(cropped_image, cv2.COLOR_BGR2GRAY)
        # Apply threshold for better OCR
        _, thresh = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
        return thresh

    def math_operation(self, left_number, right_number):
        if right_number.isdigit():
            return eval(f"{left_number} + {right_number}")
        return None

    def ocr_image(self, image):
        """Run OCR on a single image region."""
        # Convert to PIL Image
        if len(image.shape) == 2:
            # Grayscale to RGB
            pil_image = Image.fromarray(image).convert("RGB")
        else:
            pil_image = Image.fromarray(cv2.cvtColor(image, cv2.COLOR_BGR2RGB))
        
        # Process with TrOCR
        pixel_values = processor(pil_image, return_tensors="pt").pixel_values.to(DEVICE)
        
        with torch.no_grad():
            generated_ids = model.generate(pixel_values, max_length=16)
        
        text = processor.batch_decode(generated_ids, skip_special_tokens=True)[0]
        return text.strip()

    def resolve(self, left_image, right_image, left_image_twice, right_image_twice):
        left_number = self.ocr_image(left_image_twice)
        if left_number.isdigit():
            left_number = int(left_number)
            if left_number < 10 or left_number is None or left_number == "":
                left_number = self.ocr_image(left_image)
                right_number = self.ocr_image(right_image_twice)
                if right_number.isdigit() and int(right_number) > 10:
                    return self.math_operation(left_number, right_number)
                else:
                    right_number = self.ocr_image(right_image)
                    return self.math_operation(left_number, right_number)
            elif left_number >= 10:
                right_number = self.ocr_image(right_image_twice)
                return self.math_operation(left_number, right_number)
        else:
            left_number = self.ocr_image(left_image)
            if left_number.isdigit():
                right_number = self.ocr_image(right_image)
                return self.math_operation(left_number, right_number)
        return None

    def solve_captcha(self):
        positions = {'left': 5, 'right_unit': 57, 'right_twice': 71}
        dimensions = {'width_twice': 31, 'width_unit': 19, 'height': 20}

        left_image_for_unit_number = self.image[7:30, positions['left']:positions['left']+dimensions['width_unit']]
        left_image_for_twice_number = self.image[7:30, positions['left']:positions['left']+dimensions['width_twice']]
        right_image_for_left_twice_number = self.image[7:30, positions['right_twice']:positions['right_twice']+dimensions['width_twice']]
        right_image_for_left_unit_number = self.image[7:30, positions['right_unit']:positions['right_unit']+dimensions['width_twice']]

        left_enhanced = self.enhance_legibility(left_image_for_unit_number)
        left_enhanced_for_twice_number = self.enhance_legibility(left_image_for_twice_number)
        right_enhanced = self.enhance_legibility(right_image_for_left_unit_number)
        right_enhanced_for_twice_number = self.enhance_legibility(right_image_for_left_twice_number)

        result = self.resolve(
            left_enhanced, 
            right_enhanced, 
            left_enhanced_for_twice_number, 
            right_enhanced_for_twice_number
        )
        
        # Clean up
        gc.collect()
        return result


def solve_captcha_from_base64(base64_string: str) -> dict:
    """
    Solve math captcha from base64 encoded image.
    Returns: {"ok": True, "answer": "48"} or {"ok": False, "error": "..."}
    """
    try:
        if "," in base64_string:
            base64_string = base64_string.split(",")[1]
        
        image_data = base64.b64decode(base64_string)
        
        image = Image.open(io.BytesIO(image_data))
        image_array = cv2.cvtColor(np.array(image), cv2.COLOR_RGB2BGR)
        
        solver = MathCaptchaSolver(image_array)
        result = solver.solve_captcha()
        
        if result is not None:
            return {"ok": True, "answer": str(int(result))}
        else:
            return {"ok": False, "error": "Could not solve captcha"}
            
    except Exception as e:
        return {"ok": False, "error": str(e)}