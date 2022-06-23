if [ "$#" -ne 2 ]; then
  echo Must use 2 arguments, your username and the database name
else
  echo Destroying and re-creating database
  cat docs/new-schema.sql docs/seed.sql | mysql -u $1 -p $2
  echo Deleting project files
  sudo rm -rf Files/Projects
  echo Setting proper permissions on Files/
  find Files -type f -exec chmod 644 -- {} +
  find Files -type d -exec chmod 777 -- {} +
fi
