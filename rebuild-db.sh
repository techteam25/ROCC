if [ "$#" -ne 2 ]; then
  echo Must use 2 arguments, your username and the database name
else
  echo Destroying and re-creating database
  cat docs/new-schema.sql docs/seed.sql | mysql -u $1 -p $2
  echo Deleting templates and project files
  sudo rm -rf Files
  echo Copying templates and project files from 'seed/' directory
  cp -r seed Files
  echo Setting proper permissions on Files/
  find Files -type f -exec chmod 644 -- {} +
  find Files -type d -exec chmod 777 -- {} +
fi
