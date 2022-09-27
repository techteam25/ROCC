<?php 
$cmd = 'curl -X POST --header "Authorization: key=AAAAU8MDzIQ:APA91bEm-Xskg66XnJXnUe5MvFs60eHiq-14eCiZ3n7atak-mbYcz7idkWQ7OB1IDDsQV0TPWhixEX_StNGCZUemP805qd4vzKndmvuAMcvfmr35gZZTzN3qVeXsBnmB3lGHZB-9QdVT "     --Header "Content-Type: application/json"     https://fcm.googleapis.com/fcm/send -d "{\"to\":\"fxO-OrFFSjyeQwdEEGI_Zj:APA91bE2scSyDaphdTqTWy_P4em04kiLWfyAGAwhkBgBdzORQOoOIhiZVXi1POZeZOCih44fgpFVukZ8qgrdFyU1rXtzHgRjQSHNjQRP7A-cQAWjJ0YmIvkQzM9FdI6bz2VRbmd_Yba6\",\"notification\":{\"title\":\"Story Producer Adv\",\"body\":\"Story - - 5% audio files approved.\"}}"';

$result = shell_exec($cmd);

echo "!" . $result . "!";


?>
