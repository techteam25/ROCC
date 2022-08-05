<?php
    session_start();

    if (!isset($_SESSION['email'])) {
        header("Location: login.php");
    }

    //if ($_SESSION['admin'] === false){
        //header("Location: index.php");
    //}

    require_once('API/utils/Model.php');

    //submit consultant-project assignment to database
    $alertMsg = "";
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        error_log(json_encode($_POST));

        if (array_key_exists('assignmentConsultantId', $_POST) && array_key_exists('assignmentProjectId', $_POST)) {

            $assignmentConsultantId = trim($_POST['assignmentConsultantId']);
            $assignmentProjectId = trim($_POST['assignmentProjectId']);
                
            $conn = GetDatabaseConnection();
            $sql = "INSERT IGNORE INTO Assigned(ConsultantId, ProjectId) VALUES (?, ?)";
            $stmt = PrepareAndExecute($conn, $sql, array($assignmentConsultantId, $assignmentProjectId));
        }

        if (array_key_exists('removalConsultantId', $_POST) && array_key_exists('removalProjectId', $_POST)) {
            $removalConsultantId = trim($_POST['removalConsultantId']);
            $removalProjectId = trim($_POST['removalProjectId']);
            $conn = GetDatabaseConnection();
            $sql = "DELETE FROM Assigned WHERE ConsultantId = ? AND ProjectId = ?";
            $stmt = PrepareAndExecute($conn, $sql, array($removalConsultantId, $removalProjectId));
        }

        if (array_key_exists('passwordConsultantId', $_POST) && array_key_exists('Password', $_POST)) {
            $passwordConsultantId = trim($_POST['passwordConsultantId']);
            $Password = password_hash($_POST['Password'], PASSWORD_DEFAULT);
            $conn = GetDatabaseConnection();
            $sql = "UPDATE Consultants SET password = ? WHERE Consultants.id = ?";
            $stmt = PrepareAndExecute($conn, $sql, array($Password, $passwordConsultantId));
	    $alertMsg = "Password change successful";
        }

        if (array_key_exists('name', $_POST) &&
            array_key_exists('language', $_POST) &&
            array_key_exists('phone', $_POST) &&
            array_key_exists('email', $_POST) &&
            array_key_exists('password', $_POST)) {

            $name = trim($_POST['name']);
            $language = strtolower(trim($_POST['language']));
            $phone = trim($_POST['phone']);
            $email = trim($_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $isAdmin = array_key_exists('isAdmin', $_POST) ? true : false;

            if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
                $conn = GetDatabaseConnection();
                $stmt = PrepareAndExecute($conn, 'INSERT INTO Consultants (name, language, phone, email, password, isAdmin) VALUES (?,?,?,?,?,?)', 
                    array($name, $language, $phone, $email, $password, $isAdmin));
            } else {
                $isSuccessful = false;
                $error = "Email is not a valid email";
            }
        } else {
            $isSuccessful = false;
            $error = 'Request does not contain name, language, phone, email, password, and isAdmin fields.';
        }
    }	
    $conn = GetDatabaseConnection();
        //get consultantId from email
    $sql = "SELECT id FROM Consultants WHERE email = ?";
    $stmt = PrepareAndExecute($conn, $sql, array($_SESSION['email']));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $consultantId = $row['id'];
?>

<!doctype html>
<html lang = "en-US">

<head>
    <meta charset="utf-8">
    <meta https-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="favicon.png">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script src="admin.js"></script>
    <link rel = "stylesheet" href = "css/styles.css" type = "text/css"/>
    <link href="https://fonts.googleapis.com/css?family=Montserrat|Abel|Raleway|Roboto|Quicksand|Lora|PT+Serif|Sulphur+Point&display=swap" rel="stylesheet">
</head>


<body>
<?php
    if (!empty($alertMsg)) {
?>
    <script>alert('<?php echo $alertMsg; ?>')</script>
<?php
    }
?>
<div class ="container">
    
	<div class="flex-header">
		<div class ="header-top">
			<div class="story-info">
				<img id ="logo" src='images/SP.png' width="60" height="60">
				<h1>ROCC for SPadv</h1>
			</div>

			<div class ="header-menu">
				<a href="index.php">Dashboard</a>
				<a href="logout.php">Logout</a>
			</div>
		</div>
	</div>

    <div class = "flex-content">

    <div class="banner">Change Password</div>
        <table class="assign-table1">
            <tr>
                <th>Consultant</th>
                <th>New Password</th>
                <th>Change</th>
            </tr>
            <form class='assignment-form' method='POST'>
                <tr>
                    <td>
                        <select name='passwordConsultantId'>
                            <?php 
                                $conn = GetDatabaseConnection();
                                $stmt = PrepareAndExecute($conn, "SELECT DISTINCT name, id FROM Consultants", array());
				while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                                    if ($_SESSION['admin'] == true || $consultantId == $row['id']){
			    ?>
                                    <option type='consultantId' value='<?=$row['id']?>'><?=$row['name']?></option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                    </td>
                    <td>
        		<input type="password" name="Password" placeholder="password"></input>
                    </td>
                    <td>
                        <input class='consultants-button' type='submit' value='Change'>
                    </td>
                </tr>
            </form>
        </table>

<?php
  if ($_SESSION['admin'] == true)
  {
?>
    <div class="banner">Assign Consultants</div>
        <table class="assign-table1">
            <tr>
                <th>Consultant</th>
                <th>Project</th>
                <th>Assign</th>
            </tr>
            <form class='assignment-form' method='POST'>
                <tr>
                    <td>
                        <select name='assignmentConsultantId'>
                            <?php 
                                $conn = GetDatabaseConnection();
                                $stmt = PrepareAndExecute($conn, "SELECT DISTINCT name, id FROM Consultants", array());
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){ ?>
                                    <option type='consultantId' value='<?=$row['id']?>'><?=$row['name']?></option>
                            <?php } ?>
                        </select>
                    </td>
                    <td>
                        <select name='assignmentProjectId'>
                            <?php
                                $stmtInner = PrepareAndExecute($conn, "SELECT DISTINCT language, id FROM Projects", array());
                                while ($rowInner = $stmtInner->fetch(PDO::FETCH_ASSOC)){ ?>
                                    <option value='<?=$rowInner['id']?>'><?=$rowInner['language']?></option>
                            <?php } ?>
                        </select>
                    </td>

                    <td>
                        <input class='consultants-button' type='submit' value='Assign'>
                    </td>
                </tr>
            </form>
        </table>


    <div class="banner">Remove Assignments</div>
    <input type="text" id="searchForName" onkeyup="SearchName()" placeholder="Search by name...">
    <div class="scroll_assign">
        <table class="assign-table" id="assign-table">

            <tr>
                <th>Consultant</th>
                <th>Project</th>
                <th>Remove</th>
            </tr>
            <?php    
                $conn = GetDatabaseConnection();
                $sql = "SELECT name, Projects.language, Assigned.ConsultantId, Assigned.ProjectId FROM Projects, Consultants, Assigned WHERE Assigned.ConsultantId=Consultants.id && Assigned.ProjectId=Projects.id";
                $stmt = PrepareAndExecute($conn, $sql, array());
        
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

                    $consultantId = $row['ConsultantId'];
                    $projectId = $row['ProjectId'];
                    $name = $row['name'];
                    $lan = $row['language']; ?>
 
                    <tr>
                        <td><?=$name?></td>
                        <td><?=$lan?></td>
                        <td>
                            <form action="admin.php"
                                  method="post"
                                  onsubmit="return confirm('Are you sure you want to remove this assignment?')">
                                <input type="hidden" name="removalConsultantId" value="<?=$consultantId?>">
                                <input type="hidden" name="removalProjectId" value="<?=$projectId?>">
                                <button class="remove-consultant" name="submit">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>


        </table>
    </div>


    <div class="add">
    <div class="banner">Add New Consultant</div>
    <form action="API/AddConsultant.php" class="login-form" method="POST">
        <input type="text" name="Name" placeholder="name"></input>
        <input type="text" name="Language" placeholder="language"></input>
        <input type="text" name="Phone" placeholder="phone"></input>
        <input type="text" name="Email" placeholder="email"></input>
        <input type="password" name="Password" placeholder="password"></input>
        <div>
            Is Admin<input type="checkbox" id="isAdminCheckbox" name="IsAdmin"></input>
            <label for="isAdminCheckbox"></label>
        </div>
        <input type="submit" value="Add"></input>
    </form>
    </div>


    <div class="current">
    <div class="banner">Remove Consultants</div>
    <input type="text" id="searchForLan" onkeyup="SearchLan()" placeholder="Search by language...">
    <div class="scroll_assign">
    <table class="users-table" id="users-table">
        <tr>
            <th>Name</th>
            <th>Language</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Is Admin</th>
            <th>Remove</th>
        </tr>
        
      <?php

        require_once('API/utils/Model.php');

        $conn = GetDatabaseConnection();
        $stmt = PrepareAndExecute($conn, 'select name, language, phone, email, password, isAdmin from Consultants', array());

        while (($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            $isAdmin = $row['isAdmin'] === '1' ? 'yes' : 'no';
            $email = $row['email'];
            $name = $row['name'];
            $language = $row['language'];
            $phone = $row['phone'];
            
            
            
            echo "<tr>";
                echo "<td>$name</td>";
                echo "<td>$language</td>";
                echo "<td>$phone</td>";
                echo "<td>$email</td>";
                echo "<td>$isAdmin</td>";
                echo sprintf("<td><button class=\"remove-consultant\" onclick=\"remove('$email')\">Remove</button></td>");
            echo "</tr>";
        }

    ?>
    </table>
    </div>


    </div>
<?php
  }
?>
    </div>
</div>

</body>
</html> 
