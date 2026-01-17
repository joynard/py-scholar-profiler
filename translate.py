import sys
from googletrans import Translator

sys.stdout.reconfigure(encoding='utf-8') #type: ignore

try:
    # Cek apakah ada argumen input
    if len(sys.argv) > 1:
        text = sys.argv[1]
        
        translator = Translator()
        # Tambahkan handling error koneksi translator
        result = translator.translate(text, src="en", dest="id")
        
        if result and result.text: #type: ignore
            print(result.text)     #type: ignore
        else:
            # Fallback jika hasil translate kosong, print aslinya
            print(text)
    else:
        print("") # Print kosong jika tidak ada input

except Exception as e:
    # Jika error, print teks aslinya saja (fail-safe)
    # Kita hindari print error traceback agar tidak merusak JSON/Array PHP
    if len(sys.argv) > 1:
        print(sys.argv[1])
    else:
        print("")
