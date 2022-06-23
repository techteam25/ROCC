echo Setting all file permissions to 644
find . -type f -exec chmod 644 -- {} +
echo Setting all directory permissions to 755
find . -type d -exec chmod 755 -- {} +
echo Setting all Files directory permissions to 777
find Files -type d -exec chmod 777 -- {} +
