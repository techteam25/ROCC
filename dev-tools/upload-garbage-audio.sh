if [ "$#" -ne 3 ]; then
  echo Must use 3 arguments, server url prefix, phone id and template title.
  echo The server url prefix could be localhost:3030, or it could be much longer.
  echo Phone ids may be listed with \`php dev-tools/list-project-ids.php\`.
  echo Template titles may be found in the "Files/Templates" directory.
else
  curl -X POST --data "Key=fake-key&PhoneId=$2&IsWholeStory=true&TemplateTitle=$3&Data=audio-data\"" "$1/API/UploadSlideBacktranslation.php"
fi
