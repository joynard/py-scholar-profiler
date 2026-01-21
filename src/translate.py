import sys
from googletrans import Translator

# convert output ke UTF-8
sys.stdout.reconfigure(encoding='utf-8')

try:
    if len(sys.argv) > 1:
        text = sys.argv[1]
        
        # default target bahasa indo
        target_lang = "id"
        if len(sys.argv) > 2:
            target_lang = sys.argv[2] 

        translator = Translator()
        
        # auto detect source -> translate ke target
        result = translator.translate(text, dest=target_lang)
        
        if result and result.text:
            print(result.text) 
        else:
            print(text)
    else:
        print("")

except Exception as e:
    # Print text asli jika error, supaya PHP tetap dapat output
    if len(sys.argv) > 1:
        print(sys.argv[1])
    else:
        print("")