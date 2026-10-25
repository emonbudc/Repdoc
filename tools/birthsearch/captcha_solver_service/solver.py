import cv2
import numpy as np
import base64
import tempfile
import os
from transformers import pipeline
from PIL import Image
import io

pipe = pipeline("image-to-text", model="microsoft/trocr-large-printed")

class MathCaptchaSolver:
    def __init__(self, image_array):
        self.image = image_array
        self.kernel = np.ones((2, 2), np.uint8)

    def enhance_legibility(self, cropped_image):
        gray = cv2.cvtColor(cropped_image, cv2.COLOR_BGR2GRAY)
        return gray

    def math_operation(self, left_number, right_number):
        if right_number.isdigit():
            return eval(f"{left_number} + {right_number}")
        return None

    def resolve(self, left_image, right_image, left_image_twice, right_image_twice):
        left_number = pipe(left_image_twice)[0]['generated_text']
        if left_number.isdigit():
            left_number = int(left_number)
            if left_number < 10 or left_number is None or left_number == "":
                left_number = pipe(left_image)[0]['generated_text']
                right_number = pipe(right_image_twice)[0]['generated_text']
                if right_number.isdigit() and int(right_number) > 10:
                    return self.math_operation(left_number, right_number)
                else:
                    right_number = pipe(right_image)[0]['generated_text']
                    return self.math_operation(left_number, right_number)
            elif left_number >= 10:
                right_number = pipe(right_image_twice)[0]['generated_text']
                return self.math_operation(left_number, right_number)
        else:
            left_number = pipe(left_image)[0]['generated_text']
            if left_number.isdigit():
                right_number = pipe(right_image)[0]['generated_text']
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

        return self.resolve(
            left_enhanced, 
            right_enhanced, 
            left_enhanced_for_twice_number, 
            right_enhanced_for_twice_number
        )


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