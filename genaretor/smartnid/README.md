# Smart NID Card Generator

A simple web application to generate customized National ID (NID) cards for Bangladesh.

## Features

- Create personalized NID cards with custom information
- Barcode generation based on input data
- Print-ready output
- Supports Bangla text

## How to Use

1. Navigate to the index.php page in your browser
2. Fill out all the required information in the form:
   - Name (in Bangla and English)
   - Father's name
   - Mother's name
   - Date of birth
   - NID number
   - Address information
   - Blood group
   - Place of birth
   - PIN (for barcode generation)
3. Click the "Generate NID Card" button
4. The system will display your custom NID card
5. Use your browser's print functionality to save or print the card

## Requirements

- PHP-enabled web server (like Apache)
- Modern web browser with JavaScript enabled

## Files

- `index.php` - Contains the input form for NID card details
- `output.php` - Generates the NID card with the provided information
- `photos/` - Contains image assets for the NID card
- `fonts/` - Contains fonts used for displaying text on the NID card

## Notes

- This application is for demonstration purposes only
- The barcode is generated using the PDF417 JavaScript library
- The design mimics a Bangladesh National ID card 