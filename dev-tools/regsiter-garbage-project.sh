if [ "$#" -ne 2 ]; then
  echo Must use 2 arguments, server url prefix and the newly register phone id
  echo The server url prefix could be localhost:3030, or it could be much longer.
else
  a="Key=fake-key"
  a="$a&PhoneId=$2"
  a="$a&TranslatorEmail=translator@sil.com"
  a="$a&TranslatorPhone=1234567890"
  a="$a&TranslatorLanguage=English"
  a="$a&ProjectEthnoCode=ABC"
  a="$a&ProjectLanguage=Example Language"
  a="$a&ProjectCountry=Example Country"
  a="$a&ProjectMajorityLanguage=English"
  a="$a&ConsultantEmail=consultant@sil.org"
  a="$a&TrainerEmail=trainer@sil.org"
  curl -X POST --data "$a" "$1/API/RegisterPhone.php"
fi
