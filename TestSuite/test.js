var testFunctions = [];
var ROOT = "https://storyproducer.eastus.cloudapp.azure.com/API/";

var email = "testadmin@example.com";
var password = "testPassword";
var key = "XUKYjBHCsD6OVla8dYAt298D9zkaKSqd";
var phoneId = "c90b982465e9fd2b";
var translatorEmail = "translator@example.com";
var translatorPhone = "6305551234";
var translatorLanguage = "English";
var ethnoCode = "esp";
var language = "Spanish";
var country = "Mexico";
var majorityLanguage = "English";
var trainerEmail = "trainer@example.com";
var templates = ["Good Dog", "Greedy Dog", "Hungry Dog",
                 "Big Dog", "Sad Dog"];


function ret(name, msg, result) {
    var resultString;
    var resultColor;
    switch (result) {
        case -1:
            resultString = "Fail";
            resultColor = "red";
            break;
        case  0:
            resultString = "Error";
            resultColor = "yellow";
            break;
        case  1:
            resultString = "Pass";
            resultColor = "green";
            break;
    }
    return {"title":name,"msg":msg,"result":resultString, "color":resultColor};
}

function makeAjax(ur, dt, tp) {
    returnValue = "";
    $.ajax({
        url: ROOT + ur,
        async: false,
        data: dt,
        method: tp,
        success: function(result) {
            returnValue = result;
        }
    });
    return returnValue;
}

function makeGet(url, data) {
    return makeAjax(url, data, "GET");
}

function makePost(url, data) {
    return makeAjax(url, data, "POST");
}

function WebAuthentication() {
    title = "Web: Login/out";
    msg = "Success!";

    email = "testadmin@example.com";
    password = "testPassword";
    invalidEmail = "testadmin@example.co";
    invalidPassword1 = "testpassword";
    invalidPassword2 = "testPasswordd";

    request = makePost("LoginRequest.php", {
        "Email":email,
        "Password":invalidPassword1
    });
    if (!request.Success) {
        msg = request.Error;
        result = 0;
        return ret(title, msg, result);
    }
    if (request.LoginSuccess) {
        msg = "Login worked with wrong password";
        result = -1;
        return ret(title, msg, result);
    }

    request = makePost("LoginRequest.php", {
        "Email":email,
        "Password":invalidPassword2
    });
    if (!request.Success) {
        msg = request.Error;
        result = 0;
        return ret(title, msg, result);
    }
    if (request.LoginSuccess) {
        msg = "Login worked with wrong password";
        result = -1;
        return ret(title, msg, result);
    }

    request = makePost("LoginRequest.php", {
        "Email":invalidEmail,
        "Password":password
    });
    if (!request.Success) {
        msg = request.Error;
        result = 0;
        return ret(title, msg, result);
    }
    if (request.LoginSuccess) {
        msg = "Login worked with wrong email";
        result = -1;
        return ret(title, msg, result);
    }

    request = makePost("LoginRequest.php", {
        "Email":email,
        "Password":password
    });
    if (!request.Success) {
        msg = request.Error;
        result = 0;
        return ret(title, msg, result);
    }
    if (!request.LoginSuccess) {
        msg = "Login did not work";
        result = -1;
        return ret(title, msg, result);
    }
    
    request = makePost("GetSessionEmail.php", "{}");
    if (!request.Success) {
        msg = request.Error;
        result = 0;
        return ret(title, msg, result);
    }
    if (request.Email != email) {
        msg = "Session email does not match current user";
        result = -1;
        return ret(title, msg, result);
    }

    request = makePost("Logout.php", "{}");
    if (!request.Success) {
        msg = request.Error;
        result = 0;
        return ret(title, msg, result);
    }

    request = makePost("GetSessionEmail.php", "{}");
    if (request.Success) {
        msg = "Logout did not work";
        result = -1;
        return ret(title, msg, result);
    }

    return ret(title, msg, 1);
}
testFunctions.push(WebAuthentication);

function WebChangePassword() {
    title = "Web: Change Password";
    msg = "Success!";
    var newPassword1 = "newPass1";
    var newPassword2 = "newpass2";

    makePost("LoginRequest.php", {
        "Email":email,
        "Password":password
    });

    request = makePost("ChangePassword.php", {
        "CurrentPassword":password,
        "NewPassword": newPassword1
    });
    if (!request.Success) {
        msg = request.Error;
        result = 0;
        return ret(title, msg, result);
    }
    request = makePost("LoginRequest.php", {
        "Email":email,
        "Password":newPassword1
    });
    if (!request.Success) {
        msg = request.Error;
        result = 0;
        return ret(title, msg, result);
    }
    if (!request.LoginSuccess) {
        msg = "Password change did not work";
        result = -1;
        return ret(title, msg, result);
    }

    request = makePost("ChangePassword.php", {
        "CurrentPassword":newPassword1,
        "NewPassword": newPassword2
    });
    if (!request.Success) {
        msg = request.Error;
        result = 0;
        return ret(title, msg, result);
    }
    request = makePost("LoginRequest.php", {
        "Email":email,
        "Password":newPassword2
    });
    if (!request.Success) {
        msg = request.Error;
        result = 0;
        return ret(title, msg, result);
    }
    if (!request.LoginSuccess) {
        msg = "Password change did not work";
        result = -1;
        return ret(title, msg, result);
    }

    request = makePost("ChangePassword.php", {
        "CurrentPassword":newPassword2,
        "NewPassword": password
    });
    if (!request.Success) {
        msg = request.Error;
        result = 0;
        return ret(title, msg, result);
    }
    request = makePost("LoginRequest.php", {
        "Email":email,
        "Password":password
    });
    if (!request.Success) {
        msg = request.Error;
        result = 0;
        return ret(title, msg, result);
    }
    if (!request.LoginSuccess) {
        msg = "Password change did not work";
        result = -1;
        return ret(title, msg, result);
    }

    return ret(title, msg, 1);
}
testFunctions.push(WebChangePassword);

function MobileRegisterPhone() {
    title = "Mobile: Phone Registration";
    msg = "Success!";
    var phoneRegistrationData = {
        "Key": key,
        "PhoneId": phoneId,
        "TranslatorEmail": translatorEmail,
        "TranslatorPhone": translatorPhone,
        "TranslatorLanguage": translatorLanguage,
        "ProjectEthnoCode": ethnoCode,
        "ProjectLanguage": language,
        "ProjectCountry": country,
        "ProjectMajorityLanguage": majorityLanguage,
        "ConsultantEmail": email,
        "TrainerEmail": trainerEmail
    };

    request = makePost("RegisterPhone.php", phoneRegistrationData);
    if (!request.Success) {
        msg = request.Error;
        result = 0;
        return ret(title, msg, result);
    }

    var newPhoneId = "abcdef1234567890";
    phoneRegistrationData.PhoneId = newPhoneId;

    // invalid key
    phoneRegistrationData.Key = "sdfdsf";
    request = makePost("RegisterPhone.php", phoneRegistrationData);
    if (request.Success) {
        msg = "Accepted request with invalid key";
        result = -1;
        return ret(title, msg, result);
    }
    phoneRegistrationData.Key = key;

    // invalid phone id
    phoneRegistrationData.PhoneId = "h123456789abcdef";
    request = makePost("RegisterPhone.php", phoneRegistrationData);
    if (request.Success) {
        msg = "Accepted request with invalid phone id characters";
        result = -1;
        return ret(title, msg, result);
    }
    phoneRegistrationData.PhoneId = "123456789abcdef";
    request = makePost("RegisterPhone.php", phoneRegistrationData);
    if (request.Success) {
        msg = "Accepted request with short phone id";
        result = -1;
        return ret(title, msg, result);
    }
    phoneRegistrationData.PhoneId = "00123456789abcdef";
    request = makePost("RegisterPhone.php", phoneRegistrationData);
    if (request.Success) {
        msg = "Accepted request with long phone id";
        result = -1;
        return ret(title, msg, result);
    }
    phoneRegistrationData.PhoneId = newPhoneId;

    // invalid TranslatorEmail
    phoneRegistrationData.TranslatorEmail = "sdfdsf";
    request = makePost("RegisterPhone.php", phoneRegistrationData);
    if (request.Success) {
        msg = "Accepted request with invalid translator email";
        result = -1;
        return ret(title, msg, result);
    }
    phoneRegistrationData.TranslatorEmail = "hey@example";
    request = makePost("RegisterPhone.php", phoneRegistrationData);
    if (request.Success) {
        msg = "Accepted request with invalid translator email";
        result = -1;
        return ret(title, msg, result);
    }
    phoneRegistrationData.TranslatorEmail = "jj@.com";
    request = makePost("RegisterPhone.php", phoneRegistrationData);
    if (request.Success) {
        msg = "Accepted request with invalid translator email";
        result = -1;
        return ret(title, msg, result);
    }
    phoneRegistrationData.TranslatorEmail = translatorEmail;

    // invalid TranslatorPhone
    phoneRegistrationData.TranslatorPhone = "sdfdsf";
    request = makePost("RegisterPhone.php", phoneRegistrationData);
    if (request.Success) {
        msg = "Accepted request with invalid phone number";
        result = -1;
        return ret(title, msg, result);
    }
    phoneRegistrationData.TranslatorPhone = "12345678";
    request = makePost("RegisterPhone.php", phoneRegistrationData);
    if (request.Success) {
        msg = "Accepted request with invalid phone number";
        result = -1;
        return ret(title, msg, result);
    }
    phoneRegistrationData.TranslatorPhone = "12345678900";
    request = makePost("RegisterPhone.php", phoneRegistrationData);
    if (request.Success) {
        msg = "Accepted request with invalid phone number";
        result = -1;
        return ret(title, msg, result);
    }
    phoneRegistrationData.TranslatorPhone = "123456789f";
    request = makePost("RegisterPhone.php", phoneRegistrationData);
    if (request.Success) {
        msg = "Accepted request with invalid phone number";
        result = -1;
        return ret(title, msg, result);
    }
    phoneRegistrationData.TranslatorPhone = "zxcasdwert";
    request = makePost("RegisterPhone.php", phoneRegistrationData);
    if (request.Success) {
        msg = "Accepted request with invalid phone number";
        result = -1;
        return ret(title, msg, result);
    }
    phoneRegistrationData.TranslatorPhone = translatorPhone;

    // invalid ProjectEthnoCode
    phoneRegistrationData.ProjectEthnoCode = "sdfdsf";
    request = makePost("RegisterPhone.php", phoneRegistrationData);
    if (request.Success) {
        msg = "Accepted request with invalid ethno code (long)";
        result = -1;
        return ret(title, msg, result);
    }
    phoneRegistrationData.ProjectEthnoCode = "sd";
    request = makePost("RegisterPhone.php", phoneRegistrationData);
    if (request.Success) {
        msg = "Accepted request with invalid ethno code (short)";
        result = -1;
        return ret(title, msg, result);
    }
    phoneRegistrationData.ProjectEthnoCode = "sd2";
    request = makePost("RegisterPhone.php", phoneRegistrationData);
    if (request.Success) {
        msg = "Accepted request with invalid ethno code (number)";
        result = -1;
        return ret(title, msg, result);
    }
    phoneRegistrationData.ProjectEthnoCode = ethnoCode;

    // invalid ConsultantEmail
    phoneRegistrationData.ConsultantEmail = "sdfdsf";
    request = makePost("RegisterPhone.php", phoneRegistrationData);
    if (request.Success) {
        msg = "Accepted request with invalid consultant email";
        result = -1;
        return ret(title, msg, result);
    }
    phoneRegistrationData.ConsultantEmail = "hey@example";
    request = makePost("RegisterPhone.php", phoneRegistrationData);
    if (request.Success) {
        msg = "Accepted request with invalid consultant email";
        result = -1;
        return ret(title, msg, result);
    }
    phoneRegistrationData.ConsultantEmail = "jj@.com";
    request = makePost("RegisterPhone.php", phoneRegistrationData);
    if (request.Success) {
        msg = "Accepted request with invalid consultant email";
        result = -1;
        return ret(title, msg, result);
    }
    phoneRegistrationData.ConsultantEmail = email;

    // invalid TrainerEmail
    phoneRegistrationData.TrainerEmail = "sdfdsf";
    request = makePost("RegisterPhone.php", phoneRegistrationData);
    if (request.Success) {
        msg = "Accepted request with invalid trainer email";
        result = -1;
        return ret(title, msg, result);
    }
    phoneRegistrationData.TrainerEmail = "hey@example";
    request = makePost("RegisterPhone.php", phoneRegistrationData);
    if (request.Success) {
        msg = "Accepted request with invalid trainer email";
        result = -1;
        return ret(title, msg, result);
    }
    phoneRegistrationData.TrainerEmail = "jj@.com";
    request = makePost("RegisterPhone.php", phoneRegistrationData);
    if (request.Success) {
        msg = "Accepted request with invalid trainer email";
        result = -1;
        return ret(title, msg, result);
    }
    phoneRegistrationData.TrainerEmail = trainerEmail;
    return ret(title, msg, 1);
}
testFunctions.push(MobileRegisterPhone);

function MobileRequestRemoteReview() {
    title = "Mobile: Request Review";
    msg = "Success!";

    for (var i = 0; i < templates.length; ++i) {
        var template = templates[i];
        var numSlides = template.length;
        request = makePost("RequestRemoteReview.php", {
            "Key": key,
            "PhoneId": phoneId,
            "TemplateTitle": template,
            "NumberOfSlides": numSlides
        });
        if (!request.Success) {
            msg = request.Error;
            result = 0;
            return ret(title, msg, result);
        }
    }

    // Don't allow duplicate requests
    request = makePost("RequestRemoteReview.php", {
        "Key": key,
        "PhoneId": phoneId,
        "TemplateTitle": templates[0],
        "NumberOfSlides": templates[0].length
    });
    if (request.Success) {
        msg = "Allowed duplicate request review";
        result = -1;
        return ret(title, msg, result);
    }

    // Test invalid key
    request = makePost("RequestRemoteReview.php", {
        "Key": "key",
        "PhoneId": phoneId,
        "TemplateTitle": "Invalid Key Template",
        "NumberOfSlides": 10
    });
    if (request.Success) {
        msg = "Allowed duplicate request review";
        result = -1;
        return ret(title, msg, result);
    }

    // Test invalid phone id
    request = makePost("RequestRemoteReview.php", {
        "Key": key,
        "PhoneId": "abdf",
        "TemplateTitle": "Invalid Key Template",
        "NumberOfSlides": 10
    });
    if (request.Success) {
        msg = "Allowed short phone id";
        result = -1;
        return ret(title, msg, result);
    }
    request = makePost("RequestRemoteReview.php", {
        "Key": key,
        "PhoneId": "3324abdf123123123",
        "TemplateTitle": "Invalid Key Template",
        "NumberOfSlides": 10
    });
    if (request.Success) {
        msg = "Allowed long phone id";
        result = -1;
        return ret(title, msg, result);
    }
    request = makePost("RequestRemoteReview.php", {
        "Key": key,
        "PhoneId": "1234567890abcdeg",
        "TemplateTitle": "Invalid Key Template",
        "NumberOfSlides": 10
    });
    if (request.Success) {
        msg = "Allowed invalid characters in phone id";
        result = -1;
        return ret(title, msg, result);
    }

    // test invalid number of slides
    request = makePost("RequestRemoteReview.php", {
        "Key": key,
        "PhoneId": phoneId,
        "TemplateTitle": "Next template name",
        "NumberOfSlides": -1
    });
    if (request.Success) {
        msg = "Allowed negative number of slides";
        result = -1;
        return ret(title, msg, result);
    }
    request = makePost("RequestRemoteReview.php", {
        "Key": key,
        "PhoneId": phoneId,
        "TemplateTitle": "Next template name",
        "NumberOfSlides": 0
    });
    if (request.Success) {
        msg = "Allowed zero number of slides";
        result = -1;
        return ret(title, msg, result);
    }
    
    return ret(title, msg, 1);
}
testFunctions.push(MobileRequestRemoteReview);

for (var i = 0; i < testFunctions.length; ++i) {
    var curTest = testFunctions[i];
    var result = curTest();
    
    $("#tests").append(
          '<div class="w3-card-4" style="float:left;width:40%;margin-bottom:20px;margin-right:25px;">'
        + '    <header class="w3-container w3-light-gray">'
        + '    <h4>' + result.title + '</h4>'
        + '    </header>'

        + '    <div class="w3-container">'
        + '    <p>' + result.msg + '</p>'
        + '    </div>'

        + '    <footer class="w3-container w3-' + result.color + '">'
        + '    <h5>' + result.result + '</h5>'
        + '    </footer>'
        + '</div>'
    );
}